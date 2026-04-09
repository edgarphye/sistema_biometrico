<?php
require_once 'Database.php';

class DispositivoBiometrico
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * Obtiene todos los dispositivos biométricos
     */
    public function getAll()
    {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos 
            ORDER BY sede, dispositivo_id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene solo dispositivos activos
     */
    public function getActivos()
    {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos 
            WHERE activo = 1 
            ORDER BY sede, dispositivo_id
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Obtiene un dispositivo por su ID
     */
    public function getById($id)
    {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Obtiene un dispositivo por dispositivo_id
     */
    public function getByDispositivoId($dispositivo_id)
    {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos WHERE dispositivo_id = ?
        ");
        $stmt->execute([$dispositivo_id]);
        return $stmt->fetch();
    }

    /**
     * Obtiene dispositivos por sede
     */
    public function getBySede($sede)
    {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM dispositivos_biometricos 
            WHERE sede = ? AND activo = 1 
            ORDER BY dispositivo_id
        ");
        $stmt->execute([$sede]);
        return $stmt->fetchAll();
    }

    /**
     * Crea un nuevo dispositivo biométrico
     */
    public function create($data)
    {
        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO dispositivos_biometricos (
                dispositivo_id, nombre, sede, ip_address, puerto, 
                tipo_dispositivo, modelo, firmware_version, capacidades, 
                activo, fecha_instalacion, configuracion_adicional, notas
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['dispositivo_id'],
            $data['nombre'],
            $data['sede'],
            $data['ip_address'],
            $data['puerto'] ?? 4370,
            $data['tipo_dispositivo'] ?? 'ZKTeco',
            $data['modelo'] ?? null,
            $data['firmware_version'] ?? null,
            isset($data['capacidades']) ? json_encode($data['capacidades']) : '{"huella":true,"cara":false}',
            $data['activo'] ?? 1,
            $data['fecha_instalacion'] ?? date('Y-m-d'),
            isset($data['configuracion_adicional']) ? json_encode($data['configuracion_adicional']) : null,
            $data['notas'] ?? null
        ]);
    }

    /**
     * Actualiza un dispositivo biométrico
     */
    public function update($id, $data)
    {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dispositivos_biometricos SET 
                nombre = ?, 
                sede = ?, 
                ip_address = ?, 
                puerto = ?, 
                tipo_dispositivo = ?, 
                modelo = ?, 
                firmware_version = ?, 
                capacidades = ?, 
                activo = ?, 
                fecha_instalacion = ?, 
                configuracion_adicional = ?, 
                notas = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['nombre'],
            $data['sede'],
            $data['ip_address'],
            $data['puerto'] ?? 4370,
            $data['tipo_dispositivo'] ?? 'ZKTeco',
            $data['modelo'] ?? null,
            $data['firmware_version'] ?? null,
            isset($data['capacidades']) ? json_encode($data['capacidades']) : null,
            $data['activo'] ?? 1,
            $data['fecha_instalacion'] ?? null,
            isset($data['configuracion_adicional']) ? json_encode($data['configuracion_adicional']) : null,
            $data['notas'] ?? null,
            $id
        ]);
    }

    /**
     * Desactiva un dispositivo (soft delete)
     */
    public function delete($id)
    {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dispositivos_biometricos SET activo = 0 WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Reactiva un dispositivo
     */
    public function reactivate($id)
    {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dispositivos_biometricos SET activo = 1 WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Actualiza el estado de un dispositivo
     */
    public function updateStatus($id, $status)
    {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dispositivos_biometricos SET activo = ? WHERE id = ?
        ");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Actualiza la última sincronización
     */
    public function updateLastSync($id)
    {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE dispositivos_biometricos 
            SET ultima_sincronizacion = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Obtiene la configuración de un dispositivo para conexión
     */
    public function getConnectionConfig($dispositivo_id)
    {
        $dispositivo = $this->getByDispositivoId($dispositivo_id);

        if (!$dispositivo) {
            return null;
        }

        return [
            'dispositivo_id' => $dispositivo['dispositivo_id'],
            'nombre' => $dispositivo['nombre'],
            'sede' => $dispositivo['sede'],
            'ip_address' => $dispositivo['ip_address'],
            'puerto' => $dispositivo['puerto'],
            'tipo_dispositivo' => $dispositivo['tipo_dispositivo'],
            'modelo' => $dispositivo['modelo'],
            'capacidades' => json_decode($dispositivo['capacidades'], true),
            'configuracion_adicional' => json_decode($dispositivo['configuracion_adicional'], true)
        ];
    }

    /**
     * Obtiene todas las sedes únicas
     */
    public function getAllSedes()
    {
        $stmt = $this->db->getConnection()->prepare("
            SELECT DISTINCT sede FROM dispositivos_biometricos 
            WHERE activo = 1 
            ORDER BY sede
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Cuenta dispositivos por sede
     */
    public function countBySede()
    {
        $stmt = $this->db->getConnection()->prepare("
            SELECT sede, COUNT(*) as total, 
                   SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as activos
            FROM dispositivos_biometricos 
            GROUP BY sede 
            ORDER BY sede
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Verifica si una IP ya está en uso
     */
    public function ipExists($ip_address, $exclude_id = null)
    {
        if ($exclude_id) {
            $stmt = $this->db->getConnection()->prepare("
                SELECT COUNT(*) as count 
                FROM dispositivos_biometricos 
                WHERE ip_address = ? AND id != ?
            ");
            $stmt->execute([$ip_address, $exclude_id]);
        } else {
            $stmt = $this->db->getConnection()->prepare("
                SELECT COUNT(*) as count 
                FROM dispositivos_biometricos 
                WHERE ip_address = ?
            ");
            $stmt->execute([$ip_address]);
        }

        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Verifica si un dispositivo_id ya está en uso
     */
    public function dispositivoIdExists($dispositivo_id, $exclude_id = null)
    {
        if ($exclude_id) {
            $stmt = $this->db->getConnection()->prepare("
                SELECT COUNT(*) as count 
                FROM dispositivos_biometricos 
                WHERE dispositivo_id = ? AND id != ?
            ");
            $stmt->execute([$dispositivo_id, $exclude_id]);
        } else {
            $stmt = $this->db->getConnection()->prepare("
                SELECT COUNT(*) as count 
                FROM dispositivos_biometricos 
                WHERE dispositivo_id = ?
            ");
            $stmt->execute([$dispositivo_id]);
        }

        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}
?>