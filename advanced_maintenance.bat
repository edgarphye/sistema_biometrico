@echo off
REM ====================================================================
REM Script de Mantenimiento Automatizado - Sistema Biométrico v2.0 (Windows)
REM ====================================================================

setlocal enabledelayedexpansion

REM Configuración
set SCRIPT_DIR=%~dp0
set BACKUP_DIR=%SCRIPT_DIR%backups\database
set LOG_FILE=%SCRIPT_DIR%logs\maintenance.log
set TIMESTAMP=%date:~6,4%-%date:~3,2%-%date:~0,2%-%time:~0,2%
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
echo         🔧 PANEL DE MANTENIMIENTO AVANZADO
echo ============================================================
echo.
echo 1. 📊 Estado Completo del Sistema
echo 2. 💾 Gestión Avanzada de Backups
echo 3. 🔄 Restauración Integral de BD
echo 4. 🧹 Limpieza y Optimización de BD
echo 5. 🔍 Diagnóstico Completo del Sistema
echo 6. ⚙️ Mantenimiento Biométrico ZK
echo 7. 📈 Gestión de Logs y Auditoría
echo 8. 📋 Reportes y Estadísticas Avanzados
echo 9. 🔐 Seguridad Integral del Sistema
echo 10. ⚙️ Configuración Avanzada
echo 11. 🚀 Tareas Automatizadas
echo 0. 🚪 Salir
echo.
echo ============================================================
set /p choice="Seleccione una opción [0-11]: "

if "%choice%"=="1" goto :complete_system_status
if "%choice%"=="2" goto :advanced_backup_management
if "%choice%"=="3" goto :integral_database_restore
if "%choice%"=="4" goto :database_cleanup_optimization
if "%choice%"=="5" goto :complete_system_diagnostic
if "%choice%"=="6" goto :biometric_maintenance
if "%choice%"=="7" goto :logs_audit_management
if "%choice%"=="8" goto :advanced_reports_statistics
if "%choice%"=="9" goto :integral_security_audit
if "%choice%"=="10" goto :advanced_system_configuration
if "%choice%"=="11" goto :automated_tasks_programming
if "%choice%"=="0" goto :exit

goto :invalid_option

:complete_system_status
call :log_message "Iniciando análisis completo del sistema"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->completeSystemStatus();"
goto :main_menu

:advanced_backup_management
call :log_message "Iniciando gestión avanzada de backups"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->advancedBackupManagement();"
goto :main_menu

:integral_database_restore
call :log_message "Iniciando restauración integral de BD"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->integralDatabaseRestore();"
goto :main_menu

:database_cleanup_optimization
call :log_message "Iniciando limpieza y optimización de BD"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->databaseCleanupOptimization();"
goto :main_menu

:complete_system_diagnostic
call :log_message "Iniciando diagnóstico completo del sistema"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->completeSystemDiagnostic();"
goto :main_menu

:biometric_maintenance
call :log_message "Iniciando mantenimiento biométrico ZK"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->biometricMaintenance();"
goto :main_menu

:logs_audit_management
call :log_message "Iniciando gestión de logs y auditoría"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->logsAuditManagement();"
goto :main_menu

:advanced_reports_statistics
call :log_message "Iniciando reportes avanzados y estadísticas"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->advancedReportsStatistics();"
goto :main_menu

:integral_security_audit
call :log_message "Iniciando auditoría integral de seguridad"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->integralSecurityAudit();"
goto :main_menu

:advanced_system_configuration
call :log_message "Iniciando configuración avanzada del sistema"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->advancedSystemConfiguration();"
goto :main_menu

:automated_tasks_programming
call :log_message "Iniciando programación de tareas automátizadas"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->automatedTasksProgramming();"
goto :main_menu

:invalid_option
echo.
echo ❌ Opción inválida. Intente nuevamente.
echo.
timeout /t 2 >nul
goto :main_menu

:main
REM Verificar si se proporcionaron argumentos de línea de comandos
if "%1%"=="--backup" goto :auto_backup
if "%1%"=="--restore" goto :auto_restore
if "%1%"=="--cleanup" goto :auto_cleanup
if "%1%"=="--health-check" goto :auto_health_check
if "%1%"=="--biometric" goto :auto_biometric
if "%1%"=="--logs-audit" goto :auto_logs_audit
if "%1%"=="--devices-check" goto :auto_devices_check
if "%1%"=="--help" goto :show_help

REM Modo interactivo por defecto
echo "Iniciando panel de mantenimiento avanzado..."
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->showMainMenu();"

goto :end

:auto_backup
call :log_message "Iniciando backup automático..."
cd /d "%SCRIPT_DIR%"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$result = \$m->backup->backupFull();"
if %errorlevel% neq 0 (
    call :log_message "❌ Error en backup: %errorlevel%"
    goto :wait_continue
)
echo ✅ Backup completado
goto :wait_continue

