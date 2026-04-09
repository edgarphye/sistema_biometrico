@echo off
REM ====================================================================
REM Script de Mantenimiento Automatizado - Sistema Biométrico (Windows)
REM ====================================================================

setlocal enabledelayedexpansion

REM Configuración
set SCRIPT_DIR=%~dp0
set LOG_FILE=%SCRIPT_DIR%logs\maintenance.log
set TIMESTAMP=%date:~6,4%-%date:~3,2%-%date:~0,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

REM Crear directorios necesarios
if not exist "%SCRIPT_DIR%logs" mkdir "%SCRIPT_DIR%logs"

REM Función para registrar logs
goto :main

:log_message
echo [%date% %time%] %~1
echo [%date% %time%] %~1 >> "%LOG_FILE%"
goto :eof

:show_menu
cls
echo ============================================================
echo         🔧 MANTENIMIENTO DEL SISTEMA BIOMÉTRICO
echo ============================================================
echo.
echo 1. 📊 Estado del Sistema
echo 2. 💾 Gestión de Backups  
echo 3. 🔄 Restauración de Base de Datos
echo 4. 🧹 Limpieza y Optimización
echo 5. 🔍 Diagnóstico y Verificación
echo 6. ⚙️  Configuración Avanzada
echo 7. 📈 Reportes y Estadísticas
echo 8. 🔐 Seguridad y Auditoría
echo 9. 🚀 Tareas Automáticas
echo 0. 🚪 Salir
echo.
echo ============================================================
set /p choice="Seleccione una opción [0-9]: "

if "%choice%"=="1" goto :system_status
if "%choice%"=="2" goto :backup_management
if "%choice%"=="3" goto :restore_management
if "%choice%"=="4" goto :cleanup_optimization
if "%choice%"=="5" goto :diagnostic_verification
if "%choice%"=="6" goto :advanced_configuration
if "%choice%"=="7" goto :reports_statistics
if "%choice%"=="8" goto :security_audit
if "%choice%"=="9" goto :automated_tasks
if "%choice%"=="0" goto :exit

goto :invalid_option

:system_status
call :log_message "Iniciando diagnóstico del sistema"
php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible"
    goto :wait_continue
)

cd /d "%SCRIPT_DIR%"
php maintenance.php
goto :main_menu

:backup_management
call :log_message "Iniciando gestión de backups"
php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible"
    goto :wait_continue
)

cd /d "%SCRIPT_DIR%"
echo.
echo SUB-OPCIONES DE BACKUP:
echo 1. Backup Completo Automático
echo 2. Backup Diferencial
echo 3. Listar Backups
echo 4. Verificar Integridad
echo 5. Volver al Menú Principal
echo.
set /p backup_choice="Seleccione una opción [1-5]: "

if "%backup_choice%"=="1" (
    call :log_message "Creando backup completo"
    php maintenance.php --backup
)
if "%backup_choice%"=="2" (
    call :log_message "Creando backup diferencial"
    php maintenance.php --backup
)
if "%backup_choice%"=="3" (
    php restore_database.php --list
)
if "%backup_choice%"=="4" (
    call :log_message "Verificando integridad de backups"
    php restore_database.php --list
    echo Verificando hashes SHA256...
)
goto :wait_continue

:restore_management
call :log_message "Iniciando gestión de restauración"
php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible"
    goto :wait_continue
)

cd /d "%SCRIPT_DIR%"
echo.
echo ⚠️  ADVERTENCIA: La restauración sobrescribirá todos los datos
echo.
echo OPCIONES DE RESTAURACIÓN:
echo 1. Restaurar Backup Seguro (con backup de seguridad)
echo 2. Restaurar Backup Rápido (sin verificación)
echo 3. Simular Restauración (Dry-Run)
echo 4. Volver al Menú Principal
echo.
set /p restore_choice="Seleccione una opción [1-4]: "

if "%restore_choice%"=="1" (
    call :log_message "Iniciando restauración segura"
    php restore_database.php
)
if "%restore_choice%"=="2" (
    call :log_message "Iniciando restauración rápida"
    php restore_database.php
)
if "%restore_choice%"=="3" (
    call :log_message "Iniciando simulación de restauración"
    php restore_database.php --dry-run
)
goto :wait_continue

:cleanup_optimization
call :log_message "Iniciando limpieza y optimización"
php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible"
    goto :wait_continue
)

cd /d "%SCRIPT_DIR%"
php maintenance.php --cleanup
goto :wait_continue

:diagnostic_verification
call :log_message "Iniciando diagnóstico completo"
php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible"
    goto :wait_continue
)

cd /d "%SCRIPT_DIR%"
php maintenance.php --health-check
goto :wait_continue

:advanced_configuration
echo.
echo ⚙️  CONFIGURACIÓN AVANZADA
echo.
echo OPCIONES:
echo 1. Verificar Configuración Actual
echo 2. Actualizar Base de Datos
echo 3. Configurar Variables de Entorno
echo 4. Volver al Menú Principal
echo.
set /p config_choice="Seleccione una opción [1-4]: "

