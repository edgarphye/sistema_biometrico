@echo off
REM ====================================================================
REM Script de Restauración Automatizada - Sistema Biométrico (Windows)
REM ====================================================================

setlocal enabledelayedexpansion

REM Configuración
set SCRIPT_DIR=%~dp0
set BACKUP_DIR=%SCRIPT_DIR%backups\database
set LOG_FILE=%SCRIPT_DIR%logs\backup.log
set TIMESTAMP=%date:~6,4%-%date:~3,2%-%date:~0,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

REM Función para registrar logs
goto :main

:log_message
echo [%date% %time%] %~1
echo [%date% %time%] %~1 >> "%LOG_FILE%"
goto :eof

:show_menu
cls
echo ============================================================
echo    RESTAURACIÓN DE BASE DE DATOS - SISTEMA BIOMÉTRICO
echo ============================================================
echo.
echo 1. Listar backups disponibles
echo 2. Restaurar backup (modo seguro)
echo 3. Restaurar backup (modo rápido)
echo 4. Simular restauración
echo 5. Verificar integridad de backups
echo 6. Crear backup de seguridad
echo 7. Restaurar por fecha específica
echo 0. Salir
echo.
echo ============================================================
set /p choice="Seleccione una opción [0-7]: "
goto :eof

:list_backups
echo.
echo === BACKUPS DISPONIBLES ===
echo ============================================================
cd /d "%SCRIPT_DIR%"

php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está disponible
    goto :wait_continue
)

php restore_database.php --list
goto :eof

:restore_safe
echo.
echo === RESTAURACIÓN MODO SEGURO ===
echo ============================================================
cd /d "%SCRIPT_DIR%"

php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está disponible
    goto :wait_continue
)

php restore_database.php
goto :eof

:restore_quick
echo.
echo === RESTAURACIÓN MODO RÁPIDO ===
echo ============================================================
echo ADVERTENCIA: Este modo no crea backup de seguridad ni verifica tablas
echo.
set /p confirm="¿Desea continuar? [s/N]: "
if /i not "%confirm%"=="s" (
    echo Restauración cancelada.
    goto :main_loop
)

cd /d "%SCRIPT_DIR%"
php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está disponible
    goto :wait_continue
)

echo Iniciando restauración rápida...
set /p backup_date="Ingrese fecha del backup (YYYY-MM-DD): "
if defined backup_date (
    php restore_database.php --restore %backup_date%
)
goto :eof

:simulate_restore
echo.
echo === SIMULACIÓN DE RESTAURACIÓN ===
echo ============================================================
cd /d "%SCRIPT_DIR%"

php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está disponible
    goto :wait_continue
)

set /p backup_date="Ingrese fecha del backup (YYYY-MM-DD): "
if defined backup_date (
    php restore_database.php --dry-run %backup_date%
)
goto :eof

:verify_integrity
echo.
echo === VERIFICACIÓN DE INTEGRIDAD ===
echo ============================================================
cd /d "%SCRIPT_DIR%"

php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está disponible
    goto :wait_continue
)

echo Verificando integridad de todos los backups...
set valid_count=0
set total_count=0

for %%f in ("%BACKUP_DIR%\*.gz") do (
    set /a total_count+=1
    set hash_file=%%f.sha256
    
    if exist "!hash_file!" (
        REM Leer hash almacenado
        set /p stored_hash=<"!hash_file!"
        
        REM Calcular hash actual
        for /f "tokens=1" %%h in ('certutil -hashfile "%%f" SHA256 ^| find /i "!stored_hash!"') do (
            if defined %%h (
                echo   [VALIDO] %%~nxf
                set /a valid_count+=1
            ) else (
                echo   [ERROR] %%~nxf - Hash inválido
            )
        )
    ) else (
        echo   [ADVERTENCIA] %%~nxf - Sin hash
    )
)

echo.
echo Resultado: !valid_count!/!total_count! backups válidos
goto :eof

:create_safety_backup
echo.
echo === CREAR BACKUP DE SEGURIDAD ===
echo ============================================================
cd /d "%SCRIPT_DIR%"

php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está disponible
    goto :wait_continue
)

echo Creando backup de seguridad actual...
php backup_database.php
goto :eof

:restore_by_date
echo.
echo === RESTAURACIÓN POR FECHA ===
echo ============================================================
echo Formatos válidos:
echo   - YYYY-MM-DD (ej: 2024-12-01)
echo   - YYYYMMDD   (ej: 20241201)
echo.
set /p backup_date="Ingrese fecha del backup: "

if not defined backup_date (
    echo Error: Debe ingresar una fecha
    goto :main_loop
)

cd /d "%SCRIPT_DIR%"
php -v >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está disponible
    goto :wait_continue
)

echo Iniciando restauración del backup: %backup_date%
php restore_database.php --restore %backup_date%

if !errorlevel! equ 0 (
    echo.
    echo ✅ Restauración completada exitosamente
    echo Puede verificar el estado ejecutando la opción 5 (Verificar integridad)
) else (
    echo.
    echo ❌ Restauración falló. Verifique los logs en: %LOG_FILE%
)
goto :eof

:wait_continue
echo.
echo Presione Enter para continuar...
pause >nul
goto :eof

:main
REM Verificar que estamos en el directorio correcto
if not exist "restore_database.php" (
    echo ERROR: restore_database.php no encontrado en el directorio actual
    echo Ejecuta este script desde el directorio raíz del proyecto
    pause
    exit /b 1
)

REM Crear directorios necesarios
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
if not exist "%SCRIPT_DIR%logs" mkdir "%SCRIPT_DIR%logs"

:Bucle principal
:main_loop
call :show_menu

if "%choice%"=="1" (
    call :list_backups
    call :wait_continue
    goto main_loop
) else if "%choice%"=="2" (
    call :restore_safe
    goto main_loop
) else if "%choice%"=="3" (
    call :restore_quick
    goto main_loop
) else if "%choice%"=="4" (
    call :simulate_restore
    goto main_loop
) else if "%choice%"=="5" (
    call :verify_integrity
    call :wait_continue
    goto main_loop
) else if "%choice%"=="6" (
    call :create_safety_backup
    call :wait_continue
    goto main_loop
) else if "%choice%"=="7" (
    call :restore_by_date
    goto main_loop
) else if "%choice%"=="0" (
    echo Saliendo del sistema de restauración.
    goto end
) else (
    echo Opción inválida. Intente nuevamente.
    timeout /t 2 >nul
    goto main_loop
)

:end
echo.
echo Gracias por usar el Sistema de Restauración Biométrico
echo Logs guardados en: %LOG_FILE%
timeout /t 3 >nul
exit /b 0