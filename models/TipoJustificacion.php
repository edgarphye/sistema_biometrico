<?php
require_once 'Database.php';

class TipoJustificacion {
    private $db;
    private $columnasCache = [];

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Crear un nuevo tipo de justificación
     */
    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO tipos_justificacion (nombre, descripcion, requiere_aprobacion, requiere_documento)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['requiere_aprobacion'] ? 1 : 0,
            $data['requiere_documento'] ? 1 : 0
        ]);
    }

    /**
     * Obtener todos los tipos de justificación activos
     */
    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM tipos_justificacion WHERE activo = 1 ORDER BY nombre
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene tipos de justificación filtrados por tipo de incidencia (si existe la columna tipo_incidencia).
     * Si la columna no existe, hace fallback a getAll().
     */
    public function getAllForIncidencia($tipoIncidencia) {
        if (!$this->tieneColumna('tipos_justificacion', 'tipo_incidencia')) {
            return $this->getAll();
        }

        // Mapeo: tipo de incidencia -> todos los tipos de justificación permitidos
        $mapeo = [
            'retardo' => ['retardo', 'retardo_menor', 'retardo_mayor', 'falta'],
            'retardo_menor' => ['retardo', 'retardo_menor', 'retardo_mayor', 'falta'],
            'retardo_mayor' => ['retardo', 'retardo_menor', 'retardo_mayor', 'falta'],
            'tolerancia' => ['retardo', 'retardo_menor', 'retardo_mayor', 'falta'],
            'falta' => ['falta', 'dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_maternos', 'cuidados_paternos', 'cuidados_parentales', 'constancia_tiempo', 'PDSEP-SNTE', 'CLIDDA', 'EYR', 'DE', 'F', 'permiso_fallecimiento'],
            'comision_entrada' => ['comision_entrada', 'dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_maternos', 'cuidados_paternos', 'cuidados_parentales', 'constancia_tiempo', 'PDSEP-SNTE', 'CLIDDA', 'EYR', 'DE', 'F', 'permiso_fallecimiento'],
            'comision_salida' => ['comision_salida', 'dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_maternos', 'cuidados_paternos', 'cuidados_parentales', 'constancia_tiempo', 'PDSEP-SNTE', 'CLIDDA', 'EYR', 'DE', 'F', 'permiso_fallecimiento'],
            'comision_todo_dia' => ['comision_todo_dia', 'dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_maternos', 'cuidados_paternos', 'cuidados_parentales', 'constancia_tiempo', 'PDSEP-SNTE', 'CLIDDA', 'EYR', 'DE', 'F', 'permiso_fallecimiento'],
            'por_definir' => ['comision_entrada', 'comision_salida', 'comision_todo_dia', 'dia_economico', 'licencia_medica', 'vacaciones', 'cuidados_maternos', 'cuidados_paternos', 'cuidados_parentales', 'constancia_tiempo', 'PDSEP-SNTE', 'CLIDDA', 'EYR', 'DE', 'F', 'permiso_fallecimiento'],
            'normal' => [] // Asistencia normal no necesita justificación
        ];
        
        $tiposPermitidos = $mapeo[$tipoIncidencia] ?? ['dia_economico', 'licencia_medica', 'constancia_tiempo', 'vacaciones', 'cuidados_parentales'];
        
        // Si no hay tipos permitidos, retornar array vacío
        if (empty($tiposPermitidos)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($tiposPermitidos), '?'));
        
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM tipos_justificacion
            WHERE activo = 1
              AND tipo_incidencia IN ($placeholders)
            ORDER BY FIELD(tipo_incidencia, " . $placeholders . "), nombre
        ");
        
        $params = array_merge($tiposPermitidos, $tiposPermitidos);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtener tipo de justificación por ID
     */
    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM tipos_justificacion WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    private function tieneColumna($tabla, $columna) {
        $key = $tabla . '.' . $columna;
        if (array_key_exists($key, $this->columnasCache)) {
            return $this->columnasCache[$key];
        }

        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) AS total
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$tabla, $columna]);
        $existe = ((int)($stmt->fetch()['total'] ?? 0)) > 0;
        $this->columnasCache[$key] = $existe;
        return $existe;
    }

    /**
     * Actualizar tipo de justificación
     */
    public function update($id, $data) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE tipos_justificacion SET
                nombre = ?,
                descripcion = ?,
                requiere_aprobacion = ?,
                requiere_documento = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['requiere_aprobacion'] ? 1 : 0,
            $data['requiere_documento'] ? 1 : 0,
            $id
        ]);
    }

    /**
     * Desactivar tipo de justificación
     */
    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE tipos_justificacion SET activo = 0 WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }
}
?>
