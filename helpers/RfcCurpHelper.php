<?php

/**
 * Helper para generación de RFC y CURP según normas oficiales mexicanas
 * RFC: Reglas de la Secretaría de Hacienda y Crédito Público (SHCP)
 * CURP: Reglas del Registro Nacional de Población (RENAPO)
 *
 * Este helper implementa las reglas oficiales para generar códigos de identificación
 * fiscal y personal conforme a la legislación mexicana.
 */

class RfcCurpHelper {
    /**
     * Tabla de vocales para RFC
     */
    private static $vocales = ['A', 'E', 'I', 'O', 'U'];

    /**
     * Tabla de consonantes para RFC
     */
    private static $consonantes = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z'];

    /**
     * Tabla de estados para CURP
     */
    private static $estados = [
        'AS' => 'AGUASCALIENTES',
        'BC' => 'BAJA CALIFORNIA',
        'BS' => 'BAJA CALIFORNIA SUR',
        'CC' => 'CAMPECHE',
        'CL' => 'COAHUILA',
        'CM' => 'COLIMA',
        'CS' => 'CHIAPAS',
        'CH' => 'CHIHUAHUA',
        'DF' => 'DISTRITO FEDERAL',
        'DG' => 'DURANGO',
        'GT' => 'GUANAJUATO',
        'GR' => 'GUERRERO',
        'HG' => 'HIDALGO',
        'JC' => 'JALISCO',
        'MC' => 'MEXICO',
        'MN' => 'MICHOACAN',
        'MS' => 'MORELOS',
        'NT' => 'NAYARIT',
        'NL' => 'NUEVO LEON',
        'OC' => 'OAXACA',
        'PL' => 'PUEBLA',
        'QT' => 'QUERETARO',
        'QR' => 'QUINTANA ROO',
        'SP' => 'SAN LUIS POTOSI',
        'SL' => 'SINALOA',
        'SR' => 'SONORA',
        'TC' => 'TABASCO',
        'TS' => 'TAMAULIPAS',
        'TL' => 'TLAXCALA',
        'VZ' => 'VERACRUZ',
        'YN' => 'YUCATAN',
        'ZS' => 'ZACATECAS',
        'NE' => 'NACIDO EN EL EXTRANJERO'
    ];

    /**
     * Genera RFC completo según normas SHCP
     */
    public static function generarRFC($nombre, $apellidoPaterno, $apellidoMaterno, $fechaNacimiento) {
        // Limpiar y preparar datos
        $nombre = self::limpiarTexto($nombre);
        $apellidoPaterno = self::limpiarTexto($apellidoPaterno);
        $apellidoMaterno = self::limpiarTexto($apellidoMaterno);

        // Extraer componentes de fecha
        $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if (!$fecha) {
            throw new Exception('Fecha de nacimiento inválida');
        }
        $anio = $fecha->format('y'); // Últimos 2 dígitos
        $mes = $fecha->format('m');
        $dia = $fecha->format('d');

        // Generar las 4 letras del nombre clave del RFC
        $nombreClave = self::generarNombreClaveRFC($apellidoPaterno, $apellidoMaterno, $nombre);

        // RFC sin homoclave (10 caracteres): 4 letras + 6 dígitos fecha
        $rfcSinHomoclave = $nombreClave . $anio . $mes . $dia;
        
        // Generar homoclave de 2 caracteres
        $homoclave = self::calcularHomoclave($apellidoPaterno, $apellidoMaterno, $nombre);
        
        // Calcular dígito verificador (RFC de 12 caracteres)
        $digitoVerificador = self::calcularDigitoVerificador($rfcSinHomoclave . $homoclave);

        // RFC completo (13 caracteres)
        $rfc = $rfcSinHomoclave . $homoclave . $digitoVerificador;

        return $rfc;
    }
    
