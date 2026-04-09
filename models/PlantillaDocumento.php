<?php
require_once __DIR__ . '/Database.php';

class PlantillaDocumento {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    public function getAll($tipo = null, $activa = true) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT * FROM plantillas_documentos WHERE 1=1";
        $params = [];

        if ($tipo) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }
        if ($activa !== null) {
            $sql .= " AND activa = ?";
            $params[] = $activa ? 1 : 0;
        }

        $sql .= " ORDER BY nombre ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM plantillas_documentos WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByTipo($tipo) {
        return $this->getAll($tipo, true);
    }

    public function create($data) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO plantillas_documentos (nombre, tipo, contenido, variables_json, activa)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['tipo'],
            $data['contenido'],
            $data['variables_json'] ?? null,
            $data['activa'] ?? 1
        ]);
    }

    public function update($id, $data) {
        $pdo = $this->db->getConnection();
        $fields = [];
        $params = [];
        
        foreach (['nombre', 'tipo', 'contenido', 'variables_json', 'activa'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $params[] = $id;
        $sql = "UPDATE plantillas_documentos SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM plantillas_documentos WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function render($plantilla_id, $variables) {
        $plantilla = $this->getById($plantilla_id);
        if (!$plantilla) {
            return false;
        }

        $contenido = $plantilla['contenido'];
        
        foreach ($variables as $key => $value) {
            $contenido = str_replace('{' . $key . '}', $value, $contenido);
        }

        return $contenido;
    }
}

class DocumentoGenerado {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    public function create($data) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO documentos_generados (empleado_id, tipo_documento, plantilla_id, titulo, contenido, archivo_path, periodo, generado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['empleado_id'],
            $data['tipo_documento'],
            $data['plantilla_id'] ?? null,
            $data['titulo'],
            $data['contenido'] ?? null,
            $data['archivo_path'] ?? null,
            $data['periodo'],
            $data['generado_por'] ?? null
        ]);
    }

    public function getByEmpleado($empleado_id, $limit = 50) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM documentos_generados 
            WHERE empleado_id = ?
            ORDER BY fecha_generacion DESC
            LIMIT ?
        ");
        $stmt->execute([$empleado_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByFecha($fecha_inicio, $fecha_fin, $tipo = null) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT d.*, e.nombre, e.apellido, e.area 
                FROM documentos_generados d 
                LEFT JOIN empleados e ON d.empleado_id = e.id 
                WHERE d.fecha_generacion BETWEEN ? AND ?";
        $params = [$fecha_inicio, $fecha_fin];

        if ($tipo) {
            $sql .= " AND d.tipo_documento = ?";
            $params[] = $tipo;
        }

        $sql .= " ORDER BY d.fecha_generacion DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
