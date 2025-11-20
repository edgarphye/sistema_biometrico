<?php
require_once 'models/Empleado.php';
require_once 'models/Asistencia.php';
require_once 'models/Retardo.php';
require_once 'models/Comision.php';
require_once 'models/Ausencia.php';

class EmpleadoController {
    private $empleadoModel;
    private $asistenciaModel;
    private $retardoModel;
    private $comisionModel;
    private $ausenciaModel;

    public function __construct() {
        $this->empleadoModel = new Empleado();
        $this->asistenciaModel = new Asistencia();
        $this->retardoModel = new Retardo();
        $this->comisionModel = new Comision();
        $this->ausenciaModel = new Ausencia();
    }

    public function index() {
        $empleados = $this->empleadoModel->getAll();
        include 'views/empleados/index.php';
    }

    public function show($id) {
        $empleado = $this->empleadoModel->getById($id);
        if (!$empleado) {
            header('Location: /sistema_biometrico/empleados');
            exit;
        }

        $asistencias = $this->asistenciaModel->getByEmpleado($id);
        $retardos = $this->retardoModel->getByEmpleado($id);
        $comisiones = $this->comisionModel->getByEmpleado($id);
        $ausencias = $this->ausenciaModel->getByEmpleado($id);

        include 'views/empleados/show.php';
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $foto_cara = null;
            $huella_dactilar = null;

            // Procesar imagen si se subió
            if (isset($_FILES['foto_cara']) && $_FILES['foto_cara']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto_cara']['tmp_name'];
                $fileName = $_FILES['foto_cara']['name'];
                $fileSize = $_FILES['foto_cara']['size'];
                $fileType = $_FILES['foto_cara']['type'];

                // Validar tipo de archivo
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    $error = "Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF.";
                } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB máximo
                    $error = "El archivo es demasiado grande. Máximo 2MB.";
                } else {
                    // Generar nombre único para el archivo
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = uniqid('empleado_', true) . '.' . $fileExtension;
                    $uploadPath = 'uploads/fotos_empleados/' . $newFileName;

                    // Mover archivo a la carpeta de uploads
                    if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                        $foto_cara = $uploadPath; // Guardar la ruta del archivo
                    } else {
                        $error = "Error al subir la imagen.";
                    }
                }
            }

            // Procesar registro de huella si se solicita
            if (isset($_POST['registrar_huella']) && $_POST['registrar_huella'] === '1') {
                $dispositivoId = (int)($_POST['dispositivo_huella'] ?? 1);

                // Incluir modelo biométrico
                require_once 'models/biometric/BiometricSimulation.php';
                $biometricoModel = new BiometricSimulation();

                // Capturar huella del dispositivo
                $huellaData = $biometricoModel->captureFingerprint($dispositivoId);

                if ($huellaData) {
                    $huella_dactilar = $huellaData;
                } else {
                    $error = "Error al capturar la huella dactilar del dispositivo.";
                }
            }

            if (!isset($error)) {
                $data = [
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'rfc' => $_POST['rfc'],
                    'curp' => $_POST['curp'],
                    'area' => $_POST['area'],
                    'jerarquia' => $_POST['jerarquia'],
                    'foto_cara' => $foto_cara,
                    'huella_dactilar' => $huella_dactilar
                ];

                try {
                    $empleadoId = $this->empleadoModel->create($data);

                    if ($empleadoId) {
                        // Registrar empleado en dispositivos biométricos si se capturó huella
                        if ($huella_dactilar && isset($dispositivoId)) {
                            require_once 'models/DispositivoBiometrico.php';
                            $dispositivoModel = new DispositivoBiometrico();
                            $dispositivoModel->registrarEmpleadoEnDispositivo($dispositivoId, $empleadoId, 'huella');
                        }

                        header('Location: /sistema_biometrico/empleados');
                        exit;
                    } else {
                        $error = 'Error al guardar el empleado en la base de datos';
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = 'Ya existe un empleado con el mismo RFC o CURP';
                    } else {
                        $error = 'Error al guardar el empleado: ' . $e->getMessage();
                    }
                }
            }
        }

        // Obtener dispositivos biométricos para el formulario
        require_once 'models/DispositivoBiometrico.php';
        $dispositivoModel = new DispositivoBiometrico();
        $dispositivos = $dispositivoModel->getActivos();

        include 'views/empleados/create.php';
    }

    public function edit($id) {
        $empleado = $this->empleadoModel->getById($id);
        if (!$empleado) {
            header('Location: /sistema_biometrico/empleados');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $foto_cara = $empleado['foto_cara']; // Mantener la foto existente por defecto

            // Procesar nueva imagen si se subió
            if (isset($_FILES['foto_cara']) && $_FILES['foto_cara']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['foto_cara']['tmp_name'];
                $fileName = $_FILES['foto_cara']['name'];
                $fileSize = $_FILES['foto_cara']['size'];
                $fileType = $_FILES['foto_cara']['type'];

                // Validar tipo de archivo
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($fileType, $allowedTypes)) {
                    $error = "Tipo de archivo no permitido. Solo se permiten JPG, PNG y GIF.";
                } elseif ($fileSize > 2 * 1024 * 1024) { // 2MB máximo
                    $error = "El archivo es demasiado grande. Máximo 2MB.";
                } else {
                    // Generar nombre único para el archivo
                    $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = uniqid('empleado_', true) . '.' . $fileExtension;
                    $uploadPath = 'uploads/fotos_empleados/' . $newFileName;

                    // Mover archivo a la carpeta de uploads
                    if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                        // Eliminar foto anterior si existe
                        if ($foto_cara && file_exists($foto_cara)) {
                            unlink($foto_cara);
                        }
                        $foto_cara = $uploadPath; // Guardar la nueva ruta del archivo
                    } else {
                        $error = "Error al subir la imagen.";
                    }
                }
            }

            if (!isset($error)) {
                $data = [
                    'nombre' => $_POST['nombre'],
                    'apellido' => $_POST['apellido'],
                    'rfc' => $_POST['rfc'],
                    'curp' => $_POST['curp'],
                    'area' => $_POST['area'],
                    'jerarquia' => $_POST['jerarquia'],
                    'foto_cara' => $foto_cara
                ];

                if ($this->empleadoModel->update($id, $data)) {
                    header('Location: /sistema_biometrico/empleados');
                    exit;
                } else {
                    $error = 'Error al actualizar el empleado en la base de datos';
                }
            }
        }

        include 'views/empleados/edit.php';
    }

    public function delete($id) {
        // Obtener empleado para eliminar su foto
        $empleado = $this->empleadoModel->getById($id);
        if ($empleado && $empleado['foto_cara'] && file_exists($empleado['foto_cara'])) {
            unlink($empleado['foto_cara']);
        }

        if ($this->empleadoModel->delete($id)) {
            // Redirigir a la lista de empleados con mensaje de éxito
            header('Location: /sistema_biometrico/empleados?deleted=1');
            exit;
        } else {
            // En caso de error, redirigir con mensaje de error
            header('Location: /sistema_biometrico/empleados?error=delete_failed');
            exit;
        }
    }

    public function generate_rfc() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos JSON inválidos']);
            return;
        }

        try {
            $rfc = $this->empleadoModel->generateRFC(
                $input['fecha_nacimiento'],
                $input['primer_apellido'],
                $input['segundo_apellido'],
                $input['nombres'], 
                $input['sexo'], 
                $input['entidad_federativa']
            );

            echo json_encode(['success' => true, 'rfc' => $rfc]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function generate_curp() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos JSON inválidos']);
            return;
        }

        try {
            $curp = $this->empleadoModel->generateCURP(
                $input['fecha_nacimiento'],
                $input['primer_apellido'],
                $input['segundo_apellido'],
                $input['nombres'],
                $input['sexo'],
                $input['entidad_federativa']
            );

            echo json_encode(['success' => true, 'curp' => $curp]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
?>
