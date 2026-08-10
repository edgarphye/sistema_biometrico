<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../helpers/Encryption.php';

class Empleado {
    private $db;

    // Constantes para entidades federativas mexicanas
    /*const ENTIDADES_FEDERATIVAS = [
        'AGUASCALIENTES' => 'AS',
        'BAJA CALIFORNIA' => 'BC',
        'BAJA CALIFORNIA SUR' => 'BS',
        'CAMPECHE' => 'CC',
        'COAHUILA' => 'CL',
        'COLIMA' => 'CM',
        'CHIAPAS' => 'CS',
        'CHIHUAHUA' => 'CH',
        'DISTRITO FEDERAL' => 'DF',
        'DURANGO' => 'DG',
        'GUANAJUATO' => 'GT',
        'GUERRERO' => 'GR',
        'HIDALGO' => 'HG',
        'JALISCO' => 'JC',
        'MEXICO' => 'MC',
        'MICHOACAN' => 'MN',
        'MORELOS' => 'MS',
        'NAYARIT' => 'NT',
        'NUEVO LEON' => 'NL',
        'OAXACA' => 'OC',
        'PUEBLA' => 'PL',
        'QUERETARO' => 'QT',
        'QUINTANA ROO' => 'QR',
        'SAN LUIS POTOSI' => 'SP',
        'SINALOA' => 'SL',
        'SONORA' => 'SR',
        'TABASCO' => 'TC',
        'TAMAULIPAS' => 'TS',
        'TLAXCALA' => 'TL',
        'VERACRUZ' => 'VZ',
        'YUCATAN' => 'YN',
        'ZACATECAS' => 'ZS',
        'NACIDO EXTRANJERO' => 'NE'
    ];*/

    public function __construct() {
        $this->db = new Database();
    }

