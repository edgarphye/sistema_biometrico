<?php
use PHPUnit\Framework\TestCase;

/**
 * @group database
 */
class DatabaseSuitePlaceholderTest extends TestCase {
    public function testSuiteExists() {
        $this->assertTrue(true, 'Database test suite initialized');
    }
}
