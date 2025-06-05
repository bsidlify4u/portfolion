<?php
namespace Portfolion\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Portfolion\Config;

class JWTAuthentication {
    private $config;
    private $key;
    private $algorithm;
    private $ttl;
    
    public function __construct() {
        $this->config = Config::getInstance();
        $this->key = $this->config->get('auth.jwt_key', 'your-secret-key');
        $this->algorithm = $this->config->get('auth.jwt_algorithm', 'HS256');
        $this->ttl = $this->config->get('auth.jwt_ttl', 3600);
    }
    
    public function generateToken(array $payload): string {
        $issuedAt = time();
        $expire = $issuedAt + $this->ttl;
        
        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => $this->config->get('app.url', 'http://localhost'),
        ]);
        
        return JWT::encode($tokenPayload, $this->key, $this->algorithm);
    }
    
    public function validateToken(string $token) {
        try {
            return JWT::decode($token, new Key($this->key, $this->algorithm));
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid token: ' . $e->getMessage());
        }
    }
    
    public function refreshToken(string $token): string {
        $payload = $this->validateToken($token);
        
        // Remove timing claims
        unset($payload->iat);
        unset($payload->exp);
        
        // Generate new token
        return $this->generateToken((array) $payload);
    }
    
    public function getPayload(string $token) {
        return $this->validateToken($token);
    }
    
    public function blacklistToken(string $token): void {
        // TODO: Implement token blacklisting
        // This should store the token in a blacklist (e.g., Redis/Cache)
        // until its expiration time
    }
}
