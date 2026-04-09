<?php
// attendance_principal.php - Portal principal de asistencia con redirección inteligente

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: views/auth/login.php');
    exit;
}

require_once 'config.php';
require_once 'models/Database.php';

// Obtener configuración de preferencia del usuario si existe
$preferencia_vista = $_COOKIE['vista_asistencia'] ?? 'mejorado';

// Función para validar y sanitizar entradas
function validarParametro($parametro, $valor_defecto = '') {
    return isset($_GET[$parametro]) ? htmlspecialchars($_GET[$parametro]) : $valor_defecto;
}

// Modo actual con fallback
$modo = validarParametro('modo', 'mejorado');
$empleado_id = validarParametro('empleado_id', '');
$area_filtro = validarParametro('area', '');
$fecha_filtro = validarParametro('fecha', date('Y-m-d'));
$mostrar_todas = isset($_GET['mostrar_todas']) ? 1 : 0;

// Redirigir a la versión preferida del usuario
switch ($preferencia_vista) {
    case 'mejorado':
        header('Location: asistencia_mejorado.php' . '?' . http_build_query([
            'modo' => $modo,
            'empleado_id' => $empleado_id,
            'area' => $area_filtro,
            'fecha' => $fecha_filtro,
            'mostrar_todas' => $mostrar_todas
        ]));
        break;
        
    case 'simple':
        header('Location: asistencia_simple.php' . '?' . http_build_query([
            'modo' => $modo,
            'empleado_id' => $empleado_id,
            'area' => $area_filtro,
            'fecha' => $fecha_filtro,
            'mostrar_todas' => $mostrar_todas
        ]));
        break;
        
    case 'directo':
        header('Location: asistencia_directo.php' . '?' . http_build_query([
            'modo' => $modo,
            'limite' => '50'
        ]));
        break;
        
    default:
        header('Location: asistencia_mejorado.php' . '?mostrar_todas=' . $mostrar_todas);
        break;
}

// Si no se especificó modo, ir al mejorado por defecto
header('Location: asistencia_mejorado.php');
exit;
?>