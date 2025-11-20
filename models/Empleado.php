<?php

use const Dom\STRING_SIZE_ERR;
require_once 'Database.php';
require_once __DIR__ . '/../helpers/Encryption.php';

class Empleado {
    private $db;

    // Constantes para entidades federativas mexicanas
    /*const ENTIDADES_FEDERATIVAS = [
        'AGUASCALIENTES' => 'AS',
        'BAJA CALIFORNIA' => 'BC',
        'BAJA CALIFORNIA SUR' => 'BS',
        'CAMPECHE' => 'CC',
        'COAHUILA' => 'CL',
        'COLIMA' => 'CM',
        'CHIAPAS' => 'CS',
        'CHIHUAHUA' => 'CH',
        'DISTRITO FEDERAL' => 'DF',
        'DURANGO' => 'DG',
        'GUANAJUATO' => 'GT',
        'GUERRERO' => 'GR',
        'HIDALGO' => 'HG',
        'JALISCO' => 'JC',
        'MEXICO' => 'MC',
        'MICHOACAN' => 'MN',
        'MORELOS' => 'MS',
        'NAYARIT' => 'NT',
        'NUEVO LEON' => 'NL',
        'OAXACA' => 'OC',
        'PUEBLA' => 'PL',
        'QUERETARO' => 'QT',
        'QUINTANA ROO' => 'QR',
        'SAN LUIS POTOSI' => 'SP',
        'SINALOA' => 'SL',
        'SONORA' => 'SR',
        'TABASCO' => 'TC',
        'TAMAULIPAS' => 'TS',
        'TLAXCALA' => 'TL',
        'VERACRUZ' => 'VZ',
        'YUCATAN' => 'YN',
        'ZACATECAS' => 'ZS',
        'NACIDO EXTRANJERO' => 'NE'
    ];*/

    public function __construct() {
        $this->db = new Database();
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM empleados WHERE activo = 1");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        // Descifrar huellas si están presentes
        foreach ($rows as &$r) {
            if (!empty($r['huella_dactilar'])) {
                $r['huella_dactilar'] = Encryption::decrypt($r['huella_dactilar']);
            }
        }
        return $rows;
    }

    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM empleados WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['huella_dactilar'])) {
            $row['huella_dactilar'] = Encryption::decrypt($row['huella_dactilar']);
        }
        return $row;
    }

    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO empleados (nombre, apellido, rfc, curp, area, jerarquia, huella_dactilar, foto_cara)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $huella = $data['huella_dactilar'] ?? null;
        if ($huella !== null) {
            $huella = Encryption::encrypt($huella);
        }
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['rfc'],
            $data['curp'],
            $data['area'],
            $data['jerarquia'],
            $huella,
            $data['foto_cara'] ?? null // Ahora es una ruta de archivo
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE empleados SET
                nombre = ?,
                apellido = ?,
                rfc = ?,
                curp = ?,
                area = ?,
                jerarquia = ?,
                huella_dactilar = ?,
                foto_cara = ?
            WHERE id = ?
        ");
        $huella = $data['huella_dactilar'] ?? null;
        if ($huella !== null) {
            $huella = Encryption::encrypt($huella);
        }
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['rfc'],
            $data['curp'],
            $data['area'],
            $data['jerarquia'],
            $huella,
            $data['foto_cara'],
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("UPDATE empleados SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function readCompleteData($id) {
        $empleado = $this->getById($id);
        if (!$empleado) {
            return null;
        }

        // Obtener estadísticas adicionales
        $asistencias = $this->getAsistenciasByEmpleado($id);
        $retardos = $this->getRetardosByEmpleado($id);
        $comisiones = $this->getComisionesByEmpleado($id);
        $ausencias = $this->getAusenciasByEmpleado($id);

        $empleado['estadisticas'] = [
            'total_asistencias' => count($asistencias),
            'retardos_mes' => count(array_filter($retardos, function($r) {
                return date('m-Y', strtotime($r['fecha'])) == date('m-Y');
            })),
            'comisiones_pendientes' => count(array_filter($comisiones, function($c) {
                return !$c['justificada'];
            })),
            'ausencias_mes' => count(array_filter($ausencias, function($a) {
                return date('m-Y', strtotime($a['fecha_inicio'])) == date('m-Y');
            }))
        ];

        $empleado['retardos'] = $retardos;
        $empleado['comisiones'] = $comisiones;
        $empleado['ausencias'] = $ausencias;

        return $empleado;
    }

    private function getAsistenciasByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM asistencias WHERE empleado_id = ? ORDER BY fecha DESC");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    private function getRetardosByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM retardos WHERE empleado_id = ? ORDER BY fecha DESC");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    private function getComisionesByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM comisiones WHERE empleado_id = ? ORDER BY fecha_vencimiento DESC");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    private function getAusenciasByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM ausencias WHERE empleado_id = ? ORDER BY fecha_inicio DESC");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

