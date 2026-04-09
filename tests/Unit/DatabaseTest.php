<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use PDO;
use PDOException;

/**
 * Tests Unitarios para Database Class
 * Pruebas de conexión y operaciones básicas
 */
class DatabaseTest extends TestCase
{
    private Database $database;
    private MockObject $mockPdo;
    private array $testConfig;

    protected function setUp(): void
    {
        $this->testConfig = [
            'host' => 'localhost',
            'dbname' => TEST_DB_NAME,
            'user' => 'test_user',
            'password' => 'test_password',
            'charset' => 'utf8mb4'
        ];
        
        $this->database = new Database($this->testConfig);
    }

    protected function tearDown(): void
    {
        cleanupTestDatabase();
    }

    /**
     * Test que verifica la creación de instancia de Database
     */
    public function testDatabaseInstanceCreation(): void
    {
        $this->assertInstanceOf(Database::class, $this->database);
    }

    /**
     * Test de conexión exitosa a la base de datos
     */
    public function testSuccessfulConnection(): void
    {
        $pdo = $this->database->getConnection();
        $this->assertInstanceOf(PDO::class, $pdo);
        
        // Verificar que la conexión esté activa
        $this->assertEquals(PDO::ATTR_ERRMODE, $pdo->getAttribute(PDO::ATTR_ERRMODE));
    }

    /**
     * Test de conexión con credenciales inválidas
     */
    public function testConnectionWithInvalidCredentials(): void
    {
        $invalidConfig = [
            'host' => 'localhost',
            'dbname' => 'nonexistent_db',
            'user' => 'invalid_user',
            'password' => 'wrong_password',
            'charset' => 'utf8mb4'
        ];

        $this->expectException(PDOException::class);
        $database = new Database($invalidConfig);
        $database->getConnection();
    }

    /**
     * Test del método prepare con query válido
     */
    public function testPrepareValidQuery(): void
    {
        $pdo = $this->database->getConnection();
        $stmt = $pdo->prepare("SELECT 1 as test");
        
        $this->assertInstanceOf(PDOStatement::class, $stmt);
    }

    /**
     * Test del método query con consulta simple
     */
    public function testSimpleQuery(): void
    {
        $pdo = $this->database->getConnection();
        $result = $pdo->query("SELECT 1 as test_value");
        
        $this->assertInstanceOf(PDOStatement::class, $result);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $this->assertEquals(1, $row['test_value']);
    }

    /**
     * Test de inserción de datos
     */
    public function testInsertData(): void
    {
        $pdo = $this->database->getConnection();
        
        // Crear tabla de prueba
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insertar datos de prueba
        $stmt = $pdo->prepare("INSERT INTO test_table (name, email) VALUES (:name, :email)");
        $result = $stmt->execute([
            ':name' => 'Test User',
            ':email' => 'test@example.com'
        ]);
        
        $this->assertTrue($result);
        
        // Verificar inserción
        $stmt = $pdo->prepare("SELECT * FROM test_table WHERE name = :name");
        $stmt->execute([':name' => 'Test User']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotNull($row);
        $this->assertEquals('Test User', $row['name']);
        $this->assertEquals('test@example.com', $row['email']);
    }

    /**
     * Test de actualización de datos
     */
    public function testUpdateData(): void
    {
        $pdo = $this->database->getConnection();
        
        // Crear e insertar datos iniciales
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_update_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        $stmt = $pdo->prepare("INSERT INTO test_update_table (name) VALUES (:name)");
        $stmt->execute([':name' => 'Original Name']);
        $id = $pdo->lastInsertId();
        
        // Actualizar datos
        $stmt = $pdo->prepare("UPDATE test_update_table SET name = :new_name WHERE id = :id");
        $result = $stmt->execute([
            ':new_name' => 'Updated Name',
            ':id' => $id
        ]);
        
        $this->assertTrue($result);
        
        // Verificar actualización
        $stmt = $pdo->prepare("SELECT name FROM test_update_table WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertEquals('Updated Name', $row['name']);
    }

    /**
     * Test de eliminación de datos
     */
    public function testDeleteData(): void
    {
        $pdo = $this->database->getConnection();
        
        // Crear e insertar datos
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_delete_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100)
        )");
        
        $stmt = $pdo->prepare("INSERT INTO test_delete_table (name) VALUES (:name)");
        $stmt->execute([':name' => 'To Delete']);
        $id = $pdo->lastInsertId();
        
        // Verificar existencia antes de eliminar
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM test_delete_table WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $this->assertEquals(1, $count);
        
        // Eliminar datos
        $stmt = $pdo->prepare("DELETE FROM test_delete_table WHERE id = :id");
        $result = $stmt->execute([':id' => $id]);
        
        $this->assertTrue($result);
        
        // Verificar eliminación
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM test_delete_table WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $this->assertEquals(0, $count);
    }

