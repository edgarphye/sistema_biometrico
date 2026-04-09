<?php
require_once __DIR__ . '/Database.php';

class ReglaNegocio {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    public function getAll($tipo = null, $categoria = null, $activa = true) {
        $pdo = $this->db->getConnection();
        $sql = "SELECT * FROM reglas_negocio WHERE 1=1";
        $params = [];

        if ($tipo) {
            $sql .= " AND tipo = ?";
            $params[] = $tipo;
        }
        if ($categoria) {
            $sql .= " AND categoria = ?";
            $params[] = $categoria;
        }
        if ($activa !== null) {
            $sql .= " AND activa = ?";
            $params[] = $activa ? 1 : 0;
        }

        $sql .= " ORDER BY prioridad ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM reglas_negocio WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO reglas_negocio (nombre, descripcion, tipo, categoria, condicion_json, accion_json, prioridad, activa, editable, requiere_aprobacion, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['nombre'],
            $data['descripcion'] ?? null,
            $data['tipo'],
            $data['categoria'],
            $data['condicion_json'],
            $data['accion_json'],
            $data['prioridad'] ?? 0,
            $data['activa'] ?? 1,
            $data['editable'] ?? 1,
            $data['requiere_aprobacion'] ?? 0,
            $data['created_by'] ?? null
        ]);
    }

    public function update($id, $data) {
        $pdo = $this->db->getConnection();
        
        $fields = [];
        $params = [];
        
        foreach (['nombre', 'descripcion', 'tipo', 'categoria', 'condicion_json', 'accion_json', 'prioridad', 'activa', 'editable', 'requiere_aprobacion'] as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($fields)) return false;
        
        $params[] = $id;
        $sql = "UPDATE reglas_negocio SET " . implode(', ', $fields) . ", version = version + 1 WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM reglas_negocio WHERE id = ? AND editable = 1");
        return $stmt->execute([$id]);
    }

    public function evaluar($tipo, $datos) {
        $reglas = $this->getAll($tipo, null, true);
        $resultados = [];

        foreach ($reglas as $regla) {
            $condicion = json_decode($regla['condicion_json'], true);
            $cumple = $this->evaluarCondicion($condicion, $datos);
            
            $resultados[] = [
                'regla_id' => $regla['id'],
                'nombre' => $regla['nombre'],
                'cumple' => $cumple,
                'accion' => json_decode($regla['accion_json'], true)
            ];
        }

        return $resultados;
    }

    private function evaluarCondicion($condicion, $datos) {
        $campo = $condicion['campo'] ?? null;
        $valor = $datos[$campo] ?? null;
        $operador = $condicion['operador'] ?? '==';

        switch ($operador) {
            case '==':
                return $valor == $condicion['valor'];
            case '!=':
                return $valor != $condicion['valor'];
            case '>':
                return $valor > $condicion['valor'];
            case '>=':
                return $valor >= $condicion['valor'];
            case '<':
                return $valor < $condicion['valor'];
            case '<=':
                return $valor <= $condicion['valor'];
            case 'entre':
                return $valor >= $condicion['min'] && $valor <= $condicion['max'];
            default:
                return false;
        }
    }
}
