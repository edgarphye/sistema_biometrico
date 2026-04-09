<?php
require_once 'Database.php';

/**
 * Modelo para Catálogos: Direcciones, Subdirecciones y Departamentos
 */
class Catalogo
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }
    
    // ==================== DIRECCIONES ====================
    
    public function getAllDirecciones() {
        $sql = "SELECT * FROM direcciones ORDER BY clave_dir";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getDireccion($clave_dir) {
        $sql = "SELECT * FROM direcciones WHERE clave_dir = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_dir]);
        return $stmt->fetch();
    }
    
    public function createDireccion($data) {
        $sql = "INSERT INTO direcciones (clave_dir, clave_dir2, nombre_direccion, activo) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['clave_dir'],
            $data['clave_dir2'] ?? '',
            $data['nombre_direccion'],
            $data['activo'] ?? 1
        ]);
    }
    
    public function updateDireccion($clave_dir, $data) {
        $sql = "UPDATE direcciones SET clave_dir2 = ?, nombre_direccion = ?, activo = ? WHERE clave_dir = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['clave_dir2'] ?? '',
            $data['nombre_direccion'],
            $data['activo'],
            $clave_dir
        ]);
    }
    
    public function deleteDireccion($clave_dir) {
        $sql = "SELECT COUNT(*) as total FROM subdirecciones WHERE clave_dir = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_dir]);
        $result = $stmt->fetch();
        if ($result['total'] > 0) {
            return ['success' => false, 'error' => 'No se puede eliminar. Tiene subdirecciones asociadas.'];
        }
        
        $sql = "DELETE FROM direcciones WHERE clave_dir = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_dir]);
        return ['success' => true];
    }
    
    // ==================== SUBDIRECCIONES ====================
    
    public function getAllSubdirecciones() {
        $conn = $this->db->getConnection();
        $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $sql = "SELECT s.*, d.nombre_direccion 
                FROM subdirecciones s 
                LEFT JOIN direcciones d ON s.clave_dir = d.clave_dir2
                ORDER BY d.clave_dir2, s.clave_subdir";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getSubdireccion($clave_subdir) {
        $conn = $this->db->getConnection();
        $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $sql = "SELECT s.*, d.nombre_direccion 
                FROM subdirecciones s 
                LEFT JOIN direcciones d ON s.clave_dir = d.clave_dir2
                WHERE s.clave_subdir = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$clave_subdir]);
        return $stmt->fetch();
    }
    
    public function getSubdireccionesByDireccion($clave_dir) {
        $sql = "SELECT * FROM subdirecciones WHERE clave_dir = ? ORDER BY nombre_subdir";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_dir]);
        return $stmt->fetchAll();
    }
    
    public function createSubdireccion($data) {
        $sql = "INSERT INTO subdirecciones (clave_subdir, clave_dir, nombre_subdir, activo) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['clave_subdir'],
            $data['clave_dir'],
            $data['nombre_subdir'],
            $data['activo'] ?? 1
        ]);
    }
    
    public function updateSubdireccion($clave_subdir, $data) {
        $sql = "UPDATE subdirecciones SET clave_dir = ?, nombre_subdir = ?, activo = ? WHERE clave_subdir = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['clave_dir'],
            $data['nombre_subdir'],
            $data['activo'],
            $clave_subdir
        ]);
    }
    
    public function deleteSubdireccion($clave_subdir) {
        $sql = "SELECT COUNT(*) as total FROM departamentos WHERE clave_sub = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_subdir]);
        $result = $stmt->fetch();
        if ($result['total'] > 0) {
            return ['success' => false, 'error' => 'No se puede eliminar. Tiene departamentos asociados.'];
        }
        
        $sql = "DELETE FROM subdirecciones WHERE clave_subdir = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_subdir]);
        return ['success' => true];
    }
    
    // ==================== DEPARTAMENTOS ====================
    
    public function getAllDepartamentos() {
        $conn = $this->db->getConnection();
        $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $sql = "SELECT d.*, COALESCE(s.nombre_subdir, dir.nombre_direccion) as nombre_subdir
                FROM departamentos d 
                LEFT JOIN subdirecciones s ON d.clave_sub = s.clave_subdir
                LEFT JOIN direcciones dir ON d.clave_sub = dir.clave_dir2
                ORDER BY d.clave_depto";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getDepartamento($clave_depto) {
        $conn = $this->db->getConnection();
        $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $sql = "SELECT d.*, COALESCE(s.nombre_subdir, dir.nombre_direccion) as nombre_subdir
                FROM departamentos d 
                LEFT JOIN subdirecciones s ON d.clave_sub = s.clave_subdir
                LEFT JOIN direcciones dir ON d.clave_sub = dir.clave_dir2
                WHERE d.clave_depto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$clave_depto]);
        return $stmt->fetch();
    }
    
    public function getDepartamentosBySubdireccion($clave_subdir) {
        $sql = "SELECT * FROM departamentos WHERE clave_sub = ? ORDER BY nombre_departamento";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_subdir]);
        return $stmt->fetchAll();
    }
    
    public function createDepartamento($data) {
        $sql = "INSERT INTO departamentos (clave_depto, clave_sub, nombre_departamento, activo) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['clave_depto'],
            $data['clave_sub'],
            $data['nombre_departamento'],
            $data['activo'] ?? 1
        ]);
    }
    
    public function updateDepartamento($clave_depto, $data) {
        $sql = "UPDATE departamentos SET clave_sub = ?, nombre_departamento = ?, activo = ? WHERE clave_depto = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['clave_sub'],
            $data['nombre_departamento'],
            $data['activo'],
            $clave_depto
        ]);
    }
    
    public function deleteDepartamento($clave_depto) {
        $sql = "DELETE FROM departamentos WHERE clave_depto = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_depto]);
        return ['success' => true];
    }
    
    // ==================== COMBOS ANIDADOS ====================
    
    public function getDireccionesForCombo() {
        $sql = "SELECT clave_dir as id, nombre_direccion as nombre FROM direcciones WHERE activo = 1 ORDER BY nombre_direccion";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getSubdireccionesForCombo($clave_dir = null) {
        if ($clave_dir) {
            $sql = "SELECT clave_subdir as id, nombre_subdir as nombre FROM subdirecciones WHERE activo = 1 AND clave_dir = ? ORDER BY nombre_subdir";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$clave_dir]);
        } else {
            $sql = "SELECT clave_subdir as id, nombre_subdir as nombre FROM subdirecciones WHERE activo = 1 ORDER BY nombre_subdir";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
    
    public function getDepartamentosForCombo($clave_sub = null) {
        if ($clave_sub) {
            $sql = "SELECT clave_depto as id, nombre_departamento as nombre FROM departamentos WHERE activo = 1 AND clave_sub = ? ORDER BY nombre_departamento";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([$clave_sub]);
        } else {
            $sql = "SELECT clave_depto as id, nombre_departamento as nombre FROM departamentos WHERE activo = 1 ORDER BY nombre_departamento";
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
    
    // ==================== CATÁLOGO DE MANDOS ====================
    
    public function getAllMandos() {
        $conn = $this->db->getConnection();
        $conn->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $sql = "SELECT m.*, d.nombre_departamento 
                FROM catalogos_mandos m 
                LEFT JOIN departamentos d ON m.clave_depto = d.clave_depto
                ORDER BY m.area, m.clave_area";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getMando($clave_area) {
        $sql = "SELECT * FROM catalogos_mandos WHERE clave_area = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_area]);
        return $stmt->fetch();
    }
    
    public function createMando($data) {
        $sql = "INSERT INTO catalogos_mandos (clave_area, nombre_mando, area, clave_depto, activo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['clave_area'],
            $data['nombre_mando'],
            $data['area'],
            $data['clave_depto'] ?? null,
            $data['activo'] ?? 1
        ]);
    }
    
    public function updateMando($clave_area, $data) {
        $sql = "UPDATE catalogos_mandos SET nombre_mando = ?, area = ?, clave_depto = ?, activo = ? WHERE clave_area = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        return $stmt->execute([
            $data['nombre_mando'],
            $data['area'],
            $data['clave_depto'] ?? null,
            $data['activo'],
            $clave_area
        ]);
    }
    
    public function deleteMando($clave_area) {
        $sql = "DELETE FROM catalogos_mandos WHERE clave_area = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$clave_area]);
        return ['success' => true];
    }
}