    /**
     * Genera las 4 letras del nombre clave del RFC
     */
    private static function generarNombreClaveRFC($apellidoPaterno, $apellidoMaterno, $nombre) {
        // Primera letra del apellido paterno
        $primeraLetraPaterno = strtoupper(substr($apellidoPaterno, 0, 1));
        
        // Primera vocal interna del apellido paterno
        $primeraVocalPaterno = self::primeraVocalInterna($apellidoPaterno);
        
        // Primera letra del apellido materno (si existe)
        $primeraLetraMaterno = strtoupper(substr($apellidoMaterno, 0, 1));
        if (empty($primeraLetraMaterno) || $primeraLetraMaterno === '') {
            $primeraLetraMaterno = 'X';
        }
        
        // Primera letra del nombre
        $primeraLetraNombre = strtoupper(substr($nombre, 0, 1));
        
        // Para nombres compuestos, usar primera letra de primer nombre
        if (strpos($nombre, ' ') !== false) {
            $partesNombre = explode(' ', $nombre);
            $nombresProhibidos = ['MARIA', 'MA.', 'MA', 'JOSE', 'J.', 'J'];
            if (in_array(strtoupper($partesNombre[0]), $nombresProhibidos) && count($partesNombre) > 1) {
                $primeraLetraNombre = strtoupper(substr($partesNombre[1], 0, 1));
            }
        }

        return $primeraLetraPaterno . $primeraVocalPaterno . $primeraLetraMaterno . $primeraLetraNombre;
    }
    
    /**
     * Calcula la homoclave de 2 caracteres usando el algoritmo oficial del SAT
     */
    private static function calcularHomoclave($apellidoPaterno, $apellidoMaterno, $nombre) {
        // Tabla de equivalencia para homoclave (según VBA)
        // 1-9, A-I = 10-18, J-R = 19-27, S-Z = 28-36, Ñ = 40, & = 10, espacio = 00
        $nombreCompleto = strtoupper(trim($nombre) . ' ' . trim($apellidoPaterno) . ' ' . trim($apellidoMaterno));
        
        $cadenaNums = '';
        $len = strlen($nombreCompleto);
        
        for ($i = 0; $i < $len; $i++) {
            $caracter = $nombreCompleto[$i];
            
            if ($caracter === ' ') {
                $cadenaNums .= '00';
            } elseif ($caracter === '&') {
                $cadenaNums .= '10';
            } elseif ($caracter === 'Ñ') {
                $cadenaNums .= '40';
            } elseif (ctype_alpha($caracter)) {
                $asc = ord($caracter);
                if ($asc >= 65 && $asc <= 73) { // A-I
                    $cadenaNums .= ($asc - 54);
                } elseif ($asc >= 74 && $asc <= 82) { // J-R
                    $cadenaNums .= ($asc - 53);
                } elseif ($asc >= 83 && $asc <= 90) { // S-Z
                    $cadenaNums .= ($asc - 51);
                }
            } elseif (ctype_digit($caracter)) {
                $cadenaNums .= $caracter;
            }
        }
        
        // Agregar 0 al inicio
        $cadenaNums = '0' . $cadenaNums;
        
        // Calcular suma: numero1 (2 dígitos) * numero2 (1 dígito)
        $suma = 0;
        $lenNums = strlen($cadenaNums);
        for ($i = 0; $i < $lenNums - 1; $i++) {
            $numero1 = intval(substr($cadenaNums, $i, 2));
            $numero2 = intval(substr($cadenaNums, $i + 1, 1));
            $suma += $numero1 * $numero2;
        }
        
        // Calcular cociente y residuo
        $ultimos3 = $suma % 1000;
        $cociente = intval($ultimos3 / 34);
        $residuo = $ultimos3 % 34;
        
        // Equivalencia sin 0, sin Ñ
        $equivalencia = '123456789ABCDEFGHIJKLMNPQRSTUVWXYZ';
        
        $c1 = isset($equivalencia[$cociente]) ? $equivalencia[$cociente] : 'X';
        $c2 = isset($equivalencia[$residuo]) ? $equivalencia[$residuo] : 'X';
        
        return $c1 . $c2;
    }
    
