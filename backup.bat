@echo off
REM ====================================================================
REM Script de Backup Automatizado - Sistema Biométrico (Windows)
REM ====================================================================

setlocal enabledelayedexpansion

REM Configuración
set SCRIPT_DIR=%~dp0
set BACKUP_DIR=%SCRIPT_DIR%backups\database
set LOG_FILE=%SCRIPT_DIR%logs\backup.log
set TIMESTAMP=%date:~6,4%-%date:~3,2%-%date:~0,2%_%time:~0,2%-%time:~3,2%-%time:~6,2%
set TIMESTAMP=%TIMESTAMP: =0%

REM Crear directorios necesarios
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"
if not exist "%SCRIPT_DIR%logs" mkdir "%SCRIPT_DIR%logs"

REM Función para registrar logs
goto :main

:log_message
echo [%date% %time%] %~1
echo [%date% %time%] %~1 >> "%LOG_FILE%"
goto :eof

:check_disk_space
REM Verificar espacio disponible (simplificado para Windows)
for /f "tokens=3" %%i in ('dir /-c "%SCRIPT_DIR%" ^| find "bytes free"') do set free_bytes=%%i
set free_gb=%free_bytes:~-9,9%

REM Si free_bytes tiene menos de 9 dígitos, tenemos menos de 1GB
if "%free_bytes:~8,1%"=="" (
    call :log_message "ERROR: Espacio en disco insuficiente. Disponible: !free_bytes! bytes"
    exit /b 1
)

goto :eof

:run_backup
set backup_type=%~1
call :log_message "Iniciando backup %backup_type%..."

cd /d "%SCRIPT_DIR%"

REM Verificar si PHP está disponible
php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible en el sistema"
    exit /b 1
)

if "%backup_type%"=="" (
    php backup_database.php 2>&1 | tee -a "%LOG_FILE%"
) else (
    php backup_database.php --%backup_type% 2>&1 | tee -a "%LOG_FILE%"
)

if !errorlevel! equ 0 (
    call :log_message "Backup %backup_type% completado exitosamente"
    exit /b 0
) else (
    call :log_message "ERROR: Backup %backup_type% falló con código !errorlevel!"
    exit /b 1
)

:verify_backups
call :log_message "Verificando integridad de backups..."

REM Contar archivos de backup
set backup_count=0
for %%f in ("%BACKUP_DIR%\*.gz") do set /a backup_count+=1
call :log_message "Se encontraron !backup_count! archivos de backup"

REM Verificar hashes SHA256
set valid_hashes=0
set total_hashes=0

for %%h in ("%BACKUP_DIR%\*.sha256") do (
    if exist "%%h" (
        set /a total_hashes+=1
        set backup_file=%%~dpnh.gz
        
        if exist "!backup_file!" (
            REM Leer hash almacenado
            set /p stored_hash=<"%%h"
            
            REM Calcular hash actual
            certutil -hashfile "!backup_file!" SHA256 | find /i "!stored_hash!" >nul
            
            if !errorlevel! equ 0 (
                set /a valid_hashes+=1
            ) else (
                call :log_message "ERROR: Hash inválido para !backup_file!"
            )
        )
    )
)

call :log_message "Hashes válidos: !valid_hashes!/!total_hashes!"

if !valid_hashes! equ !total_hashes! if !total_hashes! gtr 0 (
    call :log_message "Todos los hashes son válidos"
    exit /b 0
) else (
    call :log_message "WARNING: Algunos hashes son inválidos o faltan"
    exit /b 1
)

:cleanup_old_backups
call :log_message "Iniciando limpieza de backups antiguos..."

cd /d "%SCRIPT_DIR%"

php -v >nul 2>&1
if errorlevel 1 (
    call :log_message "ERROR: PHP no está disponible para la limpieza"
    exit /b 1
)

php backup_database.php --cleanup 2>&1 | tee -a "%LOG_FILE%"

if !errorlevel! equ 0 (
    call :log_message "Limpieza completada exitosamente"
    exit /b 0
) else (
    call :log_message "ERROR: La limpieza falló con código !errorlevel!"
    exit /b 1
)

:show_usage
echo Uso: %~nx0 [OPCIÓN]
echo.
echo Opciones disponibles:
echo   full         Realizar backup completo
echo   differential Realizar backup diferencial
echo   list         Listar backups disponibles
echo   restore DATE Restaurar backup específico (formato: YYYYMMDD)
echo   cleanup      Limpiar backups antiguos
echo   verify       Verificar integridad de backups
echo   auto         Backup completo automático (modo predeterminado)
echo.
echo Ejemplos:
echo   %~nx0                # Backup completo automático
echo   %~nx0 full           # Backup completo
echo   %~nx0 differential   # Backup diferencial
echo   %~nx0 restore 20241201 # Restaurar backup del 2024-12-01
echo   %~nx0 list           # Listar todos los backups
goto :eof

REM ====================================================================
REM LÓGICA PRINCIPAL
REM ====================================================================

:main
REM Verificar que estamos en el directorio correcto
if not exist "backup_database.php" (
    echo ERROR: backup_database.php no encontrado en el directorio actual
    echo Ejecuta este script desde el directorio raíz del proyecto
    exit /b 1
)

REM Procesar argumentos
set "arg1=%~1"
if "%arg1%"=="" set "arg1=auto"

if "%arg1%"=="full" (
    call :check_disk_space
    call :run_backup ""
    call :verify_backups
) else if "%arg1%"=="differential" (
    call :check_disk_space
    call :run_backup "differential"
    call :verify_backups
) else if "%arg1%"=="list" (
    cd /d "%SCRIPT_DIR%"
    php -v >nul 2>&1
    if errorlevel 1 (
        echo ERROR: PHP no está disponible
        exit /b 1
    )
    php backup_database.php --list
) else if "%arg1%"=="restore" (
    if "%~2"=="" (
        echo ERROR: Debes especificar una fecha de backup
        echo Uso: %~nx0 restore YYYYMMDD
        exit /b 1
    )
    
    cd /d "%SCRIPT_DIR%"
    php -v >nul 2>&1
    if errorlevel 1 (
        echo ERROR: PHP no está disponible
        exit /b 1
    )
    php backup_database.php --restore %2
) else if "%arg1%"=="cleanup" (
    call :cleanup_old_backups
) else if "%arg1%"=="verify" (
    call :verify_backups
) else if "%arg1%"=="auto" (
    REM Modo automático: backup completo con verificación
    call :log_message "=== INICIANDO BACKUP AUTOMÁTICO ==="
    call :check_disk_space
    call :run_backup ""
    call :verify_backups
    call :cleanup_old_backups
    call :log_message "=== BACKUP AUTOMÁTICO COMPLETADO ==="
) else if "%arg1%"=="help" (
    call :show_usage
) else if "%arg1%"=="-h" (
    call :show_usage
) else if "%arg1%"=="--help" (
    call :show_usage
) else (
    echo ERROR: Opción desconocida '%arg1%'
    echo.
    call :show_usage
    exit /b 1
)

endlocal