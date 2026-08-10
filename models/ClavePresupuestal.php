<?php
require_once __DIR__ . '/Database.php';

class ClavePresupuestal {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Obtiene las claves presupuestales de un empleado
     * @param int $empleado_id
     * @return array
     */
    public function getByEmpleado($empleado_id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT id, empleado_id, clave, created_at
            FROM claves_presupuestales
            WHERE empleado_id = ?
            ORDER BY id
        ");
        $stmt->execute([$empleado_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene las claves presupuestales de un empleado como array plano de claves
     * @param int $empleado_id
     * @return array
     */
    public function getClavesPorEmpleado($empleado_id) {
        $rows = $this->getByEmpleado($empleado_id);
        return array_map(function($r) {
            return $r['clave'];
        }, $rows);
    }

    /**
     * Guarda masivamente las claves presupuestales de un empleado (reemplaza las existentes)
     * @param int $empleado_id
     * @param array $claves
     * @return bool
     */
    public function guardar($empleado_id, array $claves) {
        $pdo = $this->db->getConnection();
        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare("DELETE FROM claves_presupuestales WHERE empleado_id = ?");
            $del->execute([$empleado_id]);

            $ins = $pdo->prepare("
                INSERT INTO claves_presupuestales (empleado_id, clave)
                VALUES (?, ?)
            ");
            foreach ($claves as $clave) {
                $clave = trim((string)$clave);
                if ($clave === '') {
                    continue;
                }
                $ins->execute([$empleado_id, mb_substr($clave, 0, 100)]);
            }
            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error guardando claves presupuestales de empleado $empleado_id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Agrega una clave presupuestal sin eliminar las existentes
     * @param int $empleado_id
     * @param string $clave
     * @return int|null
     */
    public function agregar($empleado_id, $clave) {
        $clave = trim((string)$clave);
        if ($clave === '') {
            return null;
        }
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO claves_presupuestales (empleado_id, clave)
            VALUES (?, ?)
        ");
        $stmt->execute([$empleado_id, mb_substr($clave, 0, 100)]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Elimina todas las claves presupuestales de un empleado
     * @param int $empleado_id
     * @return bool
     */
    public function eliminarPorEmpleado($empleado_id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM claves_presupuestales WHERE empleado_id = ?");
        return $stmt->execute([$empleado_id]);
    }

    /**
     * Une las claves de un empleado con " / " para documentos oficiales
     * @param int $empleado_id
     * @return string
     */
    public function getClavesUnidas($empleado_id) {
        return implode(' / ', $this->getClavesPorEmpleado($empleado_id));
    }
}