    /**
     * Calcula el dígito verificador del RFC
     */
    private static function calcularDigitoVerificador($rfc12) {
        // Tabla para dígito verificador
        // 0-9: 00-09, A-N: 10-23, O-Z: 24-36, Ñ: 38, &: 24, espacio: 37
        $cadenaNums = '';
        $len = strlen($rfc12);
        
        for ($i = 0; $i < $len; $i++) {
            $caracter = $rfc12[$i];
            
            if ($caracter === ' ') {
                $cadenaNums .= '37';
            } elseif ($caracter === '&') {
                $cadenaNums .= '24';
            } elseif ($caracter === 'Ñ') {
                $cadenaNums .= '38';
            } elseif (ctype_alpha($caracter)) {
                $asc = ord($caracter);
                if ($asc >= 65 && $asc <= 78) { // A-N
                    $cadenaNums .= ($asc - 55);
                } elseif ($asc >= 79 && $asc <= 90) { // O-Z
                    $cadenaNums .= ($asc - 54);
                }
            } elseif (ctype_digit($caracter)) {
                $cadenaNums .= str_pad($caracter, 2, '0', STR_PAD_LEFT);
            }
        }
        
        // Calcular suma con factores 13, 12, 11... (posiciones 1,3,5... en VBA = 0,2,4... en PHP)
        $suma = 0;
        $cont = 0;
        $lenNums = strlen($cadenaNums);
        for ($j = 0; $j < 12 && ($j * 2) < $lenNums; $j++) {
            $numero = intval(substr($cadenaNums, $j * 2, 2));
            $suma += $numero * (13 - $cont);
            $cont++;
        }
        
        $residuo = $suma % 11;
        
        if ($residuo === 0) {
            return '0';
        } elseif ($residuo === 10) {
            return 'A';
        } else {
            return (string)(11 - $residuo);
        }
    }

    /**
     * Genera homoclave del RFC
     */
    private static function generarHomoclaveRFC($apellidoPaterno, $apellidoMaterno, $nombre) {
        // Regla 1: Primera letra del apellido paterno + primera vocal del apellido paterno
        $primeraLetraPaterno = strtoupper(substr($apellidoPaterno, 0, 1));
        $primeraVocalPaterno = self::primeraVocalInterna($apellidoPaterno);

        // Regla 2: Primera letra del apellido materno
        $primeraLetraMaterno = strtoupper(substr($apellidoMaterno, 0, 1));

        // Regla 3: Primera letra del nombre
        $primeraLetraNombre = strtoupper(substr($nombre, 0, 1));

        // Para nombres compuestos, usar primera letra de primer nombre
        if (strpos($nombre, ' ') !== false) {
            $partesNombre = explode(' ', $nombre);
            // Si primer nombre es María, José, etc., usar segundo nombre
            $nombresProhibidos = ['MARIA', 'MA.', 'MA', 'JOSE', 'J.', 'J'];
            if (in_array(strtoupper($partesNombre[0]), $nombresProhibidos) && count($partesNombre) > 1) {
                $primeraLetraNombre = strtoupper(substr($partesNombre[1], 0, 1));
            }
        }

        $homoclave = $primeraLetraPaterno . $primeraVocalPaterno . $primeraLetraMaterno . $primeraLetraNombre;

        return $homoclave;
    }

    /**
     * Genera homoclave completa (3 caracteres) para RFC
     */
    private static function generarHomoclaveCompletaRFC($apellidoPaterno, $apellidoMaterno, $nombre, $rfcBase) {
        // Tabla de valores para cada carácter según SAT
        $tabla = [
            '0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
            'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,'I'=>18,'J'=>19,'K'=>20,
            'L'=>21,'M'=>22,'N'=>23,'Ñ'=>24,'O'=>25,'P'=>26,'Q'=>27,'R'=>28,'S'=>29,'T'=>30,'U'=>31,
            'V'=>32,'W'=>33,'X'=>34,'Y'=>35,'Z'=>36
        ];
        
        // Caracteres válidos para homoclave
        $caracteres36 = '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';
        
        // Unir nombre completo en mayúsculas y sin acentos
        $cadena = strtoupper($apellidoPaterno . ' ' . $apellidoMaterno . ' ' . $nombre);
        $cadena = preg_replace('/[^A-ZÑ0-9 ]/u', '', $cadena);
        $cadena = str_replace(' ', '', $cadena);
        
        // Convertir a valores numéricos
        $valores = '';
        $chars = str_split($cadena);
        foreach ($chars as $char) {
            $valor = $tabla[$char] ?? 0;
            $valores .= str_pad($valor, 2, '0', STR_PAD_LEFT);
        }
        
        // Calcular suma ponderada para homoclave
        $suma = 0;
        $lenValores = strlen($valores);
        for ($i = 0; $i < $lenValores - 1; $i++) {
            $num1 = intval(substr($valores, $i * 2, 2));
            $num2 = intval(substr($valores, ($i + 1) * 2, 2));
            $suma += $num1 * $num2;
        }
        
        // Obtener los dos primeros caracteres de la homoclave
        $residuo = $suma % 1000;
        $digito1 = intval($residuo / 36);
        $digito2 = $residuo % 36;
        
        $homoclave = $caracteres36[$digito1] . $caracteres36[$digito2];
        
        // Calcular dígito verificador usando el RFC sin el DV
        $rfcParcial = $rfcBase . $homoclave;
        $sumaDV = 0;
        $factor = 13;
        $charsRFC = str_split($rfcParcial);
        
        foreach ($charsRFC as $char) {
            $valor = $tabla[$char] ?? 0;
            $sumaDV += $valor * $factor;
            $factor--;
        }
        
        $dv = 11 - ($sumaDV % 11);
        if ($dv < 0) {
            $dv += 11;
        }
        
        if ($dv == 10) {
            $dv = 'A';
        } elseif ($dv == 11 || $dv == 0) {
            $dv = '0';
        }
        
        return $homoclave . $dv;
    }


