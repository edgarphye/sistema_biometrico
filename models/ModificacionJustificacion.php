<?php
require_once 'Database.php';

class ModificacionJustificacion {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las modificaciones de justificaciones con filtros
     */
    public function getAll($filtros = []) {
        $sql = "SELECT mj.*, 
                       e.num_empleado, e.nombre, e.apellido_paterno, e.apellido_materno,
                       u_mod.nombre as nombre_modificador,
                       u_valid.nombre as nombre_validador,
                       tj.nombre as tipo_justificacion_nombre
                FROM modificaciones_justificacion mj
                LEFT JOIN empleados e ON mj.empleado_id = e.id
                LEFT JOIN usuarios u_mod ON mj.modificado_por = u_mod.id
                LEFT JOIN usuarios u_valid ON mj.validado_por = u_valid.id
                LEFT JOIN tipos_justificacion tj ON mj.valor_nuevo = tj.id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros['empleado_id'])) {
            $sql .= " AND mj.empleado_id = ?";
            $params[] = $filtros['empleado_id'];
        }
        
        if (!empty($filtros['estatus'])) {
            $sql .= " AND mj.estatus = ?";
            $params[] = $filtros['estatus'];
        }
        
        if (!empty($filtros['tabla_origen'])) {
            $sql .= " AND mj.tabla_origen = ?";
            $params[] = $filtros['tabla_origen'];
        }
        
        if (!empty($filtros['fecha_inicio'])) {
            $sql .= " AND DATE(mj.fecha_modificacion) >= ?";
            $params[] = $filtros['fecha_inicio'];
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $sql .= " AND DATE(mj.fecha_modificacion) <= ?";
            $params[] = $filtros['fecha_fin'];
        }
        
        $sql .= " ORDER BY mj.fecha_modificacion DESC";
        
        if (!empty($filtros['limit'])) {
            $sql .= " LIMIT " . (int)$filtros['limit'];
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Crear una nueva modificación de justificación
     */
    public function create($data) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // 1. Insertar el registro de modificación
            $stmt = $conn->prepare("
                INSERT INTO modificaciones_justificacion 
                (tabla_origen, registro_id, empleado_id, campo_modificado, valor_anterior, valor_nuevo, modificado_por, motivo_modificacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['tabla_origen'],
                $data['registro_id'],
                $data['empleado_id'],
                $data['campo_modificado'],
                $data['valor_anterior'],
                $data['valor_nuevo'],
                $data['modificado_por'],
                $data['motivo_modificacion'] ?? null
            ]);
            
            $modificacion_id = $conn->lastInsertId();
            
            // 2. Actualizar el registro original según la tabla
            $tabla = $data['tabla_origen'];
            $registro_id = $data['registro_id'];
            $campo = $data['campo_modificado'];
            $valor_nuevo = $data['valor_nuevo'];
            
            if ($tabla === 'retardos') {
                $updateStmt = $conn->prepare("
                    UPDATE retardos SET 
                        tipo_justificacion_id = ?,
                        motivo_justificacion = ?,
                        modificado_por = ?,
                        fecha_modificacion = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $valor_nuevo,
                    $data['motivo_modificacion'] ?? null,
                    $data['modificado_por'],
                    $registro_id
                ]);
            } elseif ($tabla === 'asistencia') {
                $updateStmt = $conn->prepare("
                    UPDATE asistencia SET 
                        tipo_justificacion_id = ?,
                        observaciones = ?,
                        modificado_por = ?,
                        fecha_modificacion = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([
                    $valor_nuevo,
                    $data['motivo_modificacion'] ?? null,
                    $data['modificado_por'],
                    $registro_id
                ]);
            }
            
            $conn->commit();
            return $modificacion_id;
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * Aprobar o rechazar una modificación
     */
    public function validar($modificacion_id, $user_id, $estatus, $es_validacion_jefe = false) {
        $conn = $this->db->getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Obtener la modificación
            $stmt = $conn->prepare("SELECT * FROM modificaciones_justificacion WHERE id = ?");
            $stmt->execute([$modificacion_id]);
            $modificacion = $stmt->fetch();
            
            if (!$modificacion) {
                throw new Exception("Modificación no encontrada");
            }
            
            // Actualizar la modificación
            $updateStmt = $conn->prepare("
                UPDATE modificaciones_justificacion SET 
                    validado_por = ?,
                    fecha_validacion = NOW(),
                    es_validacion_jefe = ?,
                    estatus = ?
                WHERE id = ?
            ");
            $updateStmt->execute([
                $user_id,
                $es_validacion_jefe ? 1 : 0,
                $estatus,
                $modificacion_id
            ]);
            
            // Si se aprueba, actualizar el registro original
            if ($estatus === 'aprobado') {
                $tabla = $modificacion['tabla_origen'];
                $registro_id = $modificacion['registro_id'];
                $campo = $modificacion['campo_modificado'];
                $valor_approved = $modificacion['validado_por'];
                
                if ($tabla === 'retardos') {
                    $updOrig = $conn->prepare("
                        UPDATE retardos SET 
                            justificado = 1,
                            aprobado_por = ?,
                            fecha_aprobacion = NOW()
                        WHERE id = ?
                    ");
                    $updOrig->execute([$user_id, $registro_id]);
                }
            }
            
            $conn->commit();
            return true;
            
        } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de modificaciones por período
     */
    public function getEstadisticas($fecha_inicio, $fecha_fin) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT 
                DATE(fecha_modificacion) as fecha,
                COUNT(*) as total_modificaciones,
                SUM(CASE WHEN estatus = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estatus = 'aprobado' THEN 1 ELSE 0 END) as aprobadas,
                SUM(CASE WHEN estatus = 'rechazado' THEN 1 ELSE 0 END) as rechazadas,
                SUM(CASE WHEN es_validacion_jefe = 1 THEN 1 ELSE 0 END) as validadas_jefe
            FROM modificaciones_justificacion
            WHERE fecha_modificacion BETWEEN ? AND ?
            GROUP BY DATE(fecha_modificacion)
            ORDER BY fecha DESC
        ");
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        return $stmt->fetchAll();
    }
}