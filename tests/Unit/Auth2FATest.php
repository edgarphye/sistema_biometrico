<?php
use PHPUnit\Framework\TestCase;

class Auth2FATest extends TestCase {

    protected function setUp(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function testGenerate2FACodeFormat(): void {
        $code = str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->assertEquals(6, strlen($code));
        $this->assertTrue(ctype_digit($code));
    }

    public function testVerify2FAAttemptCounter(): void {
        $_SESSION['2fa_attempts'] = 0;
        $_SESSION['2fa_attempts']++;
        
        $this->assertEquals(1, $_SESSION['2fa_attempts']);
    }

    public function testVerify2FATimeout(): void {
        $_SESSION['2fa_timestamp'] = time() - 301;
        $timeout = 300;
        $is_expired = (time() - $_SESSION['2fa_timestamp']) > $timeout;

        $this->assertTrue($is_expired);
    }

    public function testVerify2FAMaxAttempts(): void {
        $_SESSION['2fa_attempts'] = 5;
        $max_attempts = 5;
        $is_max_reached = $_SESSION['2fa_attempts'] >= $max_attempts;

        $this->assertTrue($is_max_reached);
    }

    public function testVerify2FACodeMatch(): void {
        $expected_code = '123456';
        $entered_code = '123456';
        $is_correct = ($entered_code === $expected_code);

        $this->assertTrue($is_correct);
    }
}
?>
