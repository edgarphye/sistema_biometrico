<?php
require_once 'config.php';
require_once 'models/Database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "Actualizando estructura de la base de datos...\n";

    // Establecer motor y charset por defecto
    $conn->exec('SET default_storage_engine=InnoDB');
    $conn->exec('SET default_charset=utf8mb4');

    // =========================================================================
    // 1. ACTUALIZAR ESTRUCTURA DE TABLA ASISTENCIA
    // =========================================================================

    // Agregar columnas biométricas a la tabla asistencia (solo si no existen)
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN tipo_biometria ENUM("huella", "cara") NULL AFTER dispositivo_id');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN datos_biometricos JSON NULL AFTER tipo_biometria');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN calidad_verificacion TINYINT NULL CHECK (calidad_verificacion BETWEEN 0 AND 100) AFTER datos_biometricos');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN metadata_dispositivo JSON NULL AFTER calidad_verificacion');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }
    try {
        $conn->exec('ALTER TABLE asistencia ADD COLUMN tiempo_procesamiento DECIMAL(5,3) NULL CHECK (tiempo_procesamiento >= 0) AFTER metadata_dispositivo');
    } catch (Exception $e) {
        // Columna ya existe, continuar
    }

    // =========================================================================
    // 2. ACTUALIZAR TABLA SANCIONES COMPLETA
    // =========================================================================

    // Crear/actualizar tabla sanciones con estructura completa
    $conn->exec('
        CREATE TABLE IF NOT EXISTS sanciones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            tipo_sancion ENUM("amonestacion", "suspension", "acta_administrativa", "terminacion_propuesta", "otro") NOT NULL,
            motivo TEXT NOT NULL,
            fecha_sancion DATE NOT NULL,
            fecha_inicio DATE NULL,
            fecha_fin DATE NULL,
            dias INT DEFAULT 0 CHECK (dias >= 0),
            estatus ENUM("activa", "cumplida", "cancelada") DEFAULT "activa",
            soporte VARCHAR(255) NULL,
            creado_por INT NULL,
            modified_by INT NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_sanciones_empleado (empleado_id),
            INDEX idx_sanciones_estatus (estatus),
            INDEX idx_sanciones_fecha (fecha_sancion),
            INDEX idx_sanciones_tipo (tipo_sancion),
            INDEX idx_sanciones_modified_by (modified_by),
            INDEX idx_sanciones_fecha_modificacion (fecha_modificacion),
            
            CONSTRAINT fk_sanciones_empleado 
                FOREIGN KEY (empleado_id) 
                REFERENCES empleados(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_sanciones_creado_por 
                FOREIGN KEY (creado_por) 
                REFERENCES usuarios(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_sanciones_modified_by 
                FOREIGN KEY (modified_by) 
                REFERENCES usuarios(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE,
                
            CONSTRAINT chk_sanciones_fechas 
                CHECK ((fecha_inicio IS NULL AND fecha_fin IS NULL) OR (fecha_inicio <= fecha_fin))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // =========================================================================
    // 3. ACTUALIZAR TABLA RETARDOS CON SOPORTE Y AUDIENCIA
    // =========================================================================

    // Agregar columnas a tabla retardos
    $alterRetardos = [
        'soporte VARCHAR(255) NULL',
        'horario_id INT NULL',
        'tipo_justificacion_id INT NULL', 
        'motivo_justificacion TEXT NULL',
        'aprobado_por INT NULL',
        'fecha_aprobacion TIMESTAMP NULL'
    ];

    foreach ($alterRetardos as $column) {
        try {
            $conn->exec("ALTER TABLE retardos ADD COLUMN IF NOT EXISTS $column");
        } catch (Exception $e) {
            // Columna ya existe, continuar
        }
    }

    // =========================================================================
    // 4. ACTUALIZAR TABLA DISPOSITIVOS BIOMÉTRICOS COMPLETA
    // =========================================================================

    $conn->exec('
        CREATE TABLE IF NOT EXISTS dispositivos_biometricos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo_id INT UNIQUE NOT NULL COMMENT "ID único del dispositivo",
            nombre VARCHAR(100) NOT NULL COMMENT "Nombre descriptivo del dispositivo",
            tipo ENUM("huella", "cara", "dual") NOT NULL DEFAULT "dual",
            marca VARCHAR(50) NULL,
            modelo VARCHAR(50) NULL,
            ip_address VARCHAR(45) NOT NULL COMMENT "Dirección IP del dispositivo (IPv4 o IPv6)",
            puerto SMALLINT DEFAULT 4370,
            usuario VARCHAR(50) NULL,
            password VARCHAR(255) NULL,
            activo BOOLEAN DEFAULT TRUE,
            sede VARCHAR(100) NULL,
            capacidades JSON COMMENT "Capacidades del dispositivo: {\"huella\": true, \"cara\": true}",
            configuracion JSON NULL,
            fecha_instalacion DATE NULL,
            ultima_conexion TIMESTAMP NULL,
            ultima_sincronizacion DATETIME NULL,
            estado ENUM("conectado", "desconectado", "error") DEFAULT "desconectado",
            firmware_version VARCHAR(20) NULL,
            notas TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_dispositivos_sede (sede),
            INDEX idx_dispositivos_activo (activo),
            INDEX idx_dispositivos_ip (ip_address),
            INDEX idx_dispositivos_tipo (tipo),
            INDEX idx_dispositivos_estado (estado),
            
            CONSTRAINT chk_dispositivos_puerto 
                CHECK (puerto > 0 AND puerto <= 65535),
                
            CONSTRAINT chk_dispositivos_ip_formato 
                CHECK (ip_address REGEXP "^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$" OR 
                       ip_address REGEXP "^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$")
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // =========================================================================
    // 5. TABLA DE LOGS DE DISPOSITIVOS MEJORADA
    // =========================================================================

    $conn->exec('
        CREATE TABLE IF NOT EXISTS logs_dispositivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dispositivo_id INT NOT NULL,
            tipo_evento ENUM("conexion", "desconexion", "error", "mantenimiento", "verificacion", "sincronizacion") NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            empleado_id INT NULL,
            tipo_biometria ENUM("huella", "cara") NULL,
            resultado ENUM("exitoso", "fallido", "error") NOT NULL,
            mensaje TEXT NULL,
            metadata JSON NULL,
            tiempo_respuesta DECIMAL(8,3) NULL COMMENT "Tiempo de respuesta en milisegundos",
            
            INDEX idx_logs_dispositivo (dispositivo_id),
            INDEX idx_logs_fecha (timestamp),
            INDEX idx_logs_empleado (empleado_id),
            INDEX idx_logs_evento (tipo_evento),
            INDEX idx_logs_resultado (resultado),
            INDEX idx_logs_compuesto (dispositivo_id, timestamp, resultado),
            
            CONSTRAINT fk_logs_dispositivo 
                FOREIGN KEY (dispositivo_id) 
                REFERENCES dispositivos_biometricos(dispositivo_id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_logs_empleado 
                FOREIGN KEY (empleado_id) 
                REFERENCES empleados(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE,
                
            CONSTRAINT chk_logs_tiempo_respuesta 
                CHECK (tiempo_respuesta IS NULL OR tiempo_respuesta >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // =========================================================================
    // 6. TABLAS DE HORARIOS CON SOPORTE MULTI-SEDE
    // =========================================================================

    // Crear tabla de horarios laborales
    $conn->exec('
        CREATE TABLE IF NOT EXISTS horarios_laborales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            hora_entrada TIME NOT NULL,
            hora_salida TIME NOT NULL,
            tolerancia_minutos INT DEFAULT 10,
            descripcion TEXT NULL,
            activo BOOLEAN DEFAULT TRUE,
            sede VARCHAR(100) NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_horario_sede (sede),
            INDEX idx_horario_activo (activo),
            INDEX idx_horario_nombre (nombre),
            
            CONSTRAINT chk_horario_horas 
                CHECK (hora_entrada < hora_salida),
                
            CONSTRAINT chk_horario_tolerancia 
                CHECK (tolerancia_minutos >= 0 AND tolerancia_minutos <= 60)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // Crear tabla de horarios por empleado
    $conn->exec('
        CREATE TABLE IF NOT EXISTS horarios_empleados (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            horario_id INT NOT NULL,
            dia_semana ENUM("lunes", "martes", "miercoles", "jueves", "viernes", "sabado", "domingo") NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            sede VARCHAR(100) NULL,
            fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_horarios_empleados_empleado (empleado_id),
            INDEX idx_horarios_empleados_dia (dia_semana),
            INDEX idx_horarios_empleados_horario (horario_id),
            INDEX idx_horarios_empleados_sede (sede),
            INDEX idx_horarios_empleados_activo (activo),
            
            UNIQUE KEY unique_empleado_dia_sede (empleado_id, dia_semana, sede),
            
            CONSTRAINT fk_horarios_empleado 
                FOREIGN KEY (empleado_id) 
                REFERENCES empleados(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_horarios_horario 
                FOREIGN KEY (horario_id) 
                REFERENCES horarios_laborales(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // Tabla de asignaciones de horarios a empleados (historial con fechas)
    $conn->exec('
        CREATE TABLE IF NOT EXISTS empleado_horarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            horario_id INT DEFAULT NULL,
            ciclo_id INT DEFAULT NULL,
            fecha_inicio DATE NOT NULL,
            fecha_fin DATE DEFAULT NULL,
            fecha_asignacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_emp_horarios_empleado (empleado_id),
            INDEX idx_emp_horarios_fecha (fecha_inicio, fecha_fin),
            INDEX idx_emp_horarios_horario (horario_id),
            INDEX idx_emp_horarios_ciclo (ciclo_id),
            
            CONSTRAINT fk_emp_horarios_empleado 
                FOREIGN KEY (empleado_id) 
                REFERENCES empleados(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_emp_horarios_horario 
                FOREIGN KEY (horario_id) 
                REFERENCES horarios_laborales(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_emp_horarios_ciclo 
                FOREIGN KEY (ciclo_id) 
                REFERENCES ciclos(id) 
                ON DELETE SET NULL 
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // =========================================================================
    // 7. TABLAS DE CICLOS Y BLOQUES (PARA TURNOS ROTATIVOS)
    // =========================================================================

    $conn->exec('
        CREATE TABLE IF NOT EXISTS ciclos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            fecha_inicio DATE NOT NULL,
            num_ciclo INT DEFAULT 1,
            unidad_ciclo ENUM("Semana", "Mes") DEFAULT "Semana",
            activo BOOLEAN DEFAULT TRUE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_ciclos_nombre (nombre),
            INDEX idx_ciclos_fecha_inicio (fecha_inicio),
            INDEX idx_ciclos_activo (activo),
            
            CONSTRAINT chk_ciclos_num_positivo 
                CHECK (num_ciclo > 0),
                
            CONSTRAINT chk_ciclos_fechas 
                CHECK (fecha_inicio <= DATE_ADD(CURRENT_DATE, INTERVAL 5 YEAR))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $conn->exec('
        CREATE TABLE IF NOT EXISTS bloques_ciclo (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ciclo_id INT NOT NULL,
            dia_semana TINYINT NOT NULL COMMENT "0=Lunes, 1=Martes, ..., 6=Domingo",
            hora_inicio TIME NOT NULL,
            hora_fin TIME NOT NULL,
            horario_id INT NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_bloques_ciclo_ciclo (ciclo_id),
            INDEX idx_bloques_ciclo_dia (ciclo_id, dia_semana),
            INDEX idx_bloques_ciclo_horario (horario_id),
            
            CONSTRAINT fk_bloques_ciclo_ciclo 
                FOREIGN KEY (ciclo_id) 
                REFERENCES ciclos(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_bloques_ciclo_horario 
                FOREIGN KEY (horario_id) 
                REFERENCES horarios_laborales(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE,
                
            CONSTRAINT chk_bloques_ciclo_dia 
                CHECK (dia_semana BETWEEN 0 AND 6),
                
            CONSTRAINT chk_bloques_ciclo_horas 
                CHECK (hora_inicio < hora_fin)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $conn->exec('
        CREATE TABLE IF NOT EXISTS empleados_ciclos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            empleado_id INT NOT NULL,
            ciclo_id INT NOT NULL,
            fecha_asignacion DATE NOT NULL,
            activo BOOLEAN DEFAULT TRUE,
            tipo_ciclo VARCHAR(20) DEFAULT NULL,
            
            UNIQUE KEY unique_empleado_ciclo (empleado_id, ciclo_id),
            
            INDEX idx_empleados_ciclos_empleado (empleado_id),
            INDEX idx_empleados_ciclos_ciclo (ciclo_id),
            
            CONSTRAINT fk_empleados_ciclos_empleado 
                FOREIGN KEY (empleado_id) 
                REFERENCES empleados(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE,
                
            CONSTRAINT fk_empleados_ciclos_ciclo 
                FOREIGN KEY (ciclo_id) 
                REFERENCES ciclos(id) 
                ON DELETE CASCADE 
                ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    
    // Agregar columna tipo_ciclo si no existe
    try {
        $conn->exec("ALTER TABLE empleados_ciclos ADD COLUMN tipo_ciclo VARCHAR(20) DEFAULT NULL AFTER activo");
    } catch (Exception $e) {
        // La columna ya existe, continuar
    }

    // =========================================================================
    // 8. TABLAS ADICIONALES CON ÍNDICES Y FOREIGN KEYS
    // =========================================================================

    // Crear tabla de tipos de justificación
    $conn->exec('
        CREATE TABLE IF NOT EXISTS tipos_justificacion (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT NULL,
            requiere_aprobacion BOOLEAN DEFAULT TRUE,
            requiere_documento BOOLEAN DEFAULT FALSE,
            activo BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_tipos_justificacion_nombre (nombre),
            INDEX idx_tipos_justificacion_activo (activo),
            
            UNIQUE KEY unique_tipo_justificacion_nombre (nombre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    // Agregar campo requiere_documento si no existe
    try {
        $conn->exec('ALTER TABLE tipos_justificacion ADD COLUMN requiere_documento BOOLEAN DEFAULT FALSE AFTER requiere_aprobacion');
    } catch (Exception $e) {
        // Campo ya existe
    }

    // Actualizar tabla comisiones con estructura completa
    $alterComisiones = [
        'tipo_comision VARCHAR(50) DEFAULT "otros"',
        'requiere_aprobacion BOOLEAN DEFAULT TRUE',
        'aprobado_por INT NULL',
        'fecha_aprobacion TIMESTAMP NULL',
        'motivo_aprobacion TEXT NULL',
        'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];

    foreach ($alterComisiones as $column) {
        try {
            $conn->exec("ALTER TABLE comisiones ADD COLUMN IF NOT EXISTS $column");
        } catch (Exception $e) {
            // Columna ya existe, continuar
        }
    }

    // =========================================================================
    // 9. AGREGAR FOREIGN KEYS FALTANTES A RETARDOS
    // =========================================================================

    $foreignKeysRetardos = [
        'fk_retardos_horario' => 'ALTER TABLE retardos ADD CONSTRAINT IF NOT EXISTS fk_retardos_horario FOREIGN KEY (horario_id) REFERENCES horarios_laborales(id) ON DELETE SET NULL ON UPDATE CASCADE',
        'fk_retardos_tipo_just' => 'ALTER TABLE retardos ADD CONSTRAINT IF NOT EXISTS fk_retardos_tipo_just FOREIGN KEY (tipo_justificacion_id) REFERENCES tipos_justificacion(id) ON DELETE SET NULL ON UPDATE CASCADE',
        'fk_retardos_aprobado_por' => 'ALTER TABLE retardos ADD CONSTRAINT IF NOT EXISTS fk_retardos_aprobado_por FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE'
    ];

    foreach ($foreignKeysRetardos as $name => $sql) {
        try {
            $conn->exec($sql);
        } catch (Exception $e) {
            // Foreign key ya existe, continuar
        }
    }

    // =========================================================================
    // 10. AGREGAR FOREIGN KEYS FALTANTES A COMISIONES
    // =========================================================================

    try {
        $conn->exec('ALTER TABLE comisiones ADD CONSTRAINT IF NOT EXISTS fk_comisiones_aprobado_por FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE');
    } catch (Exception $e) {
        // Foreign key ya existe, continuar
    }

    // =========================================================================
    // 11. CREAR ÍNDICES COMPREHENSIVOS PARA RENDIMIENTO
    // =========================================================================

    // Índices para tabla asistencia
    $indicesAsistencia = [
        'idx_asistencia_empleado' => 'asistencia(empleado_id)',
        'idx_asistencia_fecha' => 'asistencia(fecha)',
        'idx_asistencia_dispositivo' => 'asistencia(dispositivo_id)',
        'idx_asistencia_tipo_biometria' => 'asistencia(tipo_biometria)',
        'idx_asistencia_compuesto' => 'asistencia(empleado_id, fecha)',
        'idx_asistencia_dispositivo_fecha' => 'asistencia(dispositivo_id, created_at)',
        'idx_asistencia_entrada' => 'asistencia(hora_entrada)',
        'idx_asistencia_salida' => 'asistencia(hora_salida)'
    ];

    // Índices para tabla retardos
    $indicesRetardos = [
        'idx_retardos_empleado' => 'retardos(empleado_id)',
        'idx_retardos_fecha' => 'retardos(fecha)',
        'idx_retardos_tipo' => 'retardos(tipo_retraso)',
        'idx_retardos_horario' => 'retardos(horario_id)',
        'idx_retardos_compuesto' => 'retardos(empleado_id, fecha)',
        'idx_retardos_aprobacion' => 'retardos(aprobado_por, fecha_aprobacion)',
        'idx_retardos_justificacion' => 'retardos(tipo_justificacion_id)'
    ];

    // Índices para tabla empleados
    $indicesEmpleados = [
        'idx_empleados_activo' => 'empleados(activo)',
        'idx_empleados_area' => 'empleados(area)',
        'idx_empleados_puesto' => 'empleados(puesto)',
        'idx_empleados_jerarquia' => 'empleados(jerarquia)',
        'idx_empleados_rfc' => 'empleados(rfc)',
        'idx_empleados_curp' => 'empleados(curp)',
        'idx_empleados_email' => 'empleados(email)',
        'idx_empleados_registro' => 'empleados(fecha_registro)'
    ];

    // Índices para tabla usuarios
    $indicesUsuarios = [
        'idx_usuarios_username' => 'usuarios(username)',
        'idx_usuarios_email' => 'usuarios(email)',
        'idx_usuarios_rol' => 'usuarios(rol)',
        'idx_usuarios_activo' => 'usuarios(activo)',
        'idx_usuarios_empleado_id' => 'usuarios(empleado_id)'
    ];

    // Función para crear índices seguros
    function createIndexSafely($conn, $indexName, $tableAndColumns) {
        try {
            $conn->exec("CREATE INDEX IF NOT EXISTS $indexName ON $tableAndColumns");
        } catch (Exception $e) {
            // Índice ya existe o error, continuar
        }
    }

    // Crear todos los índices
    array_map(function($index) use ($conn) {
        createIndexSafely($conn, $index['name'], $index['definition']);
    }, array_merge(
        array_map(fn($k, $v) => ['name' => $k, 'definition' => $v], array_keys($indicesAsistencia), $indicesAsistencia),
        array_map(fn($k, $v) => ['name' => $k, 'definition' => $v], array_keys($indicesRetardos), $indicesRetardos),
        array_map(fn($k, $v) => ['name' => $k, 'definition' => $v], array_keys($indicesEmpleados), $indicesEmpleados),
        array_map(fn($k, $v) => ['name' => $k, 'definition' => $v], array_keys($indicesUsuarios), $indicesUsuarios)
    ));

    // =========================================================================
    // 12. ACTUALIZAR TABLAS EXISTENTES CON CAMPOS FALTANTES
    // =========================================================================

    // Actualizar tabla empleados con campos faltantes
    $alterEmpleados = [
        'updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'CONSTRAINT chk_empleados_sexo CHECK (sexo IN ("M", "F", "m", "f") OR sexo IS NULL)',
        'CONSTRAINT chk_empleados_fecha_nacimiento CHECK (fecha_nacimiento < DATE_ADD(CURRENT_DATE, INTERVAL -16 YEAR) OR fecha_nacimiento IS NULL)',
        'INDEX idx_empleados_nombre_completo (nombre, apellido)'
    ];

    foreach ($alterEmpleados as $alteration) {
        try {
            if (strpos($alteration, 'CONSTRAINT') === 0 || strpos($alteration, 'INDEX') === 0) {
                $conn->exec("ALTER TABLE empleados ADD $alteration");
            } else {
                $conn->exec("ALTER TABLE empleados ADD COLUMN IF NOT EXISTS $alteration");
            }
        } catch (Exception $e) {
            // Columna ya existe, continuar
        }
    }

    // =========================================================================
    // 13. AÑADIR CHECK CONSTRAINTS A TABLAS PRINCIPALES
    // =========================================================================

    // Check constraints para tabla retardos
    $checkConstraintsRetardos = [
        'chk_retardos_minutos_positivos' => 'ALTER TABLE retardos ADD CONSTRAINT IF NOT EXISTS chk_retardos_minutos_positivos CHECK (minutos_retraso > 0)',
        'chk_retardos_tipo_retraso' => 'ALTER TABLE retardos ADD CONSTRAINT IF NOT EXISTS chk_retardos_tipo_retraso CHECK (tipo_retraso IN ("menor", "mayor"))'
    ];

    foreach ($checkConstraintsRetardos as $name => $sql) {
        try {
            $conn->exec($sql);
        } catch (Exception $e) {
            // Constraint ya existe, continuar
        }
    }

    // =========================================================================
    // 14. OPTIMIZAR TABLAS Y ACTUALIZAR ESTADÍSTICAS
    // =========================================================================

    try {
        $conn->exec('ANALYZE TABLE empleados, usuarios, asistencia, retardos, sanciones, horarios_laborales, horarios_empleados, dispositivos_biometricos, logs_dispositivos, comisiones, justificaciones, ausencias');
    } catch (Exception $e) {
        // Error en análisis, pero continuar
    }

    // =========================================================================
    // 15. INSERTAR DATOS DE EJEMPLO SI ES NECESARIO
    // =========================================================================

    // Insertar tipos de justificación básicos si no existen
    $tiposJustificacionBasicos = [
        ['nombre' => 'Nómina', 'descripcion' => 'Tiempo justificado por nómina', 'requiere_aprobacion' => false, 'requiere_documento' => false],
        ['nombre' => 'Ausencia justificada', 'descripcion' => 'Ausencia justificada sin documento', 'requiere_aprobacion' => false, 'requiere_documento' => false],
        ['nombre' => 'Comisión', 'descripcion' => 'Comisión de trabajo', 'requiere_aprobacion' => true, 'requiere_documento' => true],
        ['nombre' => 'Día económico', 'descripcion' => 'Día económico solicitado', 'requiere_aprobacion' => true, 'requiere_documento' => true],
        ['nombre' => 'Licencia médica', 'descripcion' => 'Licencia por enfermedad', 'requiere_aprobacion' => true, 'requiere_documento' => true],
        ['nombre' => 'Médico', 'descripcion' => 'Justificación por consulta médica', 'requiere_aprobacion' => true, 'requiere_documento' => true],
        ['nombre' => 'Personal', 'descripcion' => 'Asunto personal', 'requiere_aprobacion' => true, 'requiere_documento' => false],
        ['nombre' => 'Transporte', 'descripcion' => 'Problemas de transporte', 'requiere_aprobacion' => false, 'requiere_documento' => false],
        ['nombre' => 'Familiar', 'descripcion' => 'Emergencia familiar', 'requiere_aprobacion' => true, 'requiere_documento' => true]
    ];

    foreach ($tiposJustificacionBasicos as $tipo) {
        try {
            $stmt = $conn->prepare('INSERT IGNORE INTO tipos_justificacion (nombre, descripcion, requiere_aprobacion, requiere_documento) VALUES (?, ?, ?, ?)');
            $stmt->execute([$tipo['nombre'], $tipo['descripcion'], $tipo['requiere_aprobacion'] ? 1 : 0, $tipo['requiere_documento'] ? 1 : 0]);
        } catch (Exception $e) {
            // Error en inserción, continuar
        }
    }

    echo "Base de datos actualizada exitosamente con:\n";
    echo "- Estructura completa de tablas con motores InnoDB\n";
    echo "- Foreign keys con acciones CASCADE y SET NULL apropiadas\n";
    echo "- Índices optimizados para rendimiento\n";
    echo "- Check constraints para validación de datos\n";
    echo "- Tablas de ciclos y bloques para turnos rotativos\n";
    echo "- Soporte multi-sede completo\n";
    echo "- Columnas de auditoría y timestamps actualizables\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
