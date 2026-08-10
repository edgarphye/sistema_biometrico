<?php
use PHPUnit\Framework\TestCase;

/**
 * @group security
 */
class SecuritySuitePlaceholderTest extends TestCase {
    public function testSuiteExists() {
        $this->assertTrue(true, 'Security test suite initialized');
    }
}
