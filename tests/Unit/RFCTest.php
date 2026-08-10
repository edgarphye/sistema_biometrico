<?php
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 */
class RFCTest extends TestCase {
    private $rfcPattern = '/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/';

    public function testRFCFormatPersonaFisica() {
        $rfc = 'JUAP890101XXX';
        $this->assertMatchesRegularExpression($this->rfcPattern, $rfc);
        $this->assertEquals(13, strlen($rfc), 'RFC persona fisica debe tener 13 caracteres');
    }

    public function testRFCFormatPersonaMoral() {
        $rfc = 'EMP890101XXX';
        $this->assertMatchesRegularExpression($this->rfcPattern, $rfc);
        $this->assertEquals(12, strlen($rfc), 'RFC persona moral debe tener 12 caracteres');
    }

    public function testInvalidRFC() {
        $invalidRFCs = [
            '123', 'ABC', 'ABCDEFGHIJKLMN', 'JUAP890101',
            '', 'ABC-1234-XYZ'
        ];

        foreach ($invalidRFCs as $rfc) {
            $this->assertDoesNotMatchRegularExpression($this->rfcPattern, $rfc, "RFC '$rfc' debe ser invalido");
        }
    }

    public function testRFCConHomoclave() {
        $rfc = 'JUAP890101ABC';
        $this->assertMatchesRegularExpression($this->rfcPattern, $rfc);
    }

    public function testRFCDigitosFecha() {
        $rfcValido = 'JUAP890101';
        $this->assertMatchesRegularExpression('/^[A-ZÑ&]{3,4}[0-9]{6}/', $rfcValido);

        $rfcConLetrasEnFecha = 'JUAPAAAAAA';
        $this->assertDoesNotMatchRegularExpression('/^[A-ZÑ&]{3,4}[0-9]{6}/', $rfcConLetrasEnFecha);
    }
}