    public function getAll() {
        $stmt = $this->db->getConnection()->prepare("SELECT id, nombre, apellido, rfc, curp, area, jerarquia, sexo, fecha_nacimiento, entidad_federativa, foto_cara, activo, jefe_directo_id,
            CASE WHEN huella_dactilar IS NOT NULL AND huella_dactilar != '' THEN 1 ELSE 0 END as tiene_huella 
            FROM empleados WHERE activo = 1");
        $stmt->execute();
        $rows = $stmt->fetchAll();
        // No desencriptar huellas, solo indicar si existen
        return $rows;
    }

    public function getById($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT id, nombre, apellido, rfc, curp, area, jerarquia, sexo, fecha_nacimiento, fecha_ingreso, entidad_federativa, foto_cara, jefe_directo_id, jefe_directo_clave, clave_depto, activo,
            CASE WHEN huella_dactilar IS NOT NULL AND huella_dactilar != '' THEN 1 ELSE 0 END as tiene_huella 
            FROM empleados WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row;
    }
    
    /**
     * Obtiene la huella dactilar encriptada para verificación biométrica
     * @param int $id ID del empleado
     * @return string|null Huella encriptada o null si no existe
     */
    public function getHuellaEncriptada($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT huella_dactilar FROM empleados WHERE id = ? AND activo = 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row && !empty($row['huella_dactilar']) ? $row['huella_dactilar'] : null;
    }

    public function create($data) {
        // Validar campos requeridos (zkteo_id es opcional: se asigna al vincular biométricamente)
        $errors = [];
        if (empty($data['nombre'])) {
            $errors[] = 'Nombre es requerido';
        }
        if (empty($data['apellido'])) {
            $errors[] = 'Apellido es requerido';
        }
        if (empty($data['rfc'])) {
            $errors[] = 'RFC es requerido';
        }
        if (empty($data['curp'])) {
            $errors[] = 'CURP es requerido';
        }
        
        if (!empty($errors)) {
            return false;
        }

        $stmt = $this->db->getConnection()->prepare("
            INSERT INTO empleados (nombre, apellido, rfc, curp, zkteo_id, area, area_fisica, jerarquia, sexo, fecha_nacimiento, entidad_federativa, huella_dactilar, foto_cara, clave_depto, jefe_directo_id, jefe_directo_clave)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $huella = $data['huella_dactilar'] ?? null;
        if ($huella !== null) {
            $huella = Encryption::encrypt($huella);
        }
        
        // Si se seleccionó un jefe directo (clave_area), buscar su ID usando la tabla de mandos
        $jefeId = null;
        $claveJefe = $data['jefe_directo_clave'] ?? $data['jefe_directo_id'] ?? null;
        if (!empty($claveJefe)) {
            // Buscar el mando en catalogos_mandos usando la clave_area y obtener el empleado que tiene esa clave_depto
            $mandoStmt = $this->db->getConnection()->prepare("
                SELECT m.id as mando_id, m.clave_depto, e.id as empleado_id 
                FROM catalogos_mandos m
                LEFT JOIN empleados e ON e.clave_depto = m.clave_depto AND e.activo = 1
                WHERE m.clave_area = ? AND m.activo = 1
                LIMIT 1
            ");
            $mandoStmt->execute([$claveJefe]);
            $mando = $mandoStmt->fetch();
            
            if ($mando && $mando['empleado_id']) {
                $jefeId = $mando['empleado_id'];
            }
        }
        
        $result = $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['rfc'],
            $data['curp'],
            $data['zkteo_id'] ?? null,
            $data['area'],
            $data['area_fisica'] ?? null,
            $data['jerarquia'],
            $data['sexo'] ?? null,
            $data['fecha_nacimiento'] ?? null,
            $data['entidad_federativa'] ?? null,
            $huella,
            $data['foto_cara'] ?? null,
            $data['clave_depto'] ?? null,
            $jefeId,
            $claveJefe
        ]);
        
        if ($result) {
            return (int)$this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $stmt = $this->db->getConnection()->prepare("
            UPDATE empleados SET
                nombre = ?,
                apellido = ?,
                rfc = ?,
                curp = ?,
                area = ?,
                area_fisica = ?,
                jerarquia = ?,
                huella_dactilar = ?,
                foto_cara = ?,
                sexo = ?,
                fecha_nacimiento = ?,
                entidad_federativa = ?,
                puesto = ?,
                clave_depto = ?,
                jefe_directo_id = ?,
                jefe_directo_clave = ?,
                activo = ?
            WHERE id = ?
        ");
        $huella = $data['huella_dactilar'] ?? null;
        if ($huella !== null) {
            $huella = Encryption::encrypt($huella);
        }
        
        // Validar que el jefe_directo_id exista en empleados buscando en catalogos_mandos
        $jefeId = null;
        if (!empty($data['jefe_directo_clave'])) {
            // Buscar el mando en catalogos_mandos que coincida con la clave seleccionada
            $mandoStmt = $this->db->getConnection()->prepare("
                SELECT m.id as mando_id, m.clave_depto, e.id as empleado_id 
                FROM catalogos_mandos m
                LEFT JOIN empleados e ON e.clave_depto = m.clave_depto AND e.activo = 1
                WHERE m.clave_area = ? AND m.activo = 1
                LIMIT 1
            ");
            $mandoStmt->execute([$data['jefe_directo_clave']]);
            $mando = $mandoStmt->fetch();
            
            if ($mando && $mando['empleado_id']) {
                $jefeId = $mando['empleado_id'];
            }
        }
        
        return $stmt->execute([
            $data['nombre'],
            $data['apellido'],
            $data['rfc'],
            $data['curp'],
            $data['area'],
            $data['area_fisica'] ?? null,
            $data['jerarquia'],
            $huella,
            $data['foto_cara'] ?? null,
            $data['sexo'] ?? null,
            $data['fecha_nacimiento'] ?? null,
            $data['entidad_federativa'] ?? null,
            $data['puesto'] ?? null,
            $data['clave_depto'] ?? null,
            $jefeId,
            $data['jefe_directo_clave'] ?? null,
            $data['activo'] ?? 1,
            $id
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->db->getConnection()->prepare("UPDATE empleados SET activo = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Obtiene todas las áreas únicas de empleados activos
     * @return array Lista de áreas únicas
     */
    public function getAreasActivas() {
        $stmt = $this->db->getConnection()->prepare("
            SELECT DISTINCT area FROM empleados
            WHERE activo = 1 AND area IS NOT NULL AND area != ''
            ORDER BY area
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function readCompleteData($id) {
        $empleado = $this->getById($id);
        if (!$empleado) {
            return null;
        }

        $mesActual = date('m-Y');

        $asistencias = $this->getAsistenciasByEmpleado($id);
        $retardos = $this->getRetardosByEmpleado($id);
        $comisiones = $this->getComisionesByEmpleado($id);
        $ausencias = $this->getAusenciasByEmpleado($id);

        $empleado['estadisticas'] = [
            'total_asistencias' => count(array_filter($asistencias, function($a) use ($mesActual) {
                return date('m-Y', strtotime($a['fecha'])) == $mesActual;
            })),
            'retardos_mes' => count(array_filter($retardos, function($r) use ($mesActual) {
                return date('m-Y', strtotime($r['fecha'])) == $mesActual;
            })),
            'comisiones_pendientes' => count(array_filter($comisiones, function($c) {
                return !$c['justificada'];
            })),
            'ausencias_mes' => count(array_filter($ausencias, function($a) use ($mesActual) {
                return date('m-Y', strtotime($a['fecha_inicio'])) == $mesActual;
            }))
        ];

        $empleado['retardos'] = $retardos;
        $empleado['comisiones'] = $comisiones;
        $empleado['ausencias'] = $ausencias;

        return $empleado;
    }

    private function getAsistenciasByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM asistencia WHERE empleado_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    private function getRetardosByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM retardos WHERE empleado_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    private function getComisionesByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("SELECT * FROM comisiones WHERE empleado_id = ? ORDER BY fecha_vencimiento DESC");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }     

    /**
     * Obtiene las ausencias de un empleado específico
     * @param int $id ID del empleado
     * @return array Ausencias del empleado
     */
    private function getAusenciasByEmpleado($id) {
        $stmt = $this->db->getConnection()->prepare("
            SELECT * FROM ausencias 
            WHERE empleado_id = ? 
            ORDER BY fecha_inicio DESC 
            LIMIT 100
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener empleados con paginación y filtros
     * @param int $page Página actual (default 1)
     * @param int $limit Registros por página (default 20)
     * @param string $search Término de búsqueda (opcional)
     * @param string $area Filtro por área (opcional)
     * @param string $jerarquia Filtro por jerarquía (opcional)
     * @return array Empleados paginados
     */
    public function getAllPaginated($page = 1, $limit = 20, $search = '', $area = '', $jerarquia = '') {
        $offset = ($page - 1) * $limit;
        
        // Construir consulta base
        $sql = "SELECT * FROM empleados WHERE activo = 1";
        $params = [];
        
        // Agregar filtros
        if (!empty($search)) {
            $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR rfc LIKE ? OR curp LIKE ? OR id = ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = is_numeric($search) ? intval($search) : 0;
        }
        
        if (!empty($area)) {
            $sql .= " AND area = ?";
            $params[] = $area;
        }
        
        if (!empty($jerarquia)) {
            $sql .= " AND jerarquia = ?";
            $params[] = $jerarquia;
        }
        
        // Agregar ordenamiento y paginación usando índices
        $sql .= " ORDER BY area ASC, nombre ASC, apellido ASC";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        
        // No desencriptar huellas, solo indicar si existen
        
        return $rows;
    }
    
    /**
     * Obtener total de empleados para paginación
     * @param string $search Término de búsqueda (opcional)
     * @param string $area Filtro por área (opcional)
     * @param string $jerarquia Filtro por jerarquía (opcional)
     * @return int Total de registros
     */
    public function getTotalCount($search = '', $area = '', $jerarquia = '') {
        $sql = "SELECT COUNT(*) as total FROM empleados WHERE activo = 1";
        $params = [];
        
        // Agregar mismos filtros que en getAllPaginated
        if (!empty($search)) {
            $sql .= " AND (nombre LIKE ? OR apellido LIKE ? OR rfc LIKE ? OR curp LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($area)) {
            $sql .= " AND area = ?";
            $params[] = $area;
        }
        
        if (!empty($jerarquia)) {
            $sql .= " AND jerarquia = ?";
            $params[] = $jerarquia;
        }
        
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'];
    }
    
    /**
     * Obtener información de paginación
     * @param int $page Página actual
     * @param int $limit Registros por página
     * @param int $total Total de registros
     * @return array Información de paginación
     */
    public function getPaginationInfo($page, $limit, $total) {
        $totalPages = ceil($total / $limit);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;
        
        return [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $hasNext,
            'has_prev' => $hasPrev,
            'next_page' => $hasNext ? $page + 1 : null,
            'prev_page' => $hasPrev ? $page - 1 : null,
            'showing_from' => min(($page - 1) * $limit + 1, $total),
            'showing_to' => min($page * $limit, $total)
        ];
    }

}

?>