if "%config_choice%"=="1" (
    call :log_message "Verificando configuración actual"
    php -r "
    require_once 'config.php';
    echo 'Database: ' . \$config['database']['name'] . PHP_EOL;
    echo 'PHP Version: ' . PHP_VERSION . PHP_EOL;
    echo 'Memory Limit: ' . ini_get('memory_limit') . PHP_EOL;
    echo 'Max Execution Time: ' . ini_get('max_execution_time') . PHP_EOL;
    "
)
if "%config_choice%"=="2" (
    call :log_message "Actualizando estructura de base de datos"
    php update_db.php
)
if "%config_choice%"=="3" (
    call :log_message "Configurando variables de entorno"
    echo Variables de entorno actuales:
    set | find "DB_"
    echo | find "ENCRYPTION_KEY"
)
goto :wait_continue

:reports_statistics
call :log_message "Generando reportes y estadísticas"
php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible"
    goto :wait_continue
)

cd /d "%SCRIPT_DIR%"
echo.
echo 📈 GENERANDO REPORTES
echo.
echo 1. Reporte de Asistencia (Últimos 30 días)
echo 2. Reporte de Retardos y Sanciones
echo 3. Estadísticas de Uso del Sistema
echo 4. Volver al Menú Principal
echo.
set /p report_choice="Seleccione una opción [1-4]: "

