<?php
require_once __DIR__ . '/../models/Ciclo.php';
require_once __DIR__ . '/BaseController.php';

class CiclosController extends BaseController {
    private $cicloModel;

    public function __construct() {
        parent::__construct();
        $this->cicloModel = new Ciclo();
    }

    protected function requireAuth() {
        if (!isset($_SESSION['user_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'No autorizado - Sesión requerida']);
            exit;
        }
        return true;
    }

    public function index() {
        $this->requireAuth();
        require __DIR__ . '/../views/ciclos/index.php';
    }

    public function getAllJson() {
        header('Content-Type: application/json');
        try {
            $ciclos = $this->cicloModel->getAll();
            echo json_encode(['success' => true, 'data' => $ciclos]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function create() {
        $this->requireAuth();
        $this->saveCicloInternal();
    }

    public function update() {
        $this->requireAuth();
        $this->saveCicloInternal();
    }

    private function saveCicloInternal() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            if (isset($data['id']) && $data['id']) {
                $result = $this->cicloModel->update($data['id'], $data);
            } else {
                $result = $this->cicloModel->create($data);
            }
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function delete() {
        $this->requireAuth();
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        
        try {
            if (isset($data['id'])) {
                $result = $this->cicloModel->delete($data['id']);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getBloquesJson($id) {
        header('Content-Type: application/json');
        
        try {
            $bloques = $this->cicloModel->getBloques((int)$id);
            echo json_encode(['success' => true, 'data' => $bloques]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function getHorariosCatalogo() {
        header('Content-Type: application/json');
        try {
            $horarios = $this->cicloModel->getAllHorarios();
            echo json_encode(['success' => true, 'data' => $horarios]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function addBloquesDesdeHorario() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);

        try {
            $result = $this->cicloModel->addBloquesDesdeHorario(
                $data['ciclo_id'],
                $data['horario_id'],
                $data['dias'],
                $data['limpiar_dia'] ?? false
            );
            if (!$result) {
                throw new Exception("Error al guardar o solapamiento detectado.");
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteBloque() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            if (isset($data['id'])) {
                $result = $this->cicloModel->deleteBloque($data['id']);
                echo json_encode(['success' => $result]);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID requerido']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function deleteAllBloques() {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        try {
            $result = $this->cicloModel->deleteAllBloques($data['ciclo_id']);
            echo json_encode(['success' => $result]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}