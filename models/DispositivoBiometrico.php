<?php
require_once 'Database.php';

/**
 * Modelo para gestión de dispositivos biométricos
 */
class DispositivoBiometrico {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Obtiene todos los dispositivos configurados
     */
    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos
            ORDER BY dispositivo_id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un dispositivo por ID
     */
    public function getById($dispositivoId) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos
            WHERE dispositivo_id = ?
        ");
        $stmt->execute([$dispositivoId]);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo dispositivo
     */
    public function create($data) {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO dispositivos_biometricos
            (dispositivo_id, nombre, tipo, marca, modelo, ip_address, puerto, usuario, password, configuracion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);
        $configuracion = isset($data['configuracion']) ? json_encode($data['configuracion']) : null;

        return $stmt->execute([
            $data['dispositivo_id'],
            $data['nombre'],
            $data['tipo'] ?? 'dual',
            $data['marca'] ?? null,
            $data['modelo'] ?? null,
            $data['ip_address'] ?? null,
            $data['puerto'] ?? 4370,
            $data['usuario'] ?? null,
            $passwordHash,
            $configuracion
        ]);
    }

    /**
     * Actualiza un dispositivo
     */
    public function update($dispositivoId, $data) {
        $fields = [];
        $params = [];

        if (isset($data['nombre'])) {
            $fields[] = "nombre = ?";
            $params[] = $data['nombre'];
        }
        if (isset($data['tipo'])) {
            $fields[] = "tipo = ?";
            $params[] = $data['tipo'];
        }
        if (isset($data['marca'])) {
            $fields[] = "marca = ?";
            $params[] = $data['marca'];
        }
        if (isset($data['modelo'])) {
            $fields[] = "modelo = ?";
            $params[] = $data['modelo'];
        }
        if (isset($data['ip_address'])) {
            $fields[] = "ip_address = ?";
            $params[] = $data['ip_address'];
        }
        if (isset($data['puerto'])) {
            $fields[] = "puerto = ?";
            $params[] = $data['puerto'];
        }
        if (isset($data['usuario'])) {
            $fields[] = "usuario = ?";
            $params[] = $data['usuario'];
        }
        if (isset($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        if (isset($data['activo'])) {
            $fields[] = "activo = ?";
            $params[] = $data['activo'];
        }
        if (isset($data['configuracion'])) {
            $fields[] = "configuracion = ?";
            $params[] = json_encode($data['configuracion']);
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $dispositivoId;
        $query = "UPDATE dispositivos_biometricos SET " . implode(', ', $fields) . " WHERE dispositivo_id = ?";

        $stmt = $this->db->getConnection()->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Actualiza el estado de un dispositivo
     */
    public function updateEstado($dispositivoId, $estado) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dispositivos_biometricos
            SET estado = ?, ultima_conexion = CURRENT_TIMESTAMP
            WHERE dispositivo_id = ?
        ");
        return $stmt->execute([$estado, $dispositivoId]);
    }

    /**
     * Elimina un dispositivo
     */
    public function delete($dispositivoId) {
        $stmt = $this->db->getConnection()->prepare("
            DELETE FROM dispositivos_biometricos WHERE dispositivo_id = ?
        ");
        return $stmt->execute([$dispositivoId]);
    }

    /**
     * Verifica si un dispositivo existe
     */
    public function exists($dispositivoId) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT COUNT(*) as count FROM dispositivos_biometricos WHERE dispositivo_id = ?
        ");
        $stmt->execute([$dispositivoId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Obtiene dispositivos activos
     */
    public function getActivos() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos
            WHERE activo = 1
            ORDER BY dispositivo_id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Registra un empleado en un dispositivo
     */
    public function registrarEmpleadoEnDispositivo($dispositivoId, $empleadoId, $tipoBiometria = null) {
        // Obtener datos del empleado
        $stmt = $this->db->getConnection()->prepare("
            SELECT nombre, apellido, huella_dactilar, foto_cara
            FROM empleados WHERE id = ?
        ");
        $stmt->execute([$empleadoId]);
        $empleado = $stmt->fetch();

        if (!$empleado) {
            return false;
        }

        // Descifrar huella si está presente
        if (!empty($empleado['huella_dactilar'])) {
            require_once __DIR__ . '/../helpers/Encryption.php';
            $empleado['huella_dactilar'] = Encryption::decrypt($empleado['huella_dactilar']);
        }

        // Aquí iría la lógica para registrar en el dispositivo físico
        // Por ahora, solo registramos en logs
        $logModel = new LogDispositivo();
        $logModel->logEvent(
            $dispositivoId,
            'registro',
            'exitoso',
            'Empleado registrado en dispositivo',
            [
                'empleado_id' => $empleadoId,
                'tipo_biometria' => $tipoBiometria,
                'nombre_completo' => $empleado['nombre'] . ' ' . $empleado['apellido']
            ],
            $empleadoId,
            $tipoBiometria
        );

        return true;
    }

    /**
     * Verifica credenciales de dispositivo
     */
    public function verificarCredenciales($dispositivoId, $usuario, $password) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT password FROM dispositivos_biometricos
            WHERE dispositivo_id = ? AND usuario = ? AND activo = 1
        ");
        $stmt->execute([$dispositivoId, $usuario]);
        $result = $stmt->fetch();

        if ($result && password_verify($password, $result['password'])) {
            return true;
        }

        return false;
    }
}
?>
