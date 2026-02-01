<?php

namespace Exceptions;

use Services\Logger;

/**
 * Global Exception Handler
 * 
 * Handles all uncaught exceptions and provides proper error responses
 */
class Handler
{
    private $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    /**
     * Register this as the global exception handler
     */
    public function register(): void
    {
        set_exception_handler([$this, 'handle']);
        set_error_handler([$this, 'handleError']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle uncaught exceptions
     */
    public function handle(\Throwable $exception): void
    {
        $this->logger->error('Uncaught exception: ' . $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        // If this is an API request, return JSON error
        if ($this->isApiRequest()) {
            $this->sendJsonError($exception);
        } else {
            $this->sendHtmlError($exception);
        }
    }

    /**
     * Handle PHP errors
     */
    public function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $this->logger->error("PHP Error: {$message}", [
            'severity' => $severity,
            'file' => $file,
            'line' => $line
        ]);

        return true;
    }

    /**
     * Handle fatal errors
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->logger->critical("Fatal error: {$error['message']}", [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
        }
    }

    /**
     * Check if request is an API request
     */
    private function isApiRequest(): bool
    {
        // Check if request is to an API endpoint
        $path = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($path, '/api/') !== false || 
               strpos($path, '/php/') !== false ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    /**
     * Send JSON error response for API requests
     */
    private function sendJsonError(\Throwable $exception): void
    {
        http_response_code(500);
        header('Content-Type: application/json');

        $response = [
            'success' => false,
            'error' => 'An error occurred',
            'errors' => []
        ];

        // Include error details in debug mode
        if ($_ENV['APP_DEBUG'] ?? false) {
            $response['error'] = $exception->getMessage();
            $response['file'] = $exception->getFile();
            $response['line'] = $exception->getLine();
            $response['trace'] = $exception->getTraceAsString();
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send HTML error response for web requests
     */
    private function sendHtmlError(\Throwable $exception): void
    {
        http_response_code(500);
        
        if ($_ENV['APP_DEBUG'] ?? false) {
            // Show detailed error in debug mode
            echo "<h1>Error</h1>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        } else {
            // Generic error message in production
            echo "<h1>An error occurred</h1>";
            echo "<p>Please contact the system administrator if this problem persists.</p>";
        }
        exit;
    }
}
