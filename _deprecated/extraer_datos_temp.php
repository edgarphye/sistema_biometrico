<?php
/**
 * Método extraerDatosCampos corregido
 */
class DataExtractor {
    private $mapeoCampos = [];

    private function extraerDatosCampos($campos) {
    $datos = [];
    
    // Extraer campos directos según el mapeo
    foreach ($this->mapeoCampos as $campo => $posicion) {
        if ($posicion === null || is_string($posicion)) {
            // Omitir referencias y nulos por ahora
            continue;
        }
        
        if (isset($campos[$posicion])) {
            $valor = trim($campos[$posicion]);
            
            // Conversiones específicas por tipo
            switch ($campo) {
                case 'empleado_id':
                case 'dispositivo_id':
                    $datos[$campo] = is_numeric($valor) ? (int)$valor : null;
                    break;
                case 'accion':
                case 'resultado':
                case 'tipo':
                case 'verificacion':
                    $datos[$campo] = is_numeric($valor) ? (int)$valor : null;
                    break;
                default:
                    $datos[$campo] = $valor;
            }
        } else {
            $datos[$campo] = null;
        }
    }
    
    // Procesar datetime para extraer fecha y hora
    if (isset($datos['datetime'])) {
        $datetime = $datos['datetime'];
        if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})$/', $datetime, $matches)) {
            $datos['fecha'] = $matches[1];
            $datos['hora'] = $matches[2];
        }
    }
    
    // Procesar referencias restantes
    foreach ($this->mapeoCampos as $campo => $posicion) {
        if ($posicion === null) {
            $datos[$campo] = null;
        } elseif (is_string($posicion)) {
            if ($posicion === 'datetime' && isset($datos['datetime'])) {
                // Para fecha y hora, ya las procesamos arriba
                if (!isset($datos['fecha']) || !isset($datos['hora'])) {
                    $datetime = $datos['datetime'];
                    if (preg_match('/^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}:\d{2})$/', $datetime, $matches)) {
                        if (!isset($datos['fecha'])) $datos['fecha'] = $matches[1];
                        if (!isset($datos['hora'])) $datos['hora'] = $matches[2];
                    }
                }
            } elseif (isset($datos[$posicion])) {
                // Referencia a otro campo
                $datos[$campo] = $datos[$posicion];
            } else {
                $datos[$campo] = null;
            }
        }
    }
    
    return $datos;
    }
}
?>