    /**
     * Genera caracteres de homonimia para RFC
     */
    /*private static function generarHomonimiaRFC($apellidoPaterno, $apellidoMaterno, $nombre) {
        // Tabla de valores para homonimia
        $valores = [
            'A' => 11, 'B' => 12, 'C' => 13, 'D' => 14, 'E' => 15, 'F' => 16,
            'G' => 17, 'H' => 18, 'I' => 19, 'J' => 21, 'K' => 22, 'L' => 23,
            'M' => 24, 'N' => 25, 'Ñ' => 26, 'O' => 27, 'P' => 28, 'Q' => 29,
            'R' => 30, 'S' => 31, 'T' => 32, 'U' => 33, 'V' => 34, 'W' => 35,
            'X' => 36, 'Y' => 37, 'Z' => 38
        ];

        // Calcular suma de valores
        $suma = 0;
        $textoCompleto = $apellidoPaterno . $apellidoMaterno . $nombre;
        
        for ($i = 0; $i < strlen($textoCompleto); $i++) {
            $letra = strtoupper($textoCompleto[$i]);
            if (isset($valores[$letra])) {
                $suma += $valores[$letra];
            }
        }

        // Aplicar fórmula para homonimia
        $resultado = ($suma * 10) % 100;
        
        // Convertir a 2 dígitos con leading zero si es necesario
        return str_pad($resultado, 2, '0', STR_PAD_LEFT);
    }*/

    /**
     * Genera dígito de verificación para RFC
     */
    private static function generarDigitoVerificacionRFC($rfcBase) {
        $diccionario = '0123456789ABCDEFGHIJKLMNÑOPQRSTUVWXYZ';
        $suma = 0;

        for ($i = 0; $i < strlen($rfcBase); $i++) {
            $valor = strpos($diccionario, $rfcBase[$i]);
            if ($valor !== false) {
                $suma += $valor * (18 - $i);
            }
        }

        $residuo = $suma % 11;
        
        if ($residuo < 2) {
            return (string)$residuo;
        } else {
            return chr(65 + (11 - $residuo)); // A, B, C, etc.
        }
    }

    /**
     * Encuentra primera vocal interna en una palabra
     */
    private static function primeraVocalInterna($palabra) {
        $palabra = strtoupper($palabra);
        for ($i = 1; $i < strlen($palabra); $i++) {
            if (in_array($palabra[$i], self::$vocales)) {
                return $palabra[$i];
            }
        }
        return 'X'; // Fallback
    }

    /**
     * Palabras altisonantes o inconvenientes a evitar en la homoclave del CURP.
     * Si la homoclave generada es una de estas, la segunda letra se reemplaza por 'X'.
     */
    private static $palabrasInconvenientes = [
        'BUEI', 'CACA', 'CAGA', 'CAKA', 'COGE', 'COJA', 'COJE', 'COJI', 'COJO', 'CULO',
        'FETO', 'GUEY', 'JOTO', 'KACA', 'KAGA', 'KOGE', 'KOJA', 'KULO', 'MAME', 'MEAR',
        'MEAS', 'MEON', 'MION', 'MOCO', 'MULA', 'PEDA', 'PEDO', 'PENA', 'PENE', 'PITO',
        'POPO', 'PUTA', 'PUTO', 'QULO', 'RATA', 'RUIN'
    ];

