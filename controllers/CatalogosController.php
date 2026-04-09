<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Catalogo.php';

class CatalogosController extends BaseController
{
    private $catalogo;

    public function __construct()
    {
        parent::__construct();
        $this->requireAuth();
        $this->catalogo = new Catalogo();
    }

    public function index()
    {
        $this->requireAuth();
        
        $direcciones = $this->catalogo->getAllDirecciones();
        $subdirecciones = $this->catalogo->getAllSubdirecciones();
        $departamentos = $this->catalogo->getAllDepartamentos();
        $mandos = $this->catalogo->getAllMandos();
        
        include __DIR__ . '/../views/catalogos/index.php';
    }

    // ==================== DIRECCIONES ====================
    
    public function guardarDireccion()
    {
        $this->requireAuth();
        
        $data = [
            'clave_dir' => $_POST['clave_dir'] ?? '',
            'clave_dir2' => $_POST['clave_dir2'] ?? '',
            'nombre_direccion' => $_POST['nombre_direccion'] ?? '',
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        if (empty($data['clave_dir']) || empty($data['nombre_direccion'])) {
            $_SESSION['error'] = 'Los campos clave y nombre son requeridos';
            $this->redirect('/catalogos');
            return;
        }
        
        $exists = $this->catalogo->getDireccion($data['clave_dir']);
        if ($exists) {
            $this->catalogo->updateDireccion($data['clave_dir'], $data);
        } else {
            $this->catalogo->createDireccion($data);
        }
        
        $_SESSION['success'] = 'Dirección guardada correctamente';
        $this->redirect('/catalogos');
    }

    public function eliminarDireccion()
    {
        $this->requireAuth();
        
        $clave_dir = $_POST['clave_dir'] ?? '';
        $result = $this->catalogo->deleteDireccion($clave_dir);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Dirección eliminada correctamente';
        } else {
            $_SESSION['error'] = $result['error'];
        }
        
        $this->redirect('/catalogos');
    }

    // ==================== SUBDIRECCIONES ====================
    
    public function guardarSubdireccion()
    {
        $this->requireAuth();
        
        $data = [
            'clave_subdir' => $_POST['clave_subdir'] ?? '',
            'clave_dir' => $_POST['clave_dir'] ?? '',
            'nombre_subdir' => $_POST['nombre_subdir'] ?? '',
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        if (empty($data['clave_subdir']) || empty($data['nombre_subdir']) || empty($data['clave_dir'])) {
            $_SESSION['error'] = 'Todos los campos son requeridos';
            $this->redirect('/catalogos');
            return;
        }
        
        $exists = $this->catalogo->getSubdireccion($data['clave_subdir']);
        if ($exists) {
            $this->catalogo->updateSubdireccion($data['clave_subdir'], $data);
        } else {
            $this->catalogo->createSubdireccion($data);
        }
        
        $_SESSION['success'] = 'Subdirección guardada correctamente';
        $this->redirect('/catalogos');
    }

    public function eliminarSubdireccion()
    {
        $this->requireAuth();
        
        $clave_subdir = $_POST['clave_subdir'] ?? '';
        $result = $this->catalogo->deleteSubdireccion($clave_subdir);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Subdirección eliminada correctamente';
        } else {
            $_SESSION['error'] = $result['error'];
        }
        
        $this->redirect('/catalogos');
    }

    // ==================== DEPARTAMENTOS ====================
    
    public function guardarDepartamento()
    {
        $this->requireAuth();
        
        $data = [
            'clave_depto' => $_POST['clave_depto'] ?? '',
            'clave_sub' => $_POST['clave_sub'] ?? '',
            'nombre_depto' => $_POST['nombre_departamento'] ?? '',
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        if (empty($data['clave_depto']) || empty($data['nombre_depto']) || empty($data['clave_sub'])) {
            $_SESSION['error'] = 'Todos los campos son requeridos';
            $this->redirect('/catalogos');
            return;
        }
        
        $exists = $this->catalogo->getDepartamento($data['clave_depto']);
        if ($exists) {
            $this->catalogo->updateDepartamento($data['clave_depto'], $data);
        } else {
            $this->catalogo->createDepartamento($data);
        }
        
        $_SESSION['success'] = 'Departamento guardado correctamente';
        $this->redirect('/catalogos');
    }

    public function eliminarDepartamento()
    {
        $this->requireAuth();
        
        $clave_depto = $_POST['clave_depto'] ?? '';
        $result = $this->catalogo->deleteDepartamento($clave_depto);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Departamento eliminado correctamente';
        } else {
            $_SESSION['error'] = $result['error'];
        }
        
        $this->redirect('/catalogos');
    }

    // ==================== API PARA COMBOS ====================
    
    public function getSubdirecciones()
    {
        $this->requireAuth();
        
        $clave_dir = $_GET['clave_dir'] ?? null;
        $subdirecciones = $this->catalogo->getSubdireccionesForCombo($clave_dir);
        
        $this->jsonResponse($subdirecciones);
    }

    public function getDepartamentos()
    {
        $this->requireAuth();
        
        $clave_sub = $_GET['clave_sub'] ?? null;
        $departamentos = $this->catalogo->getDepartamentosForCombo($clave_sub);
        
        $this->jsonResponse($departamentos);
    }
    
    public function guardarMando()
    {
        $this->requireAuth();
        
        $data = [
            'clave_area' => $_POST['clave_area'] ?? '',
            'nombre_mando' => $_POST['nombre_mando'] ?? '',
            'area' => $_POST['area'] ?? '',
            'clave_depto' => $_POST['clave_depto'] ?? null,
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];
        
        if (empty($data['clave_area']) || empty($data['nombre_mando'])) {
            $_SESSION['error'] = 'Los campos clave y nombre son requeridos';
            $this->redirect('/catalogos');
            return;
        }
        
        $exists = $this->catalogo->getMando($data['clave_area']);
        
        if ($exists) {
            $this->catalogo->updateMando($data['clave_area'], $data);
        } else {
            $this->catalogo->createMando($data);
        }
        
        $this->redirect('/catalogos');
    }
    
    public function eliminarMando()
    {
        $this->requireAuth();
        
        $clave_area = $_POST['clave_area'] ?? $_GET['clave_area'] ?? '';
        
        if (empty($clave_area)) {
            $_SESSION['error'] = 'Clave de área no proporcionada';
            $this->redirect('/catalogos');
            return;
        }
        
        $result = $this->catalogo->deleteMando($clave_area);
        
        $this->redirect('/catalogos');
    }
}
