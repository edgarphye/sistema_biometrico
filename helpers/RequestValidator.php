<?php

require_once __DIR__ . '/SecurityHelper.php';

/**
 * Validador centralizado de inputs
 * Proporciona validación consistente para todo el sistema
 */
class RequestValidator {
    
    /**
     * Valida datos de empleado
     * @param array $data Datos a validar
     * @return array Errores encontrados (vacío si es válido)
     */
    public static function validateEmpleadoData($data) {
        $errors = [];
        
        // Validar nombre
        if (empty($data['nombre']) || strlen(trim($data['nombre'])) < 2) {
            $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $data['nombre'])) {
            $errors['nombre'] = 'El nombre solo puede contener letras y espacios';
        }
        
        // Validar apellido
        if (empty($data['apellido']) || strlen(trim($data['apellido'])) < 2) {
            $errors['apellido'] = 'El apellido debe tener al menos 2 caracteres';
        } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $data['apellido'])) {
            $errors['apellido'] = 'El apellido solo puede contener letras y espacios';
        }
        
        // Validar RFC
        if (empty($data['rfc'])) {
            $errors['rfc'] = 'El RFC es obligatorio';
        } elseif (!SecurityHelper::validateRfc($data['rfc'])) {
            $errors['rfc'] = 'El RFC no tiene un formato válido';
        }
        
        // Validar CURP
        if (!empty($data['curp'])) {
            if (strlen($data['curp']) !== 18) {
                $errors['curp'] = 'La CURP debe tener exactamente 18 caracteres';
            } elseif (!preg_match('/^[A-Z]{4}[0-9]{6}[A-Z]{6}[0-9A-Z]{2}$/', $data['curp'])) {
                $errors['curp'] = 'La CURP no tiene un formato válido';
            }
        }
        
        // Validar área
        if (empty($data['area'])) {
            $errors['area'] = 'El área es obligatoria';
        } elseif (strlen(trim($data['area'])) < 2) {
            $errors['area'] = 'El área debe tener al menos 2 caracteres';
        }
        
        // Validar jerarquía
        if (empty($data['jerarquia'])) {
            $errors['jerarquia'] = 'La jerarquía es obligatoria';
        }
        
        // Validar sexo
        if (!empty($data['sexo']) && !in_array($data['sexo'], ['H', 'M', 'O'])) {
            $errors['sexo'] = 'El sexo debe ser H (Hombre), M (Mujer) u O (Otro)';
        }
        
        // Validar fecha de nacimiento
        if (!empty($data['fecha_nacimiento'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
            if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_nacimiento']) {
                $errors['fecha_nacimiento'] = 'La fecha de nacimiento no es válida';
            } else {
                $edad = (new DateTime())->diff($fecha)->y;
                if ($edad < 18 || $edad > 70) {
                    $errors['fecha_nacimiento'] = 'La edad debe estar entre 18 y 70 años';
                }
            }
        }
        
        // Validar entidad federativa
        if (!empty($data['entidad_federativa'])) {
            $entidadesValidas = [
                'AS', 'BC', 'BS', 'CC', 'CL', 'CM', 'CS', 'CH', 'DF', 'DG',
                'GT', 'GR', 'HG', 'JC', 'MC', 'MN', 'MS', 'NT', 'NL', 'OC',
                'PL', 'QT', 'QR', 'SP', 'SL', 'SR', 'TC', 'TS', 'TL', 'VZ',
                'YN', 'ZS', 'NE'
            ];
            if (!in_array($data['entidad_federativa'], $entidadesValidas)) {
                $errors['entidad_federativa'] = 'La entidad federativa no es válida';
            }
        }
        
        return $errors;
    }
    
    /**
     * Valida datos de usuario
     * @param array $data Datos a validar
     * @param bool $isUpdate Si es una actualización (no requiere contraseña)
     * @return array Errores encontrados
     */
    public static function validateUsuarioData($data, $isUpdate = false) {
        $errors = [];
        
        // Validar nombre de usuario
        if (empty($data['username'])) {
            $errors['username'] = 'El nombre de usuario es obligatorio';
        } elseif (strlen($data['username']) < 4) {
            $errors['username'] = 'El nombre de usuario debe tener al menos 4 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
        }
        
        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es obligatorio';
        } elseif (!SecurityHelper::sanitizeEmail($data['email'])) {
            $errors['email'] = 'El email no es válido';
        }
        
        // Validar contraseña (solo en creación o si se proporciona en actualización)
        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'La contraseña es obligatoria';
            } elseif (!SecurityHelper::validateStrongPassword($data['password'])) {
                $errors['password'] = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y un carácter especial';
            }
            
            // Validar confirmación de contraseña
            if (empty($data['password_confirm'])) {
                $errors['password_confirm'] = 'Debe confirmar la contraseña';
            } elseif ($data['password'] !== $data['password_confirm']) {
                $errors['password_confirm'] = 'Las contraseñas no coinciden';
            }
        }
        
        // Validar rol
        if (empty($data['rol'])) {
            $errors['rol'] = 'El rol es obligatorio';
        } elseif (!in_array($data['rol'], ['admin', 'gerente', 'empleado', 'rh'])) {
            $errors['rol'] = 'El rol no es válido';
        }
        
        return $errors;
    }
    
    /**
     * Valida datos de asistencia
     * @param array $data Datos a validar
     * @return array Errores encontrados
     */
    public static function validateAsistenciaData($data) {
        $errors = [];
        
        // Validar fecha_inicio (para filtros de rango)
        if (!empty($data['fecha_inicio'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_inicio']);
            if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_inicio']) {
                $errors['fecha_inicio'] = 'La fecha de inicio no es válida';
            } elseif ($fecha > new DateTime()) {
                $errors['fecha_inicio'] = 'La fecha de inicio no puede ser futura';
            }
        }
        
        // Validar fecha_fin (para filtros de rango)
        if (!empty($data['fecha_fin'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha_fin']);
            if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha_fin']) {
                $errors['fecha_fin'] = 'La fecha fin no es válida';
            } elseif ($fecha > new DateTime()) {
                $errors['fecha_fin'] = 'La fecha fin no puede ser futura';
            }
        }
        
        // Validar empleado_id (opcional para filtros)
        if (!empty($data['empleado_id']) && !SecurityHelper::sanitizeInt($data['empleado_id'], 1)) {
            $errors['empleado_id'] = 'El ID del empleado no es válido';
        }
        
        // Validar fecha única (para un solo día)
        if (!empty($data['fecha'])) {
            $fecha = DateTime::createFromFormat('Y-m-d', $data['fecha']);
            if (!$fecha || $fecha->format('Y-m-d') !== $data['fecha']) {
                $errors['fecha'] = 'La fecha no es válida';
            } elseif ($fecha > new DateTime()) {
                $errors['fecha'] = 'La fecha no puede ser futura';
            }
        }
        
        // Validar hora de entrada
        if (!empty($data['hora_entrada'])) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['hora_entrada'])) {
                $errors['hora_entrada'] = 'La hora de entrada no es válida (formato HH:MM)';
            }
        }
        
        // Validar hora de salida
        if (!empty($data['hora_salida'])) {
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $data['hora_salida'])) {
                $errors['hora_salida'] = 'La hora de salida no es válida (formato HH:MM)';
            }
            
            // Validar que la salida sea después de la entrada
            if (!empty($data['hora_entrada']) && !empty($errors['hora_entrada']) && !empty($errors['hora_salida'])) {
                $entrada = DateTime::createFromFormat('H:i', $data['hora_entrada']);
                $salida = DateTime::createFromFormat('H:i', $data['hora_salida']);
                if ($salida <= $entrada) {
                    $errors['hora_salida'] = 'La hora de salida debe ser posterior a la hora de entrada';
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Valida datos de área/departamento
     * @param array $data Datos a validar
     * @return array Errores encontrados
     */
    public static function validateAreaData($data) {
        $errors = [];
        
        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre del área es obligatorio';
        } elseif (strlen(trim($data['nombre'])) < 2) {
            $errors['nombre'] = 'El nombre del área debe tener al menos 2 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s\-_]+$/', $data['nombre'])) {
            $errors['nombre'] = 'El nombre del área contiene caracteres no válidos';
        }
        
        // Validar descripción
        if (!empty($data['descripcion']) && strlen(trim($data['descripcion'])) < 10) {
            $errors['descripcion'] = 'La descripción debe tener al menos 10 caracteres si se proporciona';
        }
        
        // Validar responsable
        if (!empty($data['responsable'])) {
            if (strlen(trim($data['responsable'])) < 5) {
                $errors['responsable'] = 'El nombre del responsable debe tener al menos 5 caracteres';
            } elseif (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $data['responsable'])) {
                $errors['responsable'] = 'El nombre del responsable solo puede contener letras y espacios';
            }
        }
        
        return $errors;
    }
    
    /**
     * Valida datos de búsqueda y paginación
     * @param array $data Datos a validar
     * @return array Datos validados y limpios
     */
    public static function validatePaginationData($data) {
        $validated = [];
        
        // Validar página
        $validated['page'] = SecurityHelper::sanitizeInt($data['page'] ?? 1, 1) ?: 1;
        
        // Validar límite
        $validated['limit'] = SecurityHelper::sanitizeInt($data['limit'] ?? 20, 5, 100) ?: 20;
        
        // Validar término de búsqueda
        if (!empty($data['search'])) {
            $validated['search'] = SecurityHelper::sanitizeString($data['search'], 'general');
            if (strlen($validated['search']) < 2) {
                $validated['search'] = '';
            }
        } else {
            $validated['search'] = '';
        }
        
        // Validar filtros
        $validated['area'] = !empty($data['area']) ? SecurityHelper::sanitizeString($data['area'], 'general') : '';
        $validated['jerarquia'] = !empty($data['jerarquia']) ? SecurityHelper::sanitizeString($data['jerarquia'], 'general') : '';
        
        return $validated;
    }
    
    /**
     * Sanitiza y valida un array completo de datos
     * @param array $data Datos a sanitizar
     * @param array $rules Reglas de validación personalizadas
     * @return array Datos sanitizados
     */
    public static function sanitizeAndValidate($data, $rules = []) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = SecurityHelper::escape(trim($value));
            } elseif (is_numeric($value)) {
                $sanitized[$key] = SecurityHelper::sanitizeInt($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}