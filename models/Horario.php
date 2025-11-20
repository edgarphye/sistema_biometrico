<?php 
require_once 'HorarioLaboral.php'; 
 
/** 
 * Modelo Horario - Compatibilidad hacia atrás con HorarioLaboral 
 * @deprecated Usar HorarioLaboral en su lugar 
 */ 
class Horario extends HorarioLaboral { 
    /** 
     * Obtener horario aplicable para un empleado en una fecha específica 
     * Método de compatibilidad hacia atrás 
     */ 
    public function getHorarioEmpleadoFecha($empleado_id, $fecha) { 
        return $this->getHorarioPorFecha($empleado_id, $fecha); 
    } 
} 