    /**
     * Genera CURP completo según normas del Registro Nacional de Población (RENAPO)
     *
     * @param string $nombre Nombre del individuo
     * @param string $apellidoPaterno Apellido paterno
     * @param string $apellidoMaterno Apellido materno
     * @param string $fechaNacimiento Fecha en formato YYYY-MM-DD
     * @param string $sexo Sexo (H/M)
     * @param string $estadoNacimiento Estado de nacimiento (código de 2 letras)
     * @return string CURP generado
     * @throws Exception Si la fecha es inválida
     */
    public static function generarCURP($nombre, $apellidoPaterno, $apellidoMaterno, $fechaNacimiento, $sexo, $estadoNacimiento) {
        // Limpiar y normalizar datos
        $nombre = self::limpiarTexto($nombre);
        $apellidoPaterno = self::limpiarTexto($apellidoPaterno);
        $apellidoMaterno = self::limpiarTexto($apellidoMaterno);
        $sexo = strtoupper($sexo);

        // Extraer componentes de fecha
        $fecha = DateTime::createFromFormat('Y-m-d', $fechaNacimiento);
        if (!$fecha) {
            throw new Exception('Fecha de nacimiento inválida');
        }
        $anio = $fecha->format('y');
        $mes = $fecha->format('m');
        $dia = $fecha->format('d');
        $fullAnio = (int)$fecha->format('Y');

        // 1. Generar primera parte (homoclave)
        $homoclave = self::generarHomoclaveCURP($apellidoPaterno, $apellidoMaterno, $nombre);

        // 2. Código de estado
        $codigoEstado = self::obtenerCodigoEstado($estadoNacimiento);

        // 3. Sexo
        $codigoSexo = ($sexo === 'H' || $sexo === 'M') ? $sexo : 'H';

        // 4. Consonantes internas
        $consonantePaterno = self::primeraConsonanteInterna($apellidoPaterno);
        $consonanteMaterno = self::primeraConsonanteInterna($apellidoMaterno);
        $consonanteNombre = self::primeraConsonanteInterna($nombre);

        // 5. Dígito diferenciador de siglo (homoclave)
        $diferenciadorNacimiento = ($fullAnio < 2000) ? '0' : 'A';

        // 6. Construir CURP base de 17 dígitos
        $curpSinDigito = $homoclave . $anio . $mes . $dia . $codigoSexo . $codigoEstado . $consonantePaterno . $consonanteMaterno . $consonanteNombre . $diferenciadorNacimiento;

        // 7. Calcular dígito verificador final
        $digitoVerificador = self::calcularDigitoVerificadorCURP($curpSinDigito);

        // 8. Ensamblar CURP final de 18 dígitos
        $curp = $curpSinDigito . $digitoVerificador;

        return $curp;
    }

    /**
     * Genera homoclave del CURP (primeros 4 caracteres)
     */
    private static function generarHomoclaveCURP($apellidoPaterno, $apellidoMaterno, $nombre) {
        $primeraLetraPaterno = strtoupper(substr($apellidoPaterno, 0, 1));
        $primeraVocalPaterno = self::primeraVocalInterna($apellidoPaterno);
        $primeraLetraMaterno = strtoupper(substr($apellidoMaterno, 0, 1));
        $primeraLetraNombre = strtoupper(substr($nombre, 0, 1));

        // Para nombres compuestos (e.g., "MARIA DEL CARMEN", "JOSE LUIS")
        if (strpos($nombre, ' ') !== false) {
            $partesNombre = explode(' ', $nombre);
            $nombresComunes = ['MARIA', 'MA.', 'MA', 'JOSE', 'J.', 'J'];
            if (in_array(strtoupper($partesNombre[0]), $nombresComunes) && count($partesNombre) > 1) {
                $primeraLetraNombre = strtoupper(substr($partesNombre[1], 0, 1));
            }
        }
        
        $homoclave = $primeraLetraPaterno . $primeraVocalPaterno . $primeraLetraMaterno . $primeraLetraNombre;

        // Regla de palabras inconvenientes
        if (in_array($homoclave, self::$palabrasInconvenientes)) {
            $homoclave[1] = 'X';
        }

        return $homoclave;
    }

