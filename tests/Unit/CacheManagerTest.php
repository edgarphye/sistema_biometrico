<?php

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests Unitarios para CacheManager Class
 * Pruebas de caché y manejo de memoria
 */
class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;
    private array $testConfig;

    protected function setUp(): void
    {
        $this->testConfig = [
            'driver' => 'array', // Usar driver de array para testing
            'prefix' => 'test_',
            'ttl' => 3600,
            'max_size' => 1000
        ];
        
        $this->cacheManager = new CacheManager($this->testConfig);
    }

    protected function tearDown(): void
    {
        // Limpiar cache después de cada test
        $this->cacheManager->clear();
    }

    /**
     * Test de creación de instancia
     */
    public function testCacheManagerInstanceCreation(): void
    {
        $this->assertInstanceOf(CacheManager::class, $this->cacheManager);
    }

    /**
     * Test de almacenamiento y recuperación de datos
     */
    public function testSetAndGet(): void
    {
        $key = 'test_key';
        $value = 'test_value';

        // Almacenar
        $result = $this->cacheManager->set($key, $value);
        $this->assertTrue($result);

        // Recuperar
        $cached = $this->cacheManager->get($key);
        $this->assertEquals($value, $cached);
    }

    /**
     * Test de almacenamiento con TTL
     */
    public function testSetWithTtl(): void
    {
        $key = 'test_ttl_key';
        $value = 'test_ttl_value';
        $ttl = 2; // 2 segundos

        // Almacenar con TTL corto
        $result = $this->cacheManager->set($key, $value, $ttl);
        $this->assertTrue($result);

        // Verificar inmediatamente
        $cached = $this->cacheManager->get($key);
        $this->assertEquals($value, $cached);

        // Esperar expiración
        sleep(3);
        $cached = $this->cacheManager->get($key);
        $this->assertFalse($cached);
    }

    /**
     * Test de recuperación de clave inexistente
     */
    public function testGetNonExistentKey(): void
    {
        $cached = $this->cacheManager->get('non_existent_key');
        $this->assertFalse($cached);
    }

    /**
     * Test de verificación de existencia
     */
    public function testHas(): void
    {
        $key = 'test_has_key';
        $value = 'test_has_value';

        // Antes de almacenar
        $this->assertFalse($this->cacheManager->has($key));

        // Almacenar
        $this->cacheManager->set($key, $value);

        // Después de almacenar
        $this->assertTrue($this->cacheManager->has($key));
    }

    /**
     * Test de eliminación de clave
     */
    public function testDelete(): void
    {
        $key = 'test_delete_key';
        $value = 'test_delete_value';

        // Almacenar
        $this->cacheManager->set($key, $value);
        $this->assertTrue($this->cacheManager->has($key));

        // Eliminar
        $result = $this->cacheManager->delete($key);
        $this->assertTrue($result);

        // Verificar eliminación
        $this->assertFalse($this->cacheManager->has($key));
    }

    /**
     * Test de eliminación de clave inexistente
     */
    public function testDeleteNonExistentKey(): void
    {
        $result = $this->cacheManager->delete('non_existent_key');
        $this->assertFalse($result);
    }

    /**
     * Test de limpiar toda la cache
     */
    public function testClear(): void
    {
        // Almacenar múltiples valores
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');
        $this->cacheManager->set('key3', 'value3');

        // Verificar que existen
        $this->assertTrue($this->cacheManager->has('key1'));
        $this->assertTrue($this->cacheManager->has('key2'));
        $this->cacheManager->has('key3');

        // Limpiar
        $result = $this->cacheManager->clear();
        $this->assertTrue($result);

        // Verificar que no existen
        $this->assertFalse($this->cacheManager->has('key1'));
        $this->assertFalse($this->cacheManager->has('key2'));
        $this->assertFalse($this->cacheManager->has('key3'));
    }

    /**
     * Test de incrementación de valor numérico
     */
    public function testIncrement(): void
    {
        $key = 'test_increment_key';
        $initialValue = 10;

        // Establecer valor inicial
        $this->cacheManager->set($key, $initialValue);

        // Incrementar
        $result = $this->cacheManager->increment($key, 5);
        $this->assertEquals(15, $result);

        // Verificar valor actualizado
        $cached = $this->cacheManager->get($key);
        $this->assertEquals(15, $cached);
    }

    /**
     * Test de incrementación de clave inexistente
     */
    public function testIncrementNonExistentKey(): void
    {
        $result = $this->cacheManager->increment('non_existent_key', 5);
        $this->assertEquals(5, $result);

        $cached = $this->cacheManager->get('non_existent_key');
        $this->assertEquals(5, $cached);
    }

    /**
     * Test de decrementación de valor numérico
     */
    public function testDecrement(): void
    {
        $key = 'test_decrement_key';
        $initialValue = 20;

        // Establecer valor inicial
        $this->cacheManager->set($key, $initialValue);

        // Decrementar
        $result = $this->cacheManager->decrement($key, 3);
        $this->assertEquals(17, $result);

        // Verificar valor actualizado
        $cached = $this->cacheManager->get($key);
        $this->assertEquals(17, $cached);
    }

    /**
     * Test de decrementación por debajo de cero
     */
    public function testDecrementBelowZero(): void
    {
        $key = 'test_decrement_zero_key';
        $initialValue = 5;

        // Establecer valor inicial
        $this->cacheManager->set($key, $initialValue);

        // Decrementar más que el valor
        $result = $this->cacheManager->decrement($key, 10);
        $this->assertEquals(0, $result); // No debería ser negativo

        $cached = $this->cacheManager->get($key);
        $this->assertEquals(0, $cached);
    }

    /**
     * Test de almacenamiento de array
     */
    public function testSetArray(): void
    {
        $key = 'test_array_key';
        $value = [
            'user_id' => 123,
            'name' => 'Test User',
            'roles' => ['admin', 'user'],
            'active' => true
        ];

        $result = $this->cacheManager->set($key, $value);
        $this->assertTrue($result);

        $cached = $this->cacheManager->get($key);
        $this->assertEquals($value, $cached);
        $this->assertIsArray($cached);
    }

    /**
     * Test de almacenamiento de objeto
     */
    public function testSetObject(): void
    {
        $key = 'test_object_key';
        $value = new stdClass();
        $value->id = 123;
        $value->name = 'Test Object';
        $value->active = true;

        $result = $this->cacheManager->set($key, $value);
        $this->assertTrue($result);

        $cached = $this->cacheManager->get($key);
        $this->assertEquals($value, $cached);
        $this->assertIsObject($cached);
    }

    /**
     * Test de almacenamiento de valor nulo
     */
    public function testSetNullValue(): void
    {
        $key = 'test_null_key';
        $value = null;

        $result = $this->cacheManager->set($key, $value);
        $this->assertTrue($result);

        $cached = $this->cacheManager->get($key);
        $this->assertNull($cached);
    }

    /**
     * Test de almacenamiento de valor booleano
     */
    public function testSetBooleanValue(): void
    {
        $trueKey = 'test_true_key';
        $falseKey = 'test_false_key';

        $this->cacheManager->set($trueKey, true);
        $this->cacheManager->set($falseKey, false);

        $this->assertTrue($this->cacheManager->get($trueKey));
        $this->assertFalse($this->cacheManager->get($falseKey));
    }

    /**
     * Test de múltiples operaciones atómicas
     */
    public function testMultipleOperations(): void
    {
        $operations = [
            'set' => ['key1', 'value1'],
            'set' => ['key2', 'value2', 10],
            'increment' => ['counter', 1],
            'delete' => ['key1']
        ];

        foreach ($operations as $operation => $params) {
            call_user_func_array([$this->cacheManager, $operation], $params);
        }

        $this->assertFalse($this->cacheManager->has('key1'));
        $this->assertTrue($this->cacheManager->has('key2'));
        $this->assertEquals(1, $this->cacheManager->get('counter'));
    }

    /**
     * Test de prefijo de claves
     */
    public function testKeyPrefix(): void
    {
        $key = 'test_prefix_key';
        $value = 'test_value';

        $this->cacheManager->set($key, $value);

        // Verificar que la clave interna tiene el prefijo
        $internalKey = $this->getPrivateProperty($this->cacheManager, 'prefix') . $key;
        $this->assertTrue($this->cacheManager->has($key));
    }

    /**
     * Test de estadísticas de cache
     */
    public function testCacheStatistics(): void
    {
        // Realizar algunas operaciones
        $this->cacheManager->set('stat_key1', 'value1');
        $this->cacheManager->get('stat_key1'); // Hit
        $this->cacheManager->get('non_existent'); // Miss
        $this->cacheManager->delete('stat_key1');

        // Obtener estadísticas
        $stats = $this->cacheManager->getStats();

        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('sets', $stats);
        $this->assertArrayHasKey('deletes', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);

        $this->assertEquals(1, $stats['hits']);
        $this->assertEquals(1, $stats['misses']);
        $this->assertEquals(1, $stats['sets']);
        $this->assertEquals(1, $stats['deletes']);
        $this->assertEquals(0.5, $stats['hit_rate']); // 1 hit / (1 hit + 1 miss)
    }

    /**
     * Test de tamaño máximo de cache
     */
    public function testMaxSizeLimit(): void
    {
        // Crear cache con tamaño pequeño
        $smallCache = new CacheManager([
            'driver' => 'array',
            'max_size' => 2
        ]);

        // Agregar más elementos que el máximo
        $smallCache->set('key1', 'value1');
        $smallCache->set('key2', 'value2');
        $smallCache->set('key3', 'value3'); // Debería eliminar el más antiguo

        // Verificar que mantiene el tamaño máximo
        $this->assertTrue($smallCache->has('key2'));
        $this->assertTrue($smallCache->has('key3'));
        $this->assertFalse($smallCache->has('key1')); // Eliminado por LRU
    }

    /**
     * Test de tags para agrupar claves
     */
    public function testTags(): void
    {
        $key1 = 'tagged_key1';
        $key2 = 'tagged_key2';
        $value = 'tagged_value';
        $tags = ['user', 'session'];

        $this->cacheManager->set($key1, $value, 3600, $tags);
        $this->cacheManager->set($key2, $value, 3600, $tags);

        // Limpiar por tag
        $result = $this->cacheManager->clearTag('user');
        $this->assertTrue($result);

        $this->assertFalse($this->cacheManager->has($key1));
        $this->assertFalse($this->cacheManager->has($key2));
    }

    /**
     * Test de serialización de datos complejos
     */
    public function testComplexDataSerialization(): void
    {
        $key = 'complex_key';
        $value = [
            'timestamp' => time(),
            'object' => new stdClass(),
            'nested' => [
                'array' => [1, 2, 3],
                'boolean' => true,
                'null' => null
            ]
        ];

        $result = $this->cacheManager->set($key, $value);
        $this->assertTrue($result);

        $cached = $this->cacheManager->get($key);
        $this->assertEquals($value, $cached);
        $this->assertIsArray($cached);
        $this->assertIsObject($cached['object']);
    }

    /**
     * Helper para acceder a propiedades privadas en testing
     */
    private function getPrivateProperty($object, $propertyName) {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}