// --- Catálogo de entidades federativas ---
    private const ENTIDADES_FEDERATIVAS = [
        'AGUASCALIENTES' => 'AS', 'BAJA CALIFORNIA' => 'BC', 'BAJA CALIFORNIA SUR' => 'BS',
        'CAMPECHE' => 'CC', 'COAHUILA' => 'CL', 'COLIMA' => 'CM', 'CHIAPAS' => 'CS',
        'CHIHUAHUA' => 'CH', 'DISTRITO FEDERAL' => 'DF', 'CIUDAD DE MEXICO' => 'DF',
        'DURANGO' => 'DG', 'GUANAJUATO' => 'GT', 'GUERRERO' => 'GR', 'HIDALGO' => 'HG',
        'JALISCO' => 'JC', 'MEXICO' => 'MC', 'MICHOACAN' => 'MN', 'MORELOS' => 'MS',
        'NAYARIT' => 'NT', 'NUEVO LEON' => 'NL', 'OAXACA' => 'OC', 'PUEBLA' => 'PL',
        'QUERETARO' => 'QT', 'QUINTANA ROO' => 'QR', 'SAN LUIS POTOSI' => 'SP',
        'SINALOA' => 'SL', 'SONORA' => 'SR', 'TABASCO' => 'TC', 'TAMAULIPAS' => 'TS',
        'TLAXCALA' => 'TL', 'VERACRUZ' => 'VZ', 'YUCATAN' => 'YN', 'ZACATECAS' => 'ZS',
        'NACIDO EN EL EXTRANJERO' => 'NE'
    ];

    // ============================================================
    // === UTILIDADES DE TEXTO ====================================
    // ============================================================

    private function cleanString(string $string): string
    {
        $string = mb_strtoupper(trim($string), 'UTF-8');
        $string = str_replace(['Á','É','Í','Ó','Ú','Ü'], ['A','E','I','O','U','U'], $string);
        return preg_replace('/[^A-ZÑ\s]/u', '', $string);
    }

    private function getSecondVowel(string $palabra): string
    {
        $palabra = mb_strtoupper($palabra, 'UTF-8');
        $vocales = ['A','E','I','O','U'];
        for ($i = 1, $len = mb_strlen($palabra, 'UTF-8'); $i < $len; $i++) {
            $c = mb_substr($palabra, $i, 1, 'UTF-8');
            if (in_array($c, $vocales)) return $c;
        }
        return 'X';
    }

    private function getFirstInternalConsonant(string $palabra): string
    {
        $palabra = mb_strtoupper($palabra, 'UTF-8');
        $consonantes = ['B','C','D','F','G','H','J','K','L','M','N','Ñ','P','Q','R','S','T','V','W','X','Y','Z'];
        for ($i = 1, $len = mb_strlen($palabra, 'UTF-8'); $i < $len; $i++) {
            $c = mb_substr($palabra, $i, 1, 'UTF-8');
            if (in_array($c, $consonantes)) return $c;
        }
        return 'X';
    }

    // ============================================================
    // === RFC =====================================================
    // ============================================================

    public function generateRFC(string $fechaNacimiento, string $apellidoPaterno, string $apellidoMaterno, string $nombres): string
    {
        $aPat = $this->cleanString($apellidoPaterno);
        $aMat = $this->cleanString($apellidoMaterno);
        $nom  = $this->cleanString($nombres);

        $partesNom = array_values(array_filter(explode(' ', $nom), fn($x)=>$x!==''));
        $primerNombre = $partesNom[0] ?? 'X';
        if (in_array($primerNombre, ['JOSE','MARIA']) && isset($partesNom[1])) {
            $primerNombre = $partesNom[1];
        }

        $rfc = mb_substr($aPat, 0, 1, 'UTF-8')
             . $this->getSecondVowel($aPat)
             . mb_substr($aMat, 0, 1, 'UTF-8')
             . mb_substr($primerNombre, 0, 1, 'UTF-8');

        $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if (!$fecha) throw new Exception('Fecha inválida, use YYYY-MM-DD');
        $rfc .= $fecha->format('ymd');

        return $rfc . $this->calculateRFCHomoclaveSAT($rfc);
    }

    private function calculateRFCHomoclaveSAT(string $rfcBase): string
    {
        $tabla = [
            ' '=>0,'0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
            'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,'I'=>18,'J'=>19,
            'K'=>20,'L'=>21,'M'=>22,'N'=>23,'&'=>24,'O'=>25,'P'=>26,'Q'=>27,'R'=>28,'S'=>29,
            'T'=>30,'U'=>31,'V'=>32,'W'=>33,'X'=>34,'Y'=>35,'Z'=>36
        ];

        $cadena = '0';
        foreach (str_split($rfcBase) as $c) {
            $cadena .= str_pad($tabla[$c] ?? 0, 2, '0', STR_PAD_LEFT);
        }

        $suma = 0;
        for ($i = 0; $i < strlen($cadena) - 1; $i++) {
            $suma += intval(substr($cadena, $i, 2)) * intval(substr($cadena, $i + 1, 2));
        }

        $valor = $suma % 1000;
        $entero = floor($valor / 34);
        $residuo = $valor % 34;
        $dic = '0123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
        $homoclave = $dic[$entero] . $dic[$residuo];

        // dígito verificador
        $rfcTemp = $rfcBase . $homoclave;
        $suma2 = 0; $factor = 13;
        for ($i = 0; $i < strlen($rfcTemp); $i++) {
            $pos = strpos('0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ', $rfcTemp[$i]);
            if ($pos !== false) $suma2 += $pos * $factor--;
        }

        $residuo2 = $suma2 % 11;
        $dv = $residuo2 == 0 ? '0' : ($residuo2 == 10 ? 'A' : (11 - $residuo2));
        return $homoclave . $dv;
    }

    // ============================================================
    // === CURP ====================================================
    // ============================================================

    public function generateCURP(string $fechaNacimiento, string $apellidoPaterno, string $apellidoMaterno, string $nombres, string $sexo, string $estado): string
    {
        $aPat = $this->cleanString($apellidoPaterno);
        $aMat = $this->cleanString($apellidoMaterno);
        $nom  = $this->cleanString($nombres);
        $sexo = mb_strtoupper(trim($sexo), 'UTF-8');
        $estado = mb_strtoupper(trim($estado), 'UTF-8');

        if (!isset(self::ENTIDADES_FEDERATIVAS[$estado])) {
            throw new Exception('Entidad federativa no válida');
        }

        $nomParts = array_values(array_filter(explode(' ', $nom), fn($x)=>$x!==''));
        $primerNombre = $nomParts[0] ?? 'X';
        if (in_array($primerNombre, ['JOSE','MARIA']) && isset($nomParts[1])) {
            $primerNombre = $nomParts[1];
        }

        $curp = mb_substr($aPat, 0, 1)
              . $this->getSecondVowel($aPat)
              . mb_substr($aMat, 0, 1)
              . mb_substr($primerNombre, 0, 1);

        $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if (!$fecha) throw new Exception('Fecha inválida');
        $curp .= $fecha->format('ymd');

        $curp .= $sexo;
        $curp .= self::ENTIDADES_FEDERATIVAS[$estado];
        $curp .= $this->getFirstInternalConsonant($aPat);
        $curp .= $this->getFirstInternalConsonant($aMat);
        $curp .= $this->getFirstInternalConsonant($primerNombre);

        $curp .= $this->calculateCurpHomoclave($curp, $fechaNacimiento);
        return $curp;
    }

    private function calculateCurpHomoclave(string $curp16, string $fechaNacimiento): string
    {
        $vals = [
            '0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
            'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,'I'=>18,'J'=>19,
            'K'=>20,'L'=>21,'M'=>22,'N'=>23,'Ñ'=>24,'O'=>25,'P'=>26,'Q'=>27,'R'=>28,'S'=>29,
            'T'=>30,'U'=>31,'V'=>32,'W'=>33,'X'=>34,'Y'=>35,'Z'=>36
        ];

        $sum = 0;
        for ($i = 0; $i < strlen($curp16); $i++) {
            $sum += ($vals[$curp16[$i]] ?? 0) * ($i + 1);
        }

        $year = intval(date('Y', strtotime($fechaNacimiento)));
        $char17 = $year < 2000 ? (string)($sum % 10) : chr(65 + ($sum % 26));

        $dic = '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';
        $suma2 = 0; $peso = 18;
        for ($i = 0; $i < strlen($curp16); $i++) {
            $pos = strpos($dic, $curp16[$i]) ?: 0;
            $suma2 += $pos * $peso--;
        }

        $pos17 = strpos($dic, $char17) ?: 0;
        $suma2 += $pos17 * 2;
        $res = $suma2 % 10;
        $digito = $res == 0 ? '0' : (string)((10 - $res) % 10);
        return $char17 . $digito;
    }

  
}

?>
