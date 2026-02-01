<?php

namespace Services;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

/**
 * Centralized Logging Service
 * 
 * Provides consistent logging throughout the application using Monolog
 */
class Logger
{
    private static $instance = null;
    private $logger;

    private function __construct()
    {
        $this->logger = new MonologLogger('ticket_system');
        
        // Get log level from environment or default to ERROR
        $logLevel = strtoupper($_ENV['LOG_LEVEL'] ?? 'ERROR');
        $level = constant("Monolog\Logger::{$logLevel}");
        
        // Log file path
        $logFile = $_ENV['LOG_FILE'] ?? 'logs/app.log';
        $logDir = dirname($logFile);
        
        // Create log directory if it doesn't exist
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Rotating file handler (keeps 30 days of logs)
        $fileHandler = new RotatingFileHandler($logFile, 30, $level);
        $fileHandler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        ));
        $this->logger->pushHandler($fileHandler);
        
        // Also log errors to PHP error log
        if ($_ENV['APP_DEBUG'] ?? false) {
            $errorHandler = new StreamHandler('php://stderr', MonologLogger::DEBUG);
            $this->logger->pushHandler($errorHandler);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    /**
     * Get the underlying Monolog logger instance
     */
    public function getLogger(): MonologLogger
    {
        return $this->logger;
    }
}
