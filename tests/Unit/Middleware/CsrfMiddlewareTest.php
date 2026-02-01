<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Middleware\CsrfMiddleware;

/**
 * CSRF Middleware Unit Tests
 */
class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session
        $_SESSION = [];
    }

    /**
     * Test token generation
     */
    public function testGenerateToken(): void
    {
        $token = CsrfMiddleware::generateToken();
        
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes = 64 hex characters
    }

    /**
     * Test token validation with valid token
     */
    public function testValidateTokenWithValidToken(): void
    {
        $token = CsrfMiddleware::generateToken();
        $result = CsrfMiddleware::validateToken($token);
        
        $this->assertTrue($result);
    }

    /**
     * Test token validation with invalid token
     */
    public function testValidateTokenWithInvalidToken(): void
    {
        CsrfMiddleware::generateToken();
        $invalidToken = 'invalid_token_12345';
        $result = CsrfMiddleware::validateToken($invalidToken);
        
        $this->assertFalse($result);
    }

    /**
     * Test token validation with no token
     */
    public function testValidateTokenWithNoToken(): void
    {
        $result = CsrfMiddleware::validateToken();
        
        $this->assertFalse($result);
    }
}
