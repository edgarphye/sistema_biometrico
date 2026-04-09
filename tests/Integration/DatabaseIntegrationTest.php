<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests de Integración para Base de Datos
 * Pruebas de conexión real y operaciones CRUD
 */
class DatabaseIntegrationTest extends TestCase
{
    private Database $database;
    private PDO $pdo;

    protected function setUp(): void
    {
        // Inicializar base de datos de testing
        createTestDatabase();
        
        $this->database = new Database();
        $this->pdo = $this->database->getConnection();
        $this->pdo->exec("USE " . TEST_DB_NAME);
    }

    protected function tearDown(): void
    {
        cleanupTestDatabase();
    }

    /**
     * Test de conexión real a base de datos
     */
    public function testRealDatabaseConnection(): void
    {
        $this->assertInstanceOf(PDO::class, $this->pdo);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $this->pdo->getAttribute(PDO::ATTR_ERRMODE));
    }

    /**
     * Test de creación y consulta de tablas
     */
    public function testCreateAndQueryTable(): void
    {
        // Crear tabla de prueba
        $createTableSql = "
            CREATE TABLE test_integracion (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                activo BOOLEAN DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_nombre (nombre),
                INDEX idx_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($createTableSql);
        
        // Verificar que la tabla existe
        $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'test_integracion'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotNull($result);
    }

    /**
     * Test de operaciones CRUD completas
     */
    public function testCrudOperations(): void
    {
        // Crear tabla
        $this->pdo->exec("
            CREATE TABLE test_crud (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                email VARCHAR(100) UNIQUE,
                edad INT,
                activo BOOLEAN DEFAULT 1
            ) ENGINE=InnoDB
        ");
        
        // CREATE - Insertar registro
        $stmt = $this->pdo->prepare("
            INSERT INTO test_crud (nombre, email, edad, activo) 
            VALUES (:nombre, :email, :edad, :activo)
        ");
        
        $data = [
            ':nombre' => 'Usuario Test',
            ':email' => 'test@example.com',
            ':edad' => 25,
            ':activo' => true
        ];
        
        $this->assertTrue($stmt->execute($data));
        $insertId = $this->pdo->lastInsertId();
        $this->assertNotEmpty($insertId);
        
        // READ - Consultar registro
        $stmt = $this->pdo->prepare("SELECT * FROM test_crud WHERE id = :id");
        $stmt->execute([':id' => $insertId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotNull($row);
        $this->assertEquals('Usuario Test', $row['nombre']);
        $this->assertEquals('test@example.com', $row['email']);
        $this->assertEquals(25, $row['edad']);
        $this->assertEquals(1, $row['activo']);
        
        // UPDATE - Actualizar registro
        $stmt = $this->pdo->prepare("
            UPDATE test_crud 
            SET nombre = :nombre, edad = :edad 
            WHERE id = :id
        ");
        
        $updateData = [
            ':nombre' => 'Usuario Actualizado',
            ':edad' => 26,
            ':id' => $insertId
        ];
        
        $this->assertTrue($stmt->execute($updateData));
        
        // Verificar actualización
        $stmt = $this->pdo->prepare("SELECT nombre, edad FROM test_crud WHERE id = :id");
        $stmt->execute([':id' => $insertId]);
        $updatedRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('Usuario Actualizado', $updatedRow['nombre']);
        $this->assertEquals(26, $updatedRow['edad']);
        
        // DELETE - Eliminar registro
        $stmt = $this->pdo->prepare("DELETE FROM test_crud WHERE id = :id");
        $this->assertTrue($stmt->execute([':id' => $insertId]));
        
        // Verificar eliminación
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM test_crud WHERE id = :id");
        $stmt->execute([':id' => $insertId]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $this->assertEquals(0, $count);
    }

    /**
     * Test de transacciones complejas
     */
    public function testComplexTransactions(): void
    {
        // Crear tablas para testing de transacciones
        $this->pdo->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                saldo DECIMAL(10,2) DEFAULT 0.00
            ) ENGINE=InnoDB
        ");
        
        $this->pdo->exec("
            CREATE TABLE transfers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                from_user INT NOT NULL,
                to_user INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (from_user) REFERENCES users(id),
                FOREIGN KEY (to_user) REFERENCES users(id)
            ) ENGINE=InnoDB
        ");
        
        // Insertar usuarios
        $this->pdo->exec("INSERT INTO users (nombre, saldo) VALUES ('Usuario A', 1000.00)");
        $this->pdo->exec("INSERT INTO users (nombre, saldo) VALUES ('Usuario B', 500.00)");
        
        // Iniciar transacción
        $this->pdo->beginTransaction();
        
        try {
            // Realizar transferencia
            $amount = 200.00;
            
            // Descontar del usuario A
            $stmt = $this->pdo->prepare("UPDATE users SET saldo = saldo - :amount WHERE id = 1 AND saldo >= :amount");
            $stmt1 = $stmt->execute([':amount' => $amount]);
            
            // Acreditar al usuario B
            $stmt = $this->pdo->prepare("UPDATE users SET saldo = saldo + :amount WHERE id = 2");
            $stmt2 = $stmt->execute([':amount' => $amount]);
            
            // Registrar transferencia
            $stmt = $this->pdo->prepare("
                INSERT INTO transfers (from_user, to_user, amount) 
                VALUES (:from, :to, :amount)
            ");
            $stmt3 = $stmt->execute([
                ':from' => 1,
                ':to' => 2,
                ':amount' => $amount
            ]);
            
            $this->assertTrue($stmt1);
            $this->assertTrue($stmt2);
            $this->assertTrue($stmt3);
            
            // Commit de la transacción
            $this->pdo->commit();
            
            // Verificar saldos finales
            $stmt = $this->pdo->prepare("SELECT id, nombre, saldo FROM users ORDER BY id");
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $this->assertEquals(800.00, $users[0]['saldo']); // 1000 - 200
            $this->assertEquals(700.00, $users[1]['saldo']); // 500 + 200
            
            // Verificar transferencia registrada
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM transfers WHERE amount = :amount");
            $stmt->execute([':amount' => $amount]);
            $transferCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $this->assertEquals(1, $transferCount);
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->fail("La transacción falló: " . $e->getMessage());
        }
    }

    /**
     * Test de rollback en transacciones
     */
    public function testTransactionRollback(): void
    {
        // Crear tabla de prueba
        $this->pdo->exec("
            CREATE TABLE test_rollback (
                id INT AUTO_INCREMENT PRIMARY KEY,
                value VARCHAR(100)
            ) ENGINE=InnoDB
        ");
        
        // Contar registros iniciales
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM test_rollback");
        $stmt->execute();
        $initialCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Iniciar transacción
        $this->pdo->beginTransaction();
        
        try {
            // Insertar datos
            $stmt = $this->pdo->prepare("INSERT INTO test_rollback (value) VALUES (:value)");
            $stmt->execute([':value' => 'Test Rollback 1']);
            $stmt->execute([':value' => 'Test Rollback 2']);
            
            // Forzar un error para probar rollback
            $stmt = $this->pdo->prepare("INSERT INTO test_rollback (value) VALUES (:value)");
            $stmt->execute([':value' => str_repeat('A', 200)]); // Exceder longitud
            
            $this->pdo->commit();
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            
            // Verificar que no se insertaron datos
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM test_rollback");
            $stmt->execute();
            $finalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            $this->assertEquals($initialCount, $finalCount);
        }
    }

    /**
     * Test de índices y performance
     */
    public function testIndexesAndPerformance(): void
    {
        // Crear tabla con índices
        $this->pdo->exec("
            CREATE TABLE test_indexes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                email VARCHAR(100),
                area VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_nombre (nombre),
                INDEX idx_email (email),
                INDEX idx_area (area),
                INDEX idx_created (created_at),
                INDEX idx_area_nombre (area, nombre)
            ) ENGINE=InnoDB
        ");
        
        // Insertar datos de prueba
        $stmt = $this->pdo->prepare("
            INSERT INTO test_indexes (nombre, email, area) 
            VALUES (:nombre, :email, :area)
        ");
        
        $areas = ['IT', 'RH', 'Ventas', 'Finanzas'];
        for ($i = 1; $i <= 100; $i++) {
            $area = $areas[$i % 4];
            $stmt->execute([
                ':nombre' => "Usuario {$i}",
                ':email' => "user{$i}@example.com",
                ':area' => $area
            ]);
        }
        
        // Test de consulta con índice
        $startTime = microtime(true);
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM test_indexes 
            WHERE area = :area 
            ORDER BY nombre 
            LIMIT 10
        ");
        $stmt->execute([':area' => 'IT']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convertir a milisegundos
        
        $this->assertCount(10, $results);
        $this->assertLessThan(100, $executionTime); // Debe ser rápido (< 100ms)
        
        // Verificar que los índices existen
        $stmt = $this->pdo->prepare("
            SHOW INDEX FROM test_indexes 
            WHERE Key_name != 'PRIMARY'
        ");
        $stmt->execute();
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertGreaterThan(0, count($indexes));
        
        $indexNames = array_unique(array_column($indexes, 'Key_name'));
        $this->assertContains('idx_nombre', $indexNames);
        $this->assertContains('idx_email', $indexNames);
        $this->assertContains('idx_area', $indexNames);
    }

    /**
     * Test de integridad referencial
     */
    public function testReferentialIntegrity(): void
    {
        // Crear tablas con relación
        $this->pdo->exec("
            CREATE TABLE departments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL UNIQUE
            ) ENGINE=InnoDB
        ");
        
        $this->pdo->exec("
            CREATE TABLE employees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(100) NOT NULL,
                department_id INT,
                FOREIGN KEY (department_id) REFERENCES departments(id) 
                ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB
        ");
        
        // Insertar departamento
        $stmt = $this->pdo->prepare("INSERT INTO departments (nombre) VALUES (:nombre)");
        $this->assertTrue($stmt->execute([':nombre' => 'IT Department']));
        $deptId = $this->pdo->lastInsertId();
        
        // Insertar empleado con relación válida
        $stmt = $this->pdo->prepare("
            INSERT INTO employees (nombre, department_id) 
            VALUES (:nombre, :dept_id)
        ");
        $this->assertTrue($stmt->execute([
            ':nombre' => 'John Doe',
            ':dept_id' => $deptId
        ]));
        
        // Intentar insertar empleado con department_id inválido
        $stmt = $this->pdo->prepare("
            INSERT INTO employees (nombre, department_id) 
            VALUES (:nombre, :dept_id)
        ");
        
        $this->expectException(PDOException::class);
        $stmt->execute([
            ':nombre' => 'Jane Doe',
            ':dept_id' => 999 // ID que no existe
        ]);
    }

    /**
     * Test de tipos de datos y encoding
     */
    public function testDataTypesAndEncoding(): void
    {
        $this->pdo->exec("
            CREATE TABLE test_datatypes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                texto VARCHAR(255),
                descripcion TEXT,
                numero INT,
                decimal_num DECIMAL(10,2),
                booleano BOOLEAN,
                fecha DATE,
                fecha_hora DATETIME,
                json_datos JSON,
                enum_tipo ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo'
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $testData = [
            'texto' => 'Texto con caracteres especiales: ñáéíóú',
            'descripcion' => 'Descripción larga con caracteres unicode: 🚀 💻 📱',
            'numero' => 42,
            'decimal_num' => 123.45,
            'booleano' => true,
            'fecha' => '2024-01-15',
            'fecha_hora' => '2024-01-15 14:30:00',
            'json_datos' => json_encode(['key' => 'value', 'number' => 123]),
            'enum_tipo' => 'activo'
        ];
        
        // Insertar datos
        $columns = implode(', ', array_keys($testData));
        $placeholders = ':' . implode(', :', array_keys($testData));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO test_datatypes ({$columns}) 
            VALUES ({$placeholders})
        ");
        
        $this->assertTrue($stmt->execute($testData));
        $insertId = $this->pdo->lastInsertId();
        
        // Recuperar y verificar datos
        $stmt = $this->pdo->prepare("SELECT * FROM test_datatypes WHERE id = :id");
        $stmt->execute([':id' => $insertId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($testData['texto'], $row['texto']);
        $this->assertEquals($testData['descripcion'], $row['descripcion']);
        $this->assertEquals($testData['numero'], $row['numero']);
        $this->assertEquals($testData['decimal_num'], $row['decimal_num']);
        $this->assertEquals(1, $row['booleano']); // MySQL almacena boolean como tinyint
        $this->assertEquals($testData['fecha'], $row['fecha']);
        $this->assertEquals($testData['fecha_hora'], $row['fecha_hora']);
        $this->assertEquals($testData['json_datos'], $row['json_datos']);
        $this->assertEquals($testData['enum_tipo'], $row['enum_tipo']);
    }

    /**
     * Test de concurrencia básica
     */
    public function testBasicConcurrency(): void
    {
        $this->pdo->exec("
            CREATE TABLE test_concurrency (
                id INT AUTO_INCREMENT PRIMARY KEY,
                counter INT DEFAULT 0,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB
        ");
        
        // Insertar registro inicial
        $this->pdo->exec("INSERT INTO test_concurrency (counter) VALUES (0)");
        
        // Simular múltiples actualizaciones concurrentes
        $updates = 10;
        for ($i = 0; $i < $updates; $i++) {
            $stmt = $this->pdo->prepare("
                UPDATE test_concurrency 
                SET counter = counter + 1 
                WHERE id = 1
            ");
            $this->assertTrue($stmt->execute());
        }
        
        // Verificar resultado final
        $stmt = $this->pdo->prepare("SELECT counter FROM test_concurrency WHERE id = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals($updates, $result['counter']);
    }
}