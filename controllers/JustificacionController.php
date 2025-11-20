<?php
require_once 'models/Retardo.php';
require_once 'models/TipoJustificacion.php';
require_once 'models/Usuario.php';

class JustificacionController {
    private $retardoModel;
    private $tipoJustificacionModel;
    private $usuarioModel;

    public function __construct() {
        $this->retardoModel = new Retardo();
        $this->tipoJustificacionModel = new TipoJustificacion();
        $this->usuarioModel = new Usuario();
    }

    /**
     * Mostrar retardos pendientes de justificación
     */
    public function index() {
        $retardos = $this->retardoModel->getPendientesJustificacion();
        include 'views/justificaciones/index.php';
    }

    /**
     * Justificar un retardo
     */
    public function justificar($retardo_id) {
        $retardo = $this->retardoModel->getById($retardo_id);
        if (!$retardo) {
            header('Location: /sistema_biometrico/justificaciones');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../helpers/Csrf.php';
            $csrf = $_POST['csrf_token'] ?? '';
            if (!\Csrf::validate($csrf)) {
                $error = 'Token CSRF inválido. Vuelva a intentarlo.';
            } else {
                // proceed
            }
            
            if (isset($error)) {
                // skip further processing if CSRF invalid
            } else {
            $tipo_justificacion_id = $_POST['tipo_justificacion_id'] ?? null;
            $motivo = trim($_POST['motivo'] ?? '');
            $aprobado_por = $_SESSION['user_id'] ?? null; // Asumiendo que hay sesión de usuario

            // Validar temporalidad: máximo 2 días hábiles desde la fecha del retardo
            $fecha_retardo = strtotime($retardo['fecha']);
            $hoy = strtotime(date('Y-m-d'));

            // Calcular días hábiles entre fecha del retardo y hoy
            $dias_habiles = 0;
            $current = $fecha_retardo;
            while ($current <= $hoy) {
                $dia_semana = date('N', $current);
                if ($dia_semana <= 5) $dias_habiles++;
                $current = strtotime('+1 day', $current);
            }
            $dias_habiles = max(0, $dias_habiles - 1); // no contar el día del retardo

            if ($dias_habiles > 2) {
                $error = 'La justificación supera el periodo máximo de 2 días hábiles.';
            } else {
                // Validar archivo de soporte (requerido)
                $soportePath = null;
                if (!empty($_FILES['soporte']) && $_FILES['soporte']['error'] === UPLOAD_ERR_OK) {
                    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    $fileType = $_FILES['soporte']['type'];
                    $fileTmp = $_FILES['soporte']['tmp_name'];

                    if (!in_array($fileType, $allowed)) {
                        $error = 'Tipo de soporte no permitido. Use PDF o imagen JPG/PNG.';
                    } elseif ($_FILES['soporte']['size'] > $maxSize) {
                        $error = 'El archivo de soporte es demasiado grande (máx. 5MB).';
                    } else {
                        $uploadDir = __DIR__ . '/../uploads/justificaciones/';
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $ext = pathinfo($_FILES['soporte']['name'], PATHINFO_EXTENSION);
                        $newName = 'soporte_retardo_' . $retardo_id . '_' . time() . '.' . $ext;
                        $dest = $uploadDir . $newName;
                        if (move_uploaded_file($fileTmp, $dest)) {
                            $soportePath = 'uploads/justificaciones/' . $newName;
                        } else {
                            $error = 'Error al subir el soporte documental.';
                        }
                    }
                } else {
                    $error = 'Se requiere adjuntar el soporte documental original.';
                }

                if (!isset($error)) {
                    if ($this->retardoModel->justificarRetardo($retardo_id, $tipo_justificacion_id, $motivo, $aprobado_por, $soportePath)) {
                        header('Location: /sistema_biometrico/justificaciones');
                        exit;
                    } else {
                        $error = 'Error al justificar el retardo';
                    }
                }
            }
            }
        }

        $tipos_justificacion = $this->tipoJustificacionModel->getAll();
        include 'views/justificaciones/justificar.php';
    }

    /**
     * Gestionar tipos de justificación
     */
    public function tipos() {
        $tipos = $this->tipoJustificacionModel->getAll();
        include 'views/justificaciones/tipos.php';
    }

    /**
     * Crear nuevo tipo de justificación
     */
    public function crearTipo() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $_POST['nombre'],
                'descripcion' => $_POST['descripcion'] ?? null,
                'requiere_aprobacion' => isset($_POST['requiere_aprobacion']) ? 1 : 0
            ];

            if ($this->tipoJustificacionModel->create($data)) {
                header('Location: /sistema_biometrico/justificaciones/tipos');
                exit;
            } else {
                $error = 'Error al crear el tipo de justificación';
            }
        }

        include 'views/justificaciones/crear_tipo.php';
    }
}
?>
