<?php
require_once __DIR__ . '/Database.php';

class ConfigOficio {
    private $db;

    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance();
    }

    /**
     * Obtiene la configuración de oficios para un año.
     * Si no existe la crea con valores por defecto.
     * @param int|null $anio
     * @return array|null
     */
    public function getConfig($anio = null) {
        $anio = (int)($anio ?? date('Y'));
        $pdo = $this->db->getConnection();

        $stmt = $pdo->prepare("SELECT * FROM config_oficios WHERE anio = ?");
        $stmt->execute([$anio]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            $this->crearConfig($anio);
            $stmt->execute([$anio]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return $config ?: null;
    }

    /**
     * Crea la fila de configuración para un año con valores por defecto
     */
    private function crearConfig($anio) {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO config_oficios (prefijo, anio, ultimo_folio, nombre_firmante, cargo_firmante, iniciales, membrete_path)
            VALUES (?, ?, 0, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'DGIFA/CA/RH',
            $anio,
            'JUAN JOSE OROZCO PONCE',
            'JEFE DEL DEPARTAMENTO DE RECURSOS HUMANOS',
            'DGNQ*',
            'assets/images/membrete_sep.png'
        ]);
    }

    /**
     * Reserva el siguiente folio para el año de forma atómica y devuelve el número usado.
     * @param int|null $anio
     * @return int|null folio asignado, o null si no hay configuración
     */
    public function obtenerSiguienteFolio($anio = null) {
        $anio = (int)($anio ?? date('Y'));
        $pdo = $this->db->getConnection();

        $this->getConfig($anio);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT ultimo_folio FROM config_oficios WHERE anio = ? FOR UPDATE");
            $stmt->execute([$anio]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $pdo->rollBack();
                return null;
            }
            $folio = (int)$row['ultimo_folio'] + 1;

            $upd = $pdo->prepare("UPDATE config_oficios SET ultimo_folio = ? WHERE anio = ?");
            $upd->execute([$folio, $anio]);

            $pdo->commit();
            return $folio;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Error obteniendo siguiente folio: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Formatea el folio como {prefijo}-{folio}/{anio}
     * @param array $config
     * @param int $folio
     * @return string
     */
    public static function formatearFolio($config, $folio) {
        $prefijo = ($config['prefijo'] ?? 'DGIFA/CA/RH');
        $anio = $config['anio'] ?? date('Y');
        return $prefijo . '-' . str_pad($folio, 3, '0', STR_PAD_LEFT) . '/' . $anio;
    }

    /**
     * Actualiza la configuración de oficios para un año
     * @param int $anio
     * @param array $data (prefijo, nombre_firmante, cargo_firmante, iniciales, membrete_path, ultimo_folio)
     * @return bool
     */
    public function guardar($anio, array $data) {
        $anio = (int)$anio;
        $pdo = $this->db->getConnection();

        $existe = $this->getConfig($anio);

        $campos = [];
        $params = [];
        foreach (['prefijo', 'nombre_firmante', 'cargo_firmante', 'iniciales', 'membrete_path'] as $campo) {
            if (isset($data[$campo])) {
                $campos[] = "$campo = ?";
                $params[] = mb_substr(trim((string)$data[$campo]), 0, $campo === 'cargo_firmante' ? 200 : 150);
            }
        }
        if (isset($data['ultimo_folio']) && is_numeric($data['ultimo_folio'])) {
            $campos[] = "ultimo_folio = ?";
            $params[] = (int)$data['ultimo_folio'];
        }
        if (empty($campos)) {
            return true;
        }
        $params[] = $anio;

        $sql = "UPDATE config_oficios SET " . implode(', ', $campos) . " WHERE anio = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Ruta absoluta del membrete (resuelta contra la raíz del proyecto)
     */
    public static function rutaMembrete($config) {
        $path = $config['membrete_path'] ?? 'assets/images/membrete_sep.png';
        if (strpos($path, __DIR__) === 0) {
            return $path;
        }
        return dirname(__DIR__) . '/' . ltrim($path, '/');
    }
}
