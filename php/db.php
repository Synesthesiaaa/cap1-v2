<?php
/**
 * Database Connection and Base API Class
 *
 * Provides secure database connections and optimized CRUD operations
 * 
 * NOTE: This file is maintained for backward compatibility.
 * New code should use Database\Connection instead.
 */

// Load environment variables if .env file exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
        }
    }
}

// Database Configuration - only define if not already defined
// Check environment variables first, then fallback to defaults
if (!defined('DB_HOST')) {
    define('DB_HOST', $_ENV['DB_HOST'] ?? "localhost");
}
if (!defined('DB_USER')) {
    define('DB_USER', $_ENV['DB_USER'] ?? "root");
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $_ENV['DB_PASS'] ?? "");
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $_ENV['DB_NAME'] ?? "ts_isc");
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? "utf8mb4");
}

// Singleton Database Connection - only declare if class doesn't exist
if (!class_exists('Database', false)) {
    class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            if ($this->conn->connect_error) {
                throw new Exception("Database connection failed: " . $this->conn->connect_error);
            }

            $this->conn->set_charset(DB_CHARSET);
            $this->conn->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, true);

        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            die(json_encode(["error" => "Database connection failed"]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }
}
} // End of class_exists check

// Global connection variable for backward compatibility
if (!isset($conn)) {
    $conn = Database::getInstance()->getConnection();
}

/**
 * Base API Class for CRUD Operations
 */
if (!class_exists('BaseAPI', false)) {
    abstract class BaseAPI {
    protected $conn;
    protected $table = '';
    protected $primaryKey = 'id';

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /**
     * Validate Session
     */
    protected function validateSession() {
        session_start();
        if (!isset($_SESSION['id'])) {
            $this->sendError("Unauthorized", 401);
        }
        return $_SESSION['id'];
    }

    /**
     * Sanitize Input
     */
    protected function sanitizeInput($input) {
        if (is_string($input)) {
            return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
        }
        if (is_int($input)) {
            return intval($input);
        }
        return $input;
    }

    /**
     * Send JSON Response
     */
    protected function sendResponse($data, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Send Error Response
     */
    protected function sendError($message, $code = 400) {
        $this->sendResponse(["error" => $message], $code);
    }

    /**
     * Execute Prepared Statement
     */
    protected function executePrepared($sql, $params = [], $types = '') {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            return $stmt;
        } catch (Exception $e) {
            error_log("Database Query Error: " . $e->getMessage());
            $this->sendError("Database operation failed");
        }
    }
}
} // End of class_exists check for BaseAPI

/**
 * Query Caching Class
 */
if (!class_exists('QueryCache', false)) {
    class QueryCache {
    private static $cache = [];
    private static $ttl = 300; // 5 minutes

    public static function get($key) {
        if (isset(self::$cache[$key])) {
            if (microtime(true) - self::$cache[$key]['time'] < self::$ttl) {
                return self::$cache[$key]['data'];
            } else {
                unset(self::$cache[$key]);
            }
        }
        return null;
    }

    public static function set($key, $data) {
        self::$cache[$key] = [
            'data' => $data,
            'time' => microtime(true)
        ];
    }

    public static function clear() {
        self::$cache = [];
    }
}
} // End of class_exists check for QueryCache
?>
