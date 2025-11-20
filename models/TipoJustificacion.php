<?php
require_once 'Database.php';

class TipoJustificacion {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Crear un nuevo tipo de justificación
     */
    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO tipos_justificacion (nombre, descripcion, requiere_aprobacion)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['requiere_aprobacion'] ? 1 : 0
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
     * Obtener tipo de justificación por ID
     */
    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM tipos_justificacion WHERE id = ? AND activo = 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Actualizar tipo de justificación
     */
    public function update($id, $data) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE tipos_justificacion SET
                nombre = ?,
                descripcion = ?,
                requiere_aprobacion = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['requiere_aprobacion'] ? 1 : 0,
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