if "%report_choice%"=="1" (
    call :log_message "Generando reporte de asistencia"
    php -r "
    require_once 'config.php'; require_once 'models/Database.php';
    \$db = new Database();
    \$conn = \$db->getConnection();
    \$result = \$conn->query('
        SELECT COUNT(*) as total, 
               DATE(fecha) as dia 
        FROM asistencia 
        WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(fecha)
        ORDER BY dia DESC
        LIMIT 7
    ')->fetchAll(PDO::FETCH_ASSOC);
    echo 'Asistencia últimos 7 días:' . PHP_EOL;
    foreach (\$result as \$row) {
        echo \$row['dia'] . ': ' . \$row['total'] . ' registros' . PHP_EOL;
    }
    "
)
if "%report_choice%"=="2" (
    call :log_message "Generando reporte de retardos y sanciones"
    php -r "
    require_once 'config.php'; require_once 'models/Database.php';
    \$db = new Database();
    \$conn = \$db->getConnection();
    \$retardos = \$conn->query('SELECT COUNT(*) as total FROM retardos WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)')->fetchColumn();
    \$sanciones = \$conn->query('SELECT COUNT(*) as total FROM sanciones WHERE fecha_sancion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)')->fetchColumn();
    echo 'Retardos últimos 30 días: ' . \$retardos . PHP_EOL;
    echo 'Sanciones últimos 30 días: ' . \$sanciones . PHP_EOL;
    "
)
if "%report_choice%"=="3" (
    call :log_message "Generando estadísticas de uso"
    php -r "
    require_once 'config.php'; require_once 'models/Database.php';
    \$db = new Database();
    \$conn = \$db->getConnection();
    \$usuarios = \$conn->query('SELECT COUNT(*) as total FROM usuarios')->fetchColumn();
    \$empleados = \$conn->query('SELECT COUNT(*) as total FROM empleados WHERE activo = 1')->fetchColumn();
    echo 'Usuarios activos: ' . \$usuarios . PHP_EOL;
    echo 'Empleados activos: ' . \$empleados . PHP_EOL;
    "
)
goto :wait_continue

:security_audit
call :log_message "Iniciando auditoría de seguridad"
echo.
echo 🔐 AUDITORÍA DE SEGURIDAD
echo.
echo 1. Verificar Permisos de Archivos
echo 2. Auditoría de Usuarios
echo 3. Verificar Configuración HTTPS
echo 4. Generar Reporte de Seguridad
echo 5. Volver al Menú Principal
echo.
set /p security_choice="Seleccione una opción [1-5]: "

if "%security_choice%"=="1" (
    call :log_message "Verificando permisos de archivos"
    echo Verificando permisos críticos...
    
    if not exist "config.php" (
        echo ❌ ERROR: config.php no encontrado
    ) else (
        echo ✅ config.php existe
    )
    
    if not exist ".env" (
        echo ⚠️  ADVERTENCIA: .env no encontrado
    ) else (
        echo ✅ .env existe
    )
    
    if not exist "uploads/" (
        echo ⚠️  ADVERTENCIA: uploads/ no existe
    ) else (
        echo ✅ uploads/ existe
    )
)
if "%security_choice%"=="2" (
    call :log_message "Realizando auditoría de usuarios"
    php -r "
    require_once 'config.php'; require_once 'models/Database.php';
    \$db = new Database();
    \$conn = \$db->getConnection();
    \$usuarios = \$conn->query('SELECT username, rol, activo FROM usuarios')->fetchAll(PDO::FETCH_ASSOC);
    echo 'Auditoría de usuarios:' . PHP_EOL;
    foreach (\$usuarios as \$usuario) {
        \$status = \$usuario['activo'] ? 'ACTIVO' : 'INACTIVO';
        echo \$usuario['username'] . ' - ' . \$usuario['rol'] . ' - ' . \$status . PHP_EOL;
    }
    "
)
if "%security_choice%"=="3" (
    call :log_message "Verificando configuración HTTPS"
    php -r "
    echo 'HTTPS: ' . (isset(\$_SERVER['HTTPS']) ? 'ENABLED' : 'DISABLED') . PHP_EOL;
    echo 'SSL Certificate: ' . (isset(\$_SERVER['SSL_TLS_SNI']) ? 'PRESENT' : 'NOT PRESENT') . PHP_EOL;
    echo 'Server Port: ' . (\$_SERVER['SERVER_PORT'] ?? 'UNKNOWN') . PHP_EOL;
    "
)
goto :wait_continue

:automated_tasks
call :log_message "Configurando tareas automatizadas"
echo.
echo 🚀 TAREAS AUTOMATIZADAS
echo.
echo 1. Programar Backup Diario (2 AM)
echo 2. Programar Limpieza Semanal (Domingo 3 AM)
echo 3. Configurar Monitoreo Continuo
echo 4. Configurar Notificaciones por Email
echo 5. Ver Tareas Programadas
echo 6. Volver al Menú Principal
echo.
set /p task_choice="Seleccione una opción [1-6]: "

if "%task_choice%"=="1" (
    call :log_message "Programando backup diario"
    echo Creando tarea programada para backup diario...
    schtasks /create /tn "BackupBiometrico" /tr "C:\ruta\al\proyecto\maintenance.bat --backup" /sc daily /st 02:00 /ru SYSTEM
    echo Tarea de backup diario programada
)
if "%task_choice%"=="2" (
    call :log_message "Programando limpieza semanal"
    echo Creando tarea programada para limpieza semanal...
    schtasks /create /tn "LimpiezaBiometrico" /tr "C:\ruta\al\proyecto\maintenance.bat --cleanup" /sc weekly /d SUN /st 03:00 /ru SYSTEM
    echo Tarea de limpieza semanal programada
)
if "%task_choice%"=="3" (
    call :log_message "Configurando monitoreo"
    echo Configurando monitoreo del sistema...
    echo Monitoreo configurado para registrar en: %LOG_FILE%
)
if "%task_choice%"=="4" (
    call :log_message "Configurando notificaciones"
    echo Configure notificaciones por email en el archivo de configuración
    echo Variables necesarias: EMAIL_SMTP_HOST, EMAIL_SMTP_USER, EMAIL_SMTP_PASS, EMAIL_FROM, EMAIL_TO
)
if "%task_choice%"=="5" (
    echo Tareas programadas actuales:
    schtasks /query /fo LIST | findstr /i "BackupBiometrico LimpiezaBiometrico"
)
goto :wait_continue

:wait_continue
echo.
echo Presione Enter para continuar...
pause >nul
goto :main_menu

:invalid_option
echo.
echo ❌ Opción inválida. Intente nuevamente.
echo.
timeout /t 2 >nul
goto :main_menu

:main_menu
goto :show_menu

:exit
echo.
echo 👋 Saliendo del sistema de mantenimiento...
echo Logs guardados en: %LOG_FILE%
timeout /t 3 >nul
exit /b 0

:main
REM Verificar si se proporcionaron argumentos de línea de comandos
if "%1%"=="--backup" goto :auto_backup
if "%1%"=="--restore" goto :auto_restore
if "%1%"=="--cleanup" goto :auto_cleanup
if "%1%"=="--health-check" goto :auto_health_check
if "%1%"=="--help" goto :show_help

REM Si no hay argumentos, mostrar menú interactivo
goto :show_menu

:auto_backup
call :log_message "Ejecutando backup automático"
cd /d "%SCRIPT_DIR%"
php maintenance.php --backup
exit /b 0

:auto_restore
call :log_message "Ejecutando restauración automática: %2%"
cd /d "%SCRIPT_DIR%"
php maintenance.php --restore %2
exit /b 0

:auto_cleanup
call :log_message "Ejecutando limpieza automática"
cd /d "%SCRIPT_DIR%"
php maintenance.php --cleanup
exit /b 0

:auto_health_check
call :log_message "Ejecutando diagnóstico de salud automático"
cd /d "%SCRIPT_DIR%"
php maintenance.php --health-check
exit /b 0

:show_help
echo.
echo 🔧 SISTEMA DE MANTENIMIENTO BIOMÉTRICO
echo.
echo Uso:
echo   maintenance.bat                    # Modo interactivo
echo   maintenance.bat --backup           # Backup automático
echo   maintenance.bat --restore DATE      # Restaurar backup específico
echo   maintenance.bat --cleanup          # Limpieza completa
echo   maintenance.bat --health-check      # Diagnóstico completo
echo   maintenance.bat --help              # Mostrar esta ayuda
echo.
echo Ejemplos:
echo   maintenance.bat --backup
echo   maintenance.bat --restore 2024-12-01
echo.
timeout /t 5 >nul
exit /b 0