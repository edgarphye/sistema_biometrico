<?php
/**
 * Helper de Permisos
 * Funciones para verificar permisos del usuario actual
 */

if (!function_exists('tienePermiso')) {
    function tienePermiso($permiso) {
        return Usuario::tienePermiso($permiso);
    }
}

if (!function_exists('puedeCrear')) {
    function puedeCrear($modulo) {
        return Usuario::tienePermiso($modulo . '_crear');
    }
}

if (!function_exists('puedeEditar')) {
    function puedeEditar($modulo) {
        return Usuario::tienePermiso($modulo . '_editar');
    }
}

if (!function_exists('puedeEliminar')) {
    function puedeEliminar($modulo) {
        return Usuario::tienePermiso($modulo . '_eliminar');
    }
}

if (!function_exists('puedeVer')) {
    function puedeVer($modulo) {
        return Usuario::tienePermiso($modulo) || Usuario::tienePermiso($modulo . '_ver');
    }
}

if (!function_exists('puedeAprobar')) {
    function puedeAprobar($modulo) {
        return Usuario::tienePermiso($modulo . '_aprobar');
    }
}

if (!function_exists('puedeRechazar')) {
    function puedeRechazar($modulo) {
        return Usuario::tienePermiso($modulo . '_rechazar');
    }
}

if (!function_exists('puedeExportar')) {
    function puedeExportar($modulo) {
        return Usuario::tienePermiso($modulo . '_exportar') || Usuario::tienePermiso($modulo . '_export');
    }
}

if (!function_exists('mostrarBotonCrear')) {
    function mostrarBotonCrear($modulo, $label = 'Nuevo', $icon = 'plus') {
        if (!puedeCrear($modulo)) return '';
        return '<a href="' . BASE_URL . '/' . $modulo . '/create" class="btn btn-pantone-primary btn-sm">
            <i class="fas fa-' . $icon . '"></i> ' . $label . '
        </a>';
    }
}

if (!function_exists('mostrarBotonEditar')) {
    function mostrarBotonEditar($modulo, $id, $label = '') {
        if (!puedeEditar($modulo)) return '';
        $label = $label ?: 'Editar';
        return '<a href="' . BASE_URL . '/' . $modulo . '/' . $id . '/edit" class="btn btn-outline-primary btn-sm" title="' . $label . '">
            <i class="fas fa-edit"></i>
        </a>';
    }
}

if (!function_exists('mostrarBotonEliminar')) {
    function mostrarBotonEliminar($modulo, $id, $label = '') {
        if (!puedeEliminar($modulo)) return '';
        $label = $label ?: 'Eliminar';
        return '<button class="btn btn-outline-danger btn-sm" onclick="eliminar' . ucfirst($modulo) . '(' . $id . ')" title="' . $label . '">
            <i class="fas fa-trash"></i>
        </button>';
    }
}

if (!function_exists('mostrarBotonesAccion')) {
    function mostrarBotonesAccion($modulo, $id) {
        $html = '<div class="btn-group btn-group-sm">';
        $html .= mostrarBotonEditar($modulo, $id);
        $html .= mostrarBotonEliminar($modulo, $id);
        $html .= '</div>';
        return $html;
    }
}
