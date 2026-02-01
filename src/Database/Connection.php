<?php

namespace Database;

use Services\Logger;

/**
 * Database Connection Class
 * 
 * Singleton pattern for database connections with environment-based configuration
 */
class Connection
{
    private static $instance = null;
    private $conn;
    private $logger;

    private function __construct()
    {
        $this->logger = Logger::getInstance();
        
        // Load environment variables if not already loaded
        if (!isset($_ENV['DB_HOST'])) {
            $this->loadEnvironment();
        }

        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            $name = $_ENV['DB_NAME'] ?? 'ts_isc';
            $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

            $this->conn = new \mysqli($host, $user, $pass, $name);

            if ($this->conn->connect_error) {
                throw new \Exception("Database connection failed: " . $this->conn->connect_error);
            }

            $this->conn->set_charset($charset);
            $this->conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

            $this->logger->info("Database connection established", [
                'host' => $host,
                'database' => $name
            ]);

        } catch (\Exception $e) {
            $this->logger->critical("Database connection error", [
                'error' => $e->getMessage()
            ]);
            
            // Don't expose database errors in production
            if ($_ENV['APP_DEBUG'] ?? false) {
                die(json_encode(["error" => "Database connection failed: " . $e->getMessage()]));
            } else {
                die(json_encode(["error" => "Database connection failed"]));
            }
        }
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnvironment(): void
    {
        $envFile = __DIR__ . '/../../.env';
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Skip comments
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Parse KEY=VALUE format
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);
                    
                    // Remove quotes if present
                    $value = trim($value, '"\'');
                    
                    $_ENV[$key] = $value;
                }
            }
        } else {
            // Fallback to constants if .env doesn't exist
            if (defined('DB_HOST')) {
                $_ENV['DB_HOST'] = DB_HOST;
            }
            if (defined('DB_USER')) {
                $_ENV['DB_USER'] = DB_USER;
            }
            if (defined('DB_PASS')) {
                $_ENV['DB_PASS'] = DB_PASS;
            }
            if (defined('DB_NAME')) {
                $_ENV['DB_NAME'] = DB_NAME;
            }
            if (defined('DB_CHARSET')) {
                $_ENV['DB_CHARSET'] = DB_CHARSET;
            }
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \mysqli
    {
        return $this->conn;
    }
}
