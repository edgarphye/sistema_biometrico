<?php
require_once __DIR__ . '/Database.php';

class Usuario {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function authenticate($username, $password) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    public function create($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO usuarios (username, password, email, nombre_completo, rol, empleado_id, activo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['username'],
            $hashedPassword,
            empty($data['email']) ? null : $data['email'],
            $data['nombre_completo'] ?? null,
            $data['rol'] ?? 'usuario',
            $data['empleado_id'] ?? null,
            $data['activo'] ?? 1
        ]);
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllWithEmpleado() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT u.id, u.username, u.email, u.nombre_completo, u.rol, u.empleado_id, u.activo, u.fecha_creacion,
                   CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre
            FROM usuarios u
            LEFT JOIN empleados e ON u.empleado_id = e.id
            ORDER BY u.id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $updateFields = [];
        $params = [];

        if (isset($data['username'])) {
            $updateFields[] = "username = ?";
            $params[] = $data['username'];
        }

        if (isset($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (isset($data['email'])) {
            $updateFields[] = "email = ?";
            $params[] = empty($data['email']) ? null : $data['email'];
        }

        if (isset($data['nombre_completo'])) {
            $updateFields[] = "nombre_completo = ?";
            $params[] = $data['nombre_completo'];
        }

        if (isset($data['rol'])) {
            $updateFields[] = "rol = ?";
            $params[] = $data['rol'];
        }

        if (isset($data['empleado_id'])) {
            $updateFields[] = "empleado_id = ?";
            $params[] = $data['empleado_id'];
        }

        if (isset($data['activo'])) {
            $updateFields[] = "activo = ?";
            $params[] = $data['activo'];
        }

        if (empty($updateFields)) {
            return false;
        }

        $params[] = $id;

        $stmt = $this->db->getConnection()->prepare("
            UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = ?
        ");
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("DELETE FROM usuarios WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleStatus($id, $activo) {
        $stmt = $this->db->getConnection()->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
        return $stmt->execute([$activo, $id]);
    }

    public function updatePassword($id, $password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->getConnection()->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        return $stmt->execute([$hashed, $id]);
    }

    public function getByUsername($username) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    public function getPermisos($usuarioId) {
        $stmt = $this->db->getConnection()->prepare("SELECT permiso FROM usuario_permisos WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);
        $result = $stmt->fetchAll();
        return array_column($result, 'permiso');
    }

    public function setPermisos($usuarioId, $permisos) {
        $conn = $this->db->getConnection();
        
        $stmt = $conn->prepare("DELETE FROM usuario_permisos WHERE usuario_id = ?");
        $stmt->execute([$usuarioId]);

        if (!empty($permisos)) {
            $stmt = $conn->prepare("INSERT INTO usuario_permisos (usuario_id, permiso) VALUES (?, ?)");
            foreach ($permisos as $permiso) {
                $stmt->execute([$usuarioId, $permiso]);
            }
        }
        return true;
    }

    public static function getTodosPermisos() {
        return [
            'dashboard' => 'Dashboard',
            'inicio' => 'Inicio',
            'empleados' => 'Ver Empleados',
            'empleados_ver' => 'Ver Empleados',
            'empleados_crear' => 'Crear Empleados',
            'empleados_editar' => 'Editar Empleados',
            'empleados_eliminar' => 'Eliminar Empleados',
            'asistencia' => 'Asistencia',
            'horarios' => 'Horarios',
            'horarios_ver' => 'Ver Horarios',
            'horarios_crear' => 'Crear Horarios',
            'horarios_editar' => 'Editar Horarios',
            'horarios_eliminar' => 'Eliminar Horarios',
            'ciclos' => 'Ciclos',
            'ciclos_ver' => 'Ver Ciclos',
            'ciclos_crear' => 'Crear Ciclos',
            'ciclos_editar' => 'Editar Ciclos',
            'ciclos_eliminar' => 'Eliminar Ciclos',
            'validaciones' => 'Validaciones',
            'validaciones_ver' => 'Ver Validaciones',
            'validaciones_aprobar' => 'Aprobar Validaciones',
            'validaciones_rechazar' => 'Rechazar Validaciones',
            'reportes' => 'Reportes',
            'reportes_ver' => 'Ver Reportes',
            'reportes_exportar' => 'Exportar Reportes',
            'reportes_excel' => 'Reportes Excel',
            'analisis_predictivo' => 'Análisis Predictivo',
            'biometricos' => 'Dispositivos Biométricos',
            'biometricos_ver' => 'Ver Dispositivos',
            'biometricos_agregar' => 'Agregar Dispositivos',
            'biometricos_configurar' => 'Configurar Dispositivos',
            'database' => 'Base de Datos',
            'database_backup' => 'Crear Backup',
            'database_restore' => 'Restaurar Backup',
            'database_export' => 'Exportar Datos',
            'catalogos' => 'Catálogos',
            'catalogos_ver' => 'Ver Catálogos',
            'catalogos_editar' => 'Editar Catálogos',
            'configuracion' => 'Configuración',
            'usuarios' => 'Gestión de Usuarios',
            'usuarios_ver' => 'Ver Usuarios',
            'usuarios_crear' => 'Crear Usuarios',
            'usuarios_editar' => 'Editar Usuarios',
            'usuarios_eliminar' => 'Eliminar Usuarios',
            'usuarios_permisos' => 'Administrar Permisos',
        ];
    }

    public static function getPermisosPorRol($rol) {
        $permisosBase = [
            'superadmin' => array_keys(self::getTodosPermisos()),
            'admin' => [
                'dashboard', 'inicio', 
                'empleados', 'empleados_ver', 'empleados_crear', 'empleados_editar', 'empleados_eliminar',
                'asistencia',
                'horarios', 'horarios_ver', 'horarios_crear', 'horarios_editar', 'horarios_eliminar',
                'ciclos', 'ciclos_ver', 'ciclos_crear', 'ciclos_editar', 'ciclos_eliminar',
                'validaciones', 'validaciones_ver', 'validaciones_aprobar', 'validaciones_rechazar',
                'reportes', 'reportes_ver', 'reportes_exportar', 'reportes_excel',
                'analisis_predictivo',
                'biometricos', 'biometricos_ver', 'biometricos_agregar', 'biometricos_configurar',
                'database', 'database_backup', 'database_restore', 'database_export',
                'catalogos', 'catalogos_ver', 'catalogos_editar',
                'configuracion',
                'usuarios', 'usuarios_ver', 'usuarios_crear', 'usuarios_editar', 'usuarios_eliminar', 'usuarios_permisos',
                'logs'
            ],
            'jefe' => [
                'dashboard', 'inicio', 
                'empleados', 'empleados_ver',
                'validaciones', 'validaciones_ver', 'validaciones_aprobar', 'validaciones_rechazar',
                'reportes', 'reportes_ver'
            ],
            'usuario' => [
                'dashboard', 'inicio', 
                'empleados', 'empleados_ver',
                'asistencia',
                'reportes', 'reportes_ver', 'reportes_exportar'
            ],
        ];
        return $permisosBase[$rol] ?? [];
    }
    
    public static function tienePermiso($permiso) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $rol = $_SESSION['rol'] ?? null;
        if ($rol === 'superadmin') return true;
        
        $permisos = $_SESSION['permisos'] ?? [];
        return in_array($permiso, $permisos);
    }
}
?>
