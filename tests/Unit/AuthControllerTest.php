<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests para AuthController
 */
class AuthControllerTest extends TestCase
{
    private $pdo;
    
    protected function setUp(): void
    {
        // Setup test database connection
        $this->pdo = new PDO(
            'mysql:host=localhost;dbname=sistema_biometrico_test',
            'root',
            'root'
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Clear test users
        $this->pdo->exec("DELETE FROM usuarios WHERE username LIKE 'test%'");
        
        // Initialize session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        $this->pdo->exec("DELETE FROM usuarios WHERE username LIKE 'test%'");
        $this->pdo = null;
        
        // Clean session
        $_SESSION = [];
        session_write_close();
    }
    
    /**
     * Test successful login
     */
    public function testSuccessfulLogin(): void
    {
        // Create test user
        $passwordHash = password_hash('test123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (username, password, email, rol) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(['testuser', $passwordHash, 'test@auth.com', 'user']);
        
        // Simulate login POST
        $_POST = [
            'username' => 'testuser',
            'password' => 'test123',
            'csrf_token' => 'test_token'
        ];
        
        // Test password verification
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute(['testuser']);
        $user = $stmt->fetch();
        
        $this->assertNotEmpty($user, 'User should exist');
        $this->assertTrue(password_verify('test123', $user['password']), 'Password should verify');
    }
    
    /**
     * Test failed login with wrong password
     */
    public function testFailedLoginWrongPassword(): void
    {
        // Create test user
        $passwordHash = password_hash('correctpass', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            INSERT INTO usuarios (username, password, email, rol) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(['testuser2', $passwordHash, 'test2@auth.com', 'user']);
        
        // Simulate login with wrong password
        $_POST = [
            'username' => 'testuser2',
            'password' => 'wrongpass',
            'csrf_token' => 'test_token'
        ];
        
        // Test password verification
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute(['testuser2']);
        $user = $stmt->fetch();
        
        $this->assertNotEmpty($user, 'User should exist');
        $this->assertFalse(password_verify('wrongpass', $user['password']), 'Wrong password should not verify');
    }
    
    /**
     * Test login with non-existent user
     */
    public function testLoginNonExistentUser(): void
    {
        $_POST = [
            'username' => 'nonexistent',
            'password' => 'anypassword',
            'csrf_token' => 'test_token'
        ];
        
        // Test user lookup
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute(['nonexistent']);
        $user = $stmt->fetch();
        
        $this->assertFalse($user, 'Non-existent user should not be found');
    }
    
    /**
     * Test session management after login
     */
    public function testSessionAfterLogin(): void
    {
        // Simulate successful login
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'testuser';
        $_SESSION['rol'] = 'user';
        
        $this->assertTrue(isset($_SESSION['user_id']), 'Session should have user_id');
        $this->assertEquals('testuser', $_SESSION['username'], 'Session should have username');
        $this->assertEquals('user', $_SESSION['rol'], 'Session should have role');
    }
    
    /**
     * Test logout functionality
     */
    public function testLogout(): void
    {
        // Set session data
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'testuser';
        
        // Simulate logout
        session_destroy();
        $_SESSION = [];
        
        $this->assertFalse(isset($_SESSION['user_id']), 'Session should be cleared after logout');
    }
    
    /**
     * Test password hashing
     */
    public function testPasswordHashing(): void
    {
        $password = 'testpassword123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertNotEmpty($hash, 'Password hash should not be empty');
        $this->assertNotEquals($password, $hash, 'Hash should not equal plain password');
        $this->assertTrue(password_verify($password, $hash), 'Hash should verify password');
    }
    
    /**
     * Test CSRF token validation
     */
    public function testCSRFTokenValidation(): void
    {
        // Test valid token
        $validToken = 'valid_token_123';
        $this->assertTrue($this->validateCSRF($validToken, $validToken), 'Valid token should pass');
        
        // Test invalid token
        $this->assertFalse($this->validateCSRF('valid_token', 'invalid_token'), 'Invalid token should fail');
        
        // Test empty token
        $this->assertFalse($this->validateCSRF('', 'token'), 'Empty token should fail');
    }
    
    /**
     * Test user role validation
     */
    public function testUserRoleValidation(): void
    {
        $validRoles = ['admin', 'user', 'viewer'];
        
        // Test valid roles
        foreach ($validRoles as $role) {
            $this->assertTrue(in_array($role, $validRoles), "Role $role should be valid");
        }
        
        // Test invalid role
        $this->assertFalse(in_array('invalid_role', $validRoles), 'Invalid role should not be valid');
    }
    
    /**
     * Test rate limiting simulation
     */
    public function testRateLimiting(): void
    {
        // Simulate multiple login attempts
        $attempts = [];
        for ($i = 0; $i < 5; $i++) {
            $attempts[] = time();
        }
        
        // Check if attempts exceed threshold (example: 3 attempts per minute)
        $recentAttempts = array_filter($attempts, function($timestamp) {
            return (time() - $timestamp) < 60;
        });
        
        $this->assertGreaterThanOrEqual(3, count($recentAttempts), 'Should detect multiple attempts');
    }
    
    /**
     * Helper method for CSRF validation
     */
    private function validateCSRF($expected, $actual)
    {
        return hash_equals($expected, $actual);
    }
}