    /**
     * Encuentra primera consonante interna (después de la primera letra)
     */
    private static function primeraConsonanteInterna($palabra) {
        $palabra = strtoupper($palabra);
        for ($i = 1; $i < strlen($palabra); $i++) {
            if (in_array($palabra[$i], self::$consonantes)) {
                return $palabra[$i];
            }
        }
        return 'X'; // Valor por defecto si no se encuentra
    }

    /**
     * Obtiene código de estado de 2 letras para CURP
     */
    private static function obtenerCodigoEstado($estado) {
        $estado = strtoupper(trim($estado));

        // Búsqueda por código (e.g., 'JC')
        if (isset(self::$estados[$estado])) {
            return $estado;
        }

        // Búsqueda por nombre (e.g., 'JALISCO')
        $codigo = array_search($estado, self::$estados);
        if ($codigo !== false) {
            return $codigo;
        }

        // Si no se encuentra, se asume 'Nacido en el Extranjero'
        return 'NE';
    }

/**
 * Calcula el dígito verificador final del CURP (posición 18)
 */
private static function calcularDigitoVerificadorCURP(string $curp17): int
{
    $curp17 = strtoupper(trim($curp17));
    if (strlen($curp17) !== 17) {
        throw new InvalidArgumentException(
            "Se requieren exactamente 17 caracteres para calcular el dígito verificador."
        );
    }

    // Tabla oficial de valores RENAPO
    $tabla = [
        '0'=>0,'1'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,
        'A'=>10,'B'=>11,'C'=>12,'D'=>13,'E'=>14,'F'=>15,'G'=>16,'H'=>17,'I'=>18,
        'J'=>19,'K'=>20,'L'=>21,'M'=>22,'N'=>23,'Ñ'=>24,'O'=>25,'P'=>26,'Q'=>27,
        'R'=>28,'S'=>29,'T'=>30,'U'=>31,'V'=>32,'W'=>33,'X'=>34,'Y'=>35,'Z'=>36
    ];

    $suma = 0;

    for ($i = 0; $i < 17; $i++) {
        $caracter = $curp17[$i];

        if (!array_key_exists($caracter, $tabla)) {
            throw new InvalidArgumentException("Carácter inválido en la CURP: {$caracter}");
        }

        $valor = $tabla[$caracter];
        $factor = 19 - ($i + 1); // ponderación decreciente
        $suma += $valor * $factor;
    }

    $residuo = $suma % 10;
    return (10 - $residuo) % 10;
}

    /**
     * Limpia y normaliza texto para RFC/CURP (remueve acentos, caracteres especiales)
     */
    private static function limpiarTexto($texto) {
        // Convertir a mayúsculas
        $texto = strtoupper($texto);
        // Reemplazar acentos y Ñ
        $texto = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'U', 'X'], // Ñ se trata de forma especial en algunas reglas, aquí se reemplaza por X
            $texto
        );
         // Quitar prefijos y sufijos comunes de nombres y apellidos
        $texto = preg_replace('/\b(DE|LA|LAS|LOS|DEL|Y|MC|MAC|VON|VAN)\b/','', $texto);

        // Remover caracteres no alfabéticos (excepto espacios)
        $texto = preg_replace('/[^A-Z\s]/', '', $texto);

        // Remover espacios múltiples y trim
        $texto = preg_replace('/\s+/', ' ', trim($texto));

        return $texto;
    }

    /**
     * Valida formato de RFC
     */
    public static function validarRFC($rfc) {
        // RFC para persona física (13 caracteres) o moral (12 caracteres)
        return preg_match('/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/', strtoupper($rfc));
    }

    /**
     * Valida formato y dígito verificador de un CURP.
     */
    public static function validarCURP($curp) {
        $curp = strtoupper($curp);

        // 1. Validar formato general con RegEx
        if (!preg_match('/^[A-Z]{4}[0-9]{6}[HM][A-Z]{2}[A-Z]{3}[0-9A-Z][0-9]$/', $curp)) {
            return false;
        }

        // 2. Validar dígito verificador
        $curpSinDigito = substr($curp, 0, 17);
        $digitoVerificadorOriginal = substr($curp, 17, 1);
        $digitoVerificadorCalculado = self::calcularDigitoVerificadorCURP($curpSinDigito);

        return $digitoVerificadorOriginal === $digitoVerificadorCalculado;
    }
}
?>
