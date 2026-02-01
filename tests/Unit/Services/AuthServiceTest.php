<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Services\AuthService;

/**
 * AuthService Unit Tests
 */
class AuthServiceTest extends TestCase
{
    private $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /**
     * Test password verification with hashed password
     */
    public function testPasswordVerificationWithHash(): void
    {
        $password = 'test123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->authService);
        $method = $reflection->getMethod('verifyPassword');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->authService, $password, $hash);
        $this->assertTrue($result);
    }

    /**
     * Test password verification with plain text (backward compatibility)
     */
    public function testPasswordVerificationWithPlainText(): void
    {
        $password = 'test123';
        $plainText = 'test123';
        
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->authService);
        $method = $reflection->getMethod('verifyPassword');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->authService, $password, $plainText);
        $this->assertTrue($result);
    }

    /**
     * Test authentication status check
     */
    public function testIsAuthenticated(): void
    {
        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session
        $_SESSION = [];
        
        $this->assertFalse($this->authService->isAuthenticated());
        
        // Set session
        $_SESSION['id'] = 1;
        $this->assertTrue($this->authService->isAuthenticated());
    }
}
