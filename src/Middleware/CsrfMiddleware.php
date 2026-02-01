<?php

namespace Middleware;

/**
 * CSRF Protection Middleware
 * 
 * Provides CSRF token generation and validation
 */
class CsrfMiddleware
{
    private const TOKEN_NAME = '_token';
    private const TOKEN_LENGTH = 32;

    /**
     * Generate a CSRF token
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }

        return $_SESSION[self::TOKEN_NAME];
    }

    /**
     * Get the current CSRF token
     */
    public static function getToken(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION[self::TOKEN_NAME] ?? null;
    }

    /**
     * Validate CSRF token
     */
    public static function validateToken(?string $token = null): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = $token ?? $_POST[self::TOKEN_NAME] ?? $_GET[self::TOKEN_NAME] ?? null;
        $sessionToken = $_SESSION[self::TOKEN_NAME] ?? null;

        if ($token === null || $sessionToken === null) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Require CSRF token validation (throws exception if invalid)
     */
    public static function requireToken(): void
    {
        if (!self::validateToken()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid CSRF token'
            ]);
            exit;
        }
    }

    /**
     * Get token name for form fields
     */
    public static function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }
}
