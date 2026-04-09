<?php

class ZKTecoFormatDetector {
    const TIPO_EMPLEADO_ID = 'empleado_id';
    const TIPO_DATETIME = 'datetime';
    const TIPO_FECHA = 'fecha';
    const TIPO_HORA = 'hora';
    const TIPO_ACCION = 'accion';
    const TIPO_DISPOSITIVO = 'dispositivo_id';
    const TIPO_VERIFICACION = 'verificacion';
    const TIPO_RESULTADO = 'resultado';
    
    const FORMATO_SIMPLE = 'simple';
    const FORMATO_ESTANDAR = 'estandar';
    const FORMATO_EXTENDIDO = 'extendido';

    public function detectarFormato($filePath) {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [
                'formato' => 'desconocido',
                'confianza' => 0,
                'mapa_tipos' => []
            ];
        }

        // Leer primera línea válida
        $line = false;
        while (($l = fgets($handle)) !== false) {
            if (trim($l) !== '') {
                $line = trim($l);
                break;
            }
        }
        fclose($handle);

        if (!$line) return ['formato' => 'desconocido', 'confianza' => 0, 'mapa_tipos' => []];

        // Analizar estructura:优先检查tabulación,然后espacios
        if (strpos($line, "\t") !== false) {
            $campos = explode("\t", $line);
        } else {
            $campos = preg_split('/\s+/', $line);
        }
        $conteo = count($campos);
        
        $formato = self::FORMATO_SIMPLE;
        $nombreFormato = 'simple';
        $mapa = [];
        
        // Detección básica basada en cantidad de columnas
        if ($conteo >= 5) {
            $formato = self::FORMATO_ESTANDAR;
            $nombreFormato = 'zkteco_estandar';
            // Asumir formato estándar ZK: ID, DateTime, Device/Verify, Status, ...
            $mapa = [
                0 => self::TIPO_EMPLEADO_ID,
                1 => self::TIPO_DATETIME,
                2 => self::TIPO_ACCION,
                3 => self::TIPO_VERIFICACION,
                4 => self::TIPO_RESULTADO
            ];
            if ($conteo > 5) $mapa[5] = self::TIPO_DISPOSITIVO;
        } else {
            // Asumir formato simple: ID, DateTime, (Device)
            $nombreFormato = 'zkteco_simple';
            $mapa = [
                0 => self::TIPO_EMPLEADO_ID,
                1 => self::TIPO_DATETIME
            ];
            if ($conteo > 2) $mapa[2] = self::TIPO_ACCION;
        }

        return [
            'formato' => $formato,
            'nombre' => $nombreFormato,
            'conteo_campos' => $conteo,
            'confianza' => 100,
            'mapa_tipos' => $mapa,
            'campos' => $mapa
        ];
    }

    public function validarFormato($info) {
        if ($info['formato'] === 'desconocido' || empty($info['mapa_tipos'])) {
            return ['valido' => false, 'razon' => 'No se pudo detectar un formato válido'];
        }
        return ['valido' => true];
    }
}