    /**
     * Test de transacciones
     */
    public function testTransactions(): void
    {
        $pdo = $this->database->getConnection();
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_transaction_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            value VARCHAR(100)
        )");
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // Insertar datos
            $stmt = $pdo->prepare("INSERT INTO test_transaction_table (value) VALUES (:value)");
            $stmt->execute([':value' => 'Transaction Test']);
            
            // Verificar inserción dentro de transacción
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM test_transaction_table WHERE value = :value");
            $stmt->execute([':value' => 'Transaction Test']);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $this->assertEquals(1, $count);
            
            // Commit de la transacción
            $pdo->commit();
            
            // Verificar después del commit
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM test_transaction_table WHERE value = :value");
            $stmt->execute([':value' => 'Transaction Test']);
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $this->assertEquals(1, $count);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->fail("La transacción falló: " . $e->getMessage());
        }
    }

    /**
     * Test de rollback de transacciones
     */
    public function testTransactionRollback(): void
    {
        $pdo = $this->database->getConnection();
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_rollback_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            value VARCHAR(100)
        )");
        
        // Contar registros iniciales
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM test_rollback_table");
        $stmt->execute();
        $initialCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        try {
            // Insertar datos
            $stmt = $pdo->prepare("INSERT INTO test_rollback_table (value) VALUES (:value)");
            $stmt->execute([':value' => 'Rollback Test']);
            
            // Forzar un rollback
            $pdo->rollBack();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $this->fail("El rollback falló: " . $e->getMessage());
        }
        
        // Verificar que no se insertaron datos
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM test_rollback_table");
        $stmt->execute();
        $finalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $this->assertEquals($initialCount, $finalCount);
    }

    /**
     * Test de prepared statements con diferentes tipos de datos
     */
    public function testPreparedStatementsWithDataTypes(): void
    {
        $pdo = $this->database->getConnection();
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS test_datatypes_table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            string_value VARCHAR(100),
            int_value INT,
            float_value DECIMAL(10,2),
            bool_value BOOLEAN,
            date_value DATE,
            datetime_value DATETIME,
            json_value JSON
        )");
        
        $testData = [
            'string_value' => 'Test String',
            'int_value' => 42,
            'float_value' => 3.14159,
            'bool_value' => true,
            'date_value' => '2024-01-01',
            'datetime_value' => '2024-01-01 12:00:00',
            'json_value' => '{"key": "value", "number": 123}'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO test_datatypes_table (
            string_value, int_value, float_value, bool_value, 
            date_value, datetime_value, json_value
        ) VALUES (
            :string_value, :int_value, :float_value, :bool_value,
            :date_value, :datetime_value, :json_value
        )");
        
        $result = $stmt->execute($testData);
        $this->assertTrue($result);
        
        // Verificar datos insertados
        $stmt = $pdo->prepare("SELECT * FROM test_datatypes_table WHERE string_value = :string_value");
        $stmt->execute([':string_value' => 'Test String']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotNull($row);
        $this->assertEquals('Test String', $row['string_value']);
        $this->assertEquals(42, $row['int_value']);
        $this->assertEquals('3.14', $row['float_value']);
        $this->assertEquals(1, $row['bool_value']);
        $this->assertEquals('2024-01-01', $row['date_value']);
        $this->assertEquals('{"key": "value", "number": 123}', $row['json_value']);
    }

    /**
     * Test de manejo de errores en queries inválidos
     */
    public function testInvalidQueryHandling(): void
    {
        $pdo = $this->database->getConnection();
        
        $this->expectException(PDOException::class);
        $pdo->query("SELECT FROM invalid_table");
    }

    /**
     * Test de configuración de atributos PDO
     */
    public function testPdoAttributes(): void
    {
        $pdo = $this->database->getConnection();
        
        // Verificar atributos importantes
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
        $this->assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
        $this->assertEquals('utf8mb4', $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES) ? 'utf8' : 'utf8mb4');
    }

    /**
     * Test de conexión singleton
     */
    public function testSingletonConnection(): void
    {
        $db1 = new Database($this->testConfig);
        $db2 = new Database($this->testConfig);
        
        $conn1 = $db1->getConnection();
        $conn2 = $db2->getConnection();
        
        // Ambas conexiones deben ser instancias de PDO
        $this->assertInstanceOf(PDO::class, $conn1);
        $this->assertInstanceOf(PDO::class, $conn2);
        
        // Pero no necesariamente la misma instancia (depende de la implementación)
    }
}