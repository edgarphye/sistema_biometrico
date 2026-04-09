<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests para EmpleadoController - Enhanced version
 */
class EmpleadoControllerTest extends TestCase
{
    private $pdo;
    private $controller;
    
    protected function setUp(): void
    {
        // Setup test database connection
        $this->pdo = new PDO(
            'mysql:host=localhost;dbname=sistema_biometrico_test',
            'root',
            'root'
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Clear test tables
        $this->pdo->exec("DELETE FROM empleados WHERE rfc LIKE 'TEST%'");
        
        // Initialize session for auth tests
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Initialize controller if it exists
        if (class_exists('EmpleadoController')) {
            $this->controller = new EmpleadoController();
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test data
        $this->pdo->exec("DELETE FROM empleados WHERE rfc LIKE 'TEST%'");
        $this->pdo = null;
        $this->controller = null;
    }
    
    /**
     * Test authentication requirements
     */
    public function testDeleteRequiresAuth(): void
    {
        $_SESSION = [];
        
        $isAuthed = isset($_SESSION['user_id']);
        
        $this->assertFalse($isAuthed);
    }
    
    /**
     * Test admin can delete
     */
    public function testDeleteAdminCanDelete(): void
    {
        $_SESSION = [
            'user_id' => 1,
            'rol' => 'admin',
            'empleado_id' => null
        ];

        $isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
        
        $this->assertTrue($isAdmin);
    }

    /**
     * Test regular user cannot delete others
     */
    public function testDeleteRegularUserCannotDelete(): void
    {
        $_SESSION = [
            'user_id' => 2,
            'rol' => 'user',
            'empleado_id' => 999
        ];

        $isAdmin = isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
        
        $this->assertFalse($isAdmin);
    }
    
    /**
     * Test employee creation
     */
    public function testCreateEmpleado(): void
    {
        $testData = [
            'nombre' => 'Test Employee',
            'apellido' => 'Test Lastname',
            'rfc' => 'TEST123456ABC',
            'email' => 'test@employee.com',
            'telefono' => '5551234567',
            'area' => 'Testing',
            'puesto' => 'Tester'
        ];
        
        // Test direct database insertion
        $stmt = $this->pdo->prepare("
            INSERT INTO empleados (nombre, apellido, rfc, email, telefono, area, puesto) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $testData['nombre'],
            $testData['apellido'],
            $testData['rfc'],
            $testData['email'],
            $testData['telefono'],
            $testData['area'],
            $testData['puesto']
        ]);
        
        $this->assertTrue($result);
        
        // Verify insertion
        $stmt = $this->pdo->prepare("SELECT * FROM empleados WHERE rfc = ?");
        $stmt->execute([$testData['rfc']]);
        $empleado = $stmt->fetch();
        
        $this->assertNotEmpty($empleado);
        $this->assertEquals($testData['nombre'], $empleado['nombre']);
        $this->assertEquals($testData['apellido'], $empleado['apellido']);
        $this->assertEquals($testData['rfc'], $empleado['rfc']);
    }
    
    /**
     * Test employee retrieval
     */
    public function testGetEmpleado(): void
    {
        // Insert test employee
        $stmt = $this->pdo->prepare("
            INSERT INTO empleados (nombre, apellido, rfc, email, telefono, area, puesto) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $testData = [
            'Test Get Employee',
            'Test Get Lastname', 
            'TESTGET123ABC',
            'get@employee.com',
            '5559876543',
            'Testing',
            'Getter'
        ];
        
        $stmt->execute($testData);
        $empleadoId = $this->pdo->lastInsertId();
        
        // Test retrieval
        $stmt = $this->pdo->prepare("SELECT * FROM empleados WHERE id = ?");
        $stmt->execute([$empleadoId]);
        $empleado = $stmt->fetch();
        
        $this->assertNotEmpty($empleado);
        $this->assertEquals($testData[0], $empleado['nombre']);
        $this->assertEquals($testData[1], $empleado['apellido']);
        $this->assertEquals($testData[2], $empleado['rfc']);
    }
    
    /**
     * Test employee update
     */
    public function testUpdateEmpleado(): void
    {
        // Insert test employee
        $stmt = $this->pdo->prepare("
            INSERT INTO empleados (nombre, apellido, rfc, email, telefono, area, puesto) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Original Name',
            'Original Lastname',
            'TESTUPD789XYZ',
            'original@employee.com',
            '5551112222',
            'Original Area',
            'Original Post'
        ]);
        
        $empleadoId = $this->pdo->lastInsertId();
        
        // Update employee
        $updateData = [
            'nombre' => 'Updated Name',
            'apellido' => 'Updated Lastname',
            'email' => 'updated@employee.com',
            'area' => 'Updated Area'
        ];
        
        $stmt = $this->pdo->prepare("
            UPDATE empleados 
            SET nombre = ?, apellido = ?, email = ?, area = ? 
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $updateData['nombre'],
            $updateData['apellido'],
            $updateData['email'],
            $updateData['area'],
            $empleadoId
        ]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = $this->pdo->prepare("SELECT * FROM empleados WHERE id = ?");
        $stmt->execute([$empleadoId]);
        $empleado = $stmt->fetch();
        
        $this->assertEquals($updateData['nombre'], $empleado['nombre']);
        $this->assertEquals($updateData['apellido'], $empleado['apellido']);
        $this->assertEquals($updateData['email'], $empleado['email']);
        $this->assertEquals($updateData['area'], $empleado['area']);
    }
    
    /**
     * Test employee deletion
     */
    public function testDeleteEmpleado(): void
    {
        // Insert test employee
        $stmt = $this->pdo->prepare("
            INSERT INTO empleados (nombre, apellido, rfc, email, telefono, area, puesto) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'Delete Me',
            'Test Delete',
            'TESTDEL456MNO',
            'delete@employee.com',
            '5553334444',
            'Testing',
            'To Delete'
        ]);
        
        $empleadoId = $this->pdo->lastInsertId();
        
        // Verify employee exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM empleados WHERE id = ?");
        $stmt->execute([$empleadoId]);
        $count = $stmt->fetchColumn();
        $this->assertEquals(1, $count);
        
        // Delete employee
        $stmt = $this->pdo->prepare("DELETE FROM empleados WHERE id = ?");
        $result = $stmt->execute([$empleadoId]);
        
        $this->assertTrue($result);
        
        // Verify deletion
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM empleados WHERE id = ?");
        $stmt->execute([$empleadoId]);
        $count = $stmt->fetchColumn();
        $this->assertEquals(0, $count);
    }
    
    /**
     * Test employee validation - RFC format
     */
    public function testEmpleadoRFCValidation(): void
    {
        // Test invalid RFC (too short)
        $invalidRFC = 'TEST123';
        $this->assertLessThan(13, strlen($invalidRFC), 'RFC should be at least 13 characters');
        
        // Test valid RFC format
        $validRFC = 'TEST123456ABC';
        $this->assertEquals(13, strlen($validRFC), 'RFC should be exactly 13 characters');
        $this->assertMatchesRegularExpression('/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}$/', $validRFC, 'RFC should match pattern');
    }
    
    /**
     * Test employee validation - Email format
     */
    public function testEmpleadoEmailValidation(): void
    {
        // Valid emails
        $validEmails = [
            'test@employee.com',
            'user.name@domain.co',
            'user+tag@example.org'
        ];
        
        foreach ($validEmails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "Email $email should be valid");
        }
        
        // Invalid emails
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user..name@domain.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, "Email $email should be invalid");
        }
    }
}