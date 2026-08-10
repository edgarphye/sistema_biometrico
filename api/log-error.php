<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $mensaje = $data['mensaje'] ?? $_POST['mensaje'] ?? '';
    $tipo = $data['tipo'] ?? $_POST['tipo'] ?? 'error';
    $url = $data['url'] ?? $_POST['url'] ?? '';
    
    error_log("[log-error] [$tipo] $mensaje - URL: $url");
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}