<?php

namespace Portfolion\Config\Drivers;

use PDO;
use Redis;
use RuntimeException;
use Portfolion\Config\Drivers\FileDriver;
use Portfolion\Config\Drivers\RedisDriver;
use Portfolion\Config\Drivers\DatabaseDriver;
use Portfolion\Config\Drivers\ConfigDriverInterface;

class ConfigDriverFactory {
    /**
     * Create a configuration driver instance
     */
    public static function create(string $driver, array $config = []): ConfigDriverInterface {
        return match ($driver) {
            'file' => new FileDriver($config['path'] ?? dirname(dirname(__DIR__)) . '/config'),
            'redis' => self::createRedisDriver($config),
            'database' => self::createDatabaseDriver($config),
            default => throw new RuntimeException("Unsupported configuration driver: {$driver}")
        };
    }
    
    /**
     * Create a Redis driver instance
     */
    private static function createRedisDriver(array $config): RedisDriver {
        $redis = new Redis();
        $redis->connect(
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 6379
        );
        
        if (isset($config['password'])) {
            $redis->auth($config['password']);
        }
        
        if (isset($config['database'])) {
            $redis->select($config['database']);
        }
        
        return new RedisDriver($redis, $config['prefix'] ?? 'config:');
    }
    
    /**
     * Create a Database driver instance
     */
    private static function createDatabaseDriver(array $config): DatabaseDriver {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'] ?? 'mysql',
            $config['host'] ?? 'localhost',
            $config['port'] ?? 3306,
            $config['database'],
            $config['charset'] ?? 'utf8mb4'
        );
        
        $db = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        
        return new DatabaseDriver($db, $config['table'] ?? 'configurations');
    }
}
