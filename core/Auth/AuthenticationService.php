<?php
namespace Portfolion\Auth;

use Portfolion\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;
use Portfolion\Config;
use Portfolion\Security\SecurityServiceProvider;
use Portfolion\Database\QueryBuilder;

class AuthenticationService {
    private QueryBuilder $db;
    /** @var array<string, mixed> */
    private array $config;
    private static ?self $instance = null;
    /** @var array<string, mixed>|null */
    private ?array $user = null;
    private JWTAuthentication $jwtAuth;
    
    private function __construct(QueryBuilder $db) {
        $this->db = $db;
        $this->config = $this->loadConfig();
        $this->jwtAuth = new JWTAuthentication();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Load and validate configuration.
     * 
     * @return array<string, mixed>
     */
    private function loadConfig(): array {
        $config = Config::getInstance();
        return [
            'jwt_secret' => $config->get('app.jwt_secret'),
            'jwt_expiration' => $config->get('app.jwt_expiration', 3600),
            'session_lifetime' => $config->get('app.session_lifetime', 120),
            'remember_lifetime' => $config->get('app.remember_lifetime', 43200),
        ];
    }
    
    public static function getInstance(QueryBuilder $db): self {
        if (self::$instance === null) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }
    
    /**
     * Attempt to authenticate a user.
     * 
     * @param array{email: string, password: string, two_factor_code?: string} $credentials
     * @param bool $remember
     * @return bool
     * @throws RuntimeException If account is not active
     */
    public function attempt(array $credentials, bool $remember = false): bool {
        $user = $this->findUserByCredentials($credentials['email']);
            
        if (!$user || !SecurityServiceProvider::verifyPassword($credentials['password'], $user['password'])) {
            $this->logFailedAttempt($credentials['email']);
            return false;
        }
        
        if (($user['two_factor_enabled'] ?? false) && !$this->verifyTwoFactor($credentials['two_factor_code'] ?? null, $user['id'])) {
            return false;
        }
        
        if (($user['status'] ?? 'inactive') !== 'active') {
            throw new RuntimeException('Account is not active');
        }
        
        $this->login($user, $remember);
        return true;
    }

    /**
     * Find a user by their email.
     * 
     * @param string $email
     * @return array<string, mixed>|null
     */
    private function findUserByCredentials(string $email): ?array {
        return $this->db->table('users')
            ->where('email', '=', $email)
            ->first();
    }
    
    /**
     * Log a failed authentication attempt.
     * 
     * @param string $email
     * @return bool
     */
    private function logFailedAttempt(string $email): bool {
        return $this->db->table('auth_logs')
            ->insert([
                'email' => $email,
                'type' => 'failed_login',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ]);
    }
    
    /**
     * Verify two-factor authentication code.
     * 
     * @param string|null $code
     * @param int $userId
     * @return bool
     */
    private function verifyTwoFactor(?string $code, int $userId): bool {
        if ($code === null) {
            return false;
        }
        
        $twoFactor = $this->db->table('two_factor_codes')
            ->where('user_id', '=', $userId)
            ->where('code', '=', $code)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->first();
            
        if (!$twoFactor) {
            return false;
        }
        
        $this->db->table('two_factor_codes')
            ->where('id', '=', $twoFactor['id'])
            ->delete();
            
        return true;
    }
    
    /**
     * Log the user in.
     * 
     * @param array<string, mixed> $user
     * @param bool $remember
     */
    private function login(array $user, bool $remember = false): void {
        $this->user = $user;
        $_SESSION['user_id'] = $user['id'];
        
        if ($remember) {
            $token = $this->createRememberToken($user['id']);
            if ($token) {
                setcookie(
                    'remember_token',
                    $token,
                    time() + $this->config['remember_lifetime'],
                    '/',
                    '',
                    true,
                    true
                );
            }
        }
        
        $this->db->table('auth_logs')->insert([
            'user_id' => $user['id'],
            'type' => 'login',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Create a remember token for the user.
     * 
     * @param int $userId
     * @return string|null
     */
    private function createRememberToken(int $userId): ?string {
        $token = bin2hex(random_bytes(32));
        
        $inserted = $this->db->table('remember_tokens')
            ->insert([
                'user_id' => $userId,
                'token' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + $this->config['remember_lifetime'])
            ]);
            
        return $inserted ? $token : null;
    }
    
    /**
     * Log the user out.
     */
    public function logout(): void {
        if (isset($_SESSION['user_id'])) {
            $this->db->table('remember_tokens')
                ->where('user_id', '=', $_SESSION['user_id'])
                ->delete();
                
            unset($_SESSION['user_id']);
            $this->user = null;
            
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
        }
        
        session_destroy();
    }
    
    public function check(): bool {
        return $this->user !== null || isset($_SESSION['user_id']);
    }
    
    public function user() {
        if ($this->user !== null) {
            return $this->user;
        }
        
        if (isset($_SESSION['user_id'])) {
            // Here you would typically fetch the user from database
            $this->user = $this->findUserById($_SESSION['user_id']);
            return $this->user;
        }
        
        return null;
    }
    
    public function validateJWT(Request $request): bool {
        $token = $this->getBearerToken($request);
        
        if (!$token) {
            return false;
        }
        
        try {
            $payload = $this->jwtAuth->validateToken($token);
            $this->user = $this->findUserById($payload->user_id);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function getBearerToken(Request $request): ?string {
        $header = $request->getHeader('Authorization');
        if (!$header || !preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return null;
        }
        return $matches[1];
    }
    
    private function findUserById($id) {
        // TODO: Implement user lookup
        // This should be implemented by the application using the framework
        return null;
    }
    
    private function validateCredentials($user, array $credentials): bool {
        // TODO: Implement credential validation
        // This should verify the password hash
        return false;
    }
}
