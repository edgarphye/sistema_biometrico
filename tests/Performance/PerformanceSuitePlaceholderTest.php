<?php
use PHPUnit\Framework\TestCase;

/**
 * @group performance
 */
class PerformanceSuitePlaceholderTest extends TestCase {
    public function testSuiteExists() {
        $this->assertTrue(true, 'Performance test suite initialized');
    }
}