:auto_restore
call :log_message "Iniciando restauración automática: %2%"
cd /d "%SCRIPT_DIR%"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$result = \$m->integralDatabaseRestore();"
if %errorlevel% neq 0 (
    call :log_message "❌ Error en restauración: %errorlevel%"
    goto :wait_continue
)
echo ✅ Restauración completada
goto :wait_continue

:auto_cleanup
call :log_message "Iniciando limpieza automática..."
cd /d "%SCRIPT_DIR%"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->databaseCleanupOptimization();"
if %errorlevel% neq 0 (
    call :log_message "❌ Error en limpieza: %errorlevel%"
    goto :wait_continue
)
echo ✅ Limpieza completada
goto :wait_continue

:auto_health_check
call :log_message "Iniciando diagnóstico de salud automático..."
cd /d "%SCRIPT_DIR%"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->completeSystemDiagnostic();"
if %errorlevel% neq 0 (
    call :log_message "❌ Error en diagnóstico: %errorlevel%"
    goto :wait_continue
)
echo ✅ Diagnóstico completado
goto :wait_continue

:auto_biometric
call :log_message "Iniciando mantenimiento biométrico ZK..."
cd /d "%SCRIPT_DIR%"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->checkBiometricDevices();"
if %errorlevel% neq 0 (
    call :log_message "❌ Error en verificación biométrico: %errorlevel%"
    goto :wait_continue
)
echo ✅ Verificación completada
goto :wait_continue

:auto_logs_audit
call :log_message "Iniciando auditoría de logs..."
cd /d "%SCRIPT_DIR%"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->logsAuditManagement();"
if %errorlevel% neq 0 (
    call :log_message "❌ Error en auditoría de logs: %errorlevel%"
    goto :wait_continue
)
echo ✅ Auditoría completada
goto :wait_continue

:auto_devices_check
call :log_message "Verificando dispositivos biométricos..."
cd /d "%SCRIPT_DIR%"
php -r "require_once 'advanced_maintenance.php'; \$m = new AdvancedSystemMaintenance(); \$m->checkBiometricDevices();"
if %errorlevel% neq 0 (
    call :log_message "❌ Error en verificación de dispositivos: %errorlevel%"
    goto :wait_continue
echo ✅ Verificación completada
goto :wait_continue

:wait_continue
echo Presione Enter para continuar...
pause >nul
goto :main_menu

:show_help
echo.
echo 🔧 PANEL DE MANTENIMIENTO AVANZADO - SISTEMA BIOMÉTRICO v2.0
echo.
echo Versión: 2.0 (30 tablas, 1 vista, 1 procedimiento)
echo.
echo Uso:
echo   advanced_maintenance.bat                      # Modo interactivo
echo.
echo   advanced_maintenance.bat --backup           # Backup automático
echo   advanced_maintenance.bat --restore DATE      # Restaurar backup específico
echo   advanced_maintenance.bat --cleanup          # Limpieza completa
echo   advanced_maintenance.bat --health-check      # Diagnóstico completo
echo   advanced_maintenance.bat --biometric        # Mantenimiento biométrico
echo   advanced_maintenance.bat --logs-audit        # Auditoría de logs
echo   advanced_maintenance.bat --devices-check      # Verificar dispositivos
echo   advanced_maintenance.bat --help                # Mostrar esta ayuda
echo.
echo.
echo Características:
echo ✅ Gestión completa de 30 tablas del sistema
echo ✅ Mantenimiento biométrico ZK avanzado
echo ✅ Auditoría integral de logs y seguridad
echo ✅ Reportes avanzados y estadísticas
echo ✅ Tareas automatizadas programables
echo ✅ Herramientas de desarrollo integradas
echo.
echo.
echo Tareas programables:
echo   - Backup diario (2 AM)
echo   - Limpieza semanal (Domingo 3 AM)
echo   - Verificar dispositivos biométricos
echo - Monitoreo continuo
echo   - Reportes automáticos
echo   - Notificaciones por email
echo.
echo.
echo Scripts adicionales:
echo   maintenance.bat                     # Script interactivo completo
echo   maintenance_backup.bat                   # Solo backup
echo   maintenance_restore.bat                 # Solo restauración
echo   maintenance_cleanup.bat                  # Solo limpieza
echo   maintenance_diagnostic.bat              # Solo diagnóstico
echo.
echo.
echo Ejemplos:
echo   advanced_maintenance                    # Panel interactivo
echo   advanced_maintenance --biometric        # Mantenimiento biométrico
echo   advanced_maintenance --backup          # Backup automático
echo   advanced_maintenance --restore 2024-12-01    # Restaurar backup específico
echo   advanced_maintenance --health-check      # Diagnóstico completo
echo.
echo.
timeout /t 5 >nul
exit /b 0

:exit
echo.
echo 👋 Saliendo del sistema de mantenimiento avanzado...
timeout /t 3 >nul
exit /b 0

:end
echo.

# ====================================================================
# FIN DEL SCRIPT
# Este script proporciona una interfaz amigable para todas las tareas de mantenimiento
# El sistema biométrico ahora tiene un panel completo de mantenimiento profesional
# Sistema de mantenimiento avanzado v2.0 listo para producción