@echo off
REM ═══════════════════════════════════════════════════════════════
REM SCRIPT DE CONFIGURACIÓN AUTOMÁTICA PARA PRODUCCIÓN
REM Sistema Biométrico - Windows
REM ═══════════════════════════════════════════════════════════════
REM
REM IMPORTANTE: Ejecutar como Administrador
REM
REM ═══════════════════════════════════════════════════════════════

echo.
echo ═══════════════════════════════════════════════════════════════
echo   CONFIGURACIÓN DE PRODUCCIÓN - Sistema Biométrico
echo ═══════════════════════════════════════════════════════════════
echo.

REM Verificar privilegios de administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Este script requiere permisos de Administrador
    echo        Haz clic derecho y selecciona "Ejecutar como administrador"
    pause
    exit /b 1
)

echo [OK] Ejecutando con privilegios de administrador
echo.

REM ─────────────────────────────────────────────────────────────
REM 1. CONFIGURAR VARIABLES DE ENTORNO
REM ─────────────────────────────────────────────────────────────

echo Paso 1/5: Configurando variables de entorno...
echo.

REM ENCRYPTION_KEY (CRÍTICA) - Solo si no existe
reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v ENCRYPTION_KEY >nul 2>&1
if %errorLevel% neq 0 (
    echo [INFO] ENCRYPTION_KEY no encontrada. Se recomienda ejecutar set_encryption_key.bat primero.
    echo        Estableciendo una clave por defecto (NO RECOMENDADO PARA PRODUCCIÓN REAL).
    setx ENCRYPTION_KEY "3d7e0f3fb2533d2d8c1f534ba0301632a298c17c3bb26fd7ce5db2ae2153d5d3" /M
    echo [WARNING] Clave por defecto establecida.
) else (
    echo [OK] ENCRYPTION_KEY ya existe. No se modifica.
)

REM Otras variables
setx BIOMETRIC_MODE "simulation" /M
setx TOLERANCE_MINUTES "10" /M
setx APP_NAME "Sistema Biométrico" /M
setx BASE_URL "/sistema_biometrico" /M

REM Variables de Base de Datos para unificar config.php y phinx.php
setx DB_HOST "localhost" /M
setx DB_NAME "sistema_biometrico" /M
setx DB_USER "root" /M
setx DB_PASS "" /M  REM <-- IMPORTANTE: Ajusta tu contraseña aquí si tienes una. Si no, déjalo vacío.

echo.
echo [OK] Variables de entorno configuradas
echo.

REM ─────────────────────────────────────────────────────────────
REM 2. VERIFICAR PHP Y EXTENSIONES
REM ─────────────────────────────────────────────────────────────

echo Paso 2/5: Verificando PHP...
echo.

php -v >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] PHP no está instalado o no está en PATH
    echo        Instala PHP 7.4+ y agrégalo al PATH
    pause
    exit /b 1
)

echo [OK] PHP instalado

REM Verificar extensiones
php -m | findstr pdo_mysql >nul
if %errorLevel% equ 0 (
    echo [OK] Extensión pdo_mysql disponible
) else (
    echo [WARNING] Extensión pdo_mysql no encontrada
)

php -m | findstr openssl >nul
if %errorLevel% equ 0 (
    echo [OK] Extensión openssl disponible
) else (
    echo [WARNING] Extensión openssl no encontrada
)

echo.

REM ─────────────────────────────────────────────────────────────
REM 3. CREAR CARPETAS NECESARIAS
REM ─────────────────────────────────────────────────────────────

echo Paso 3/5: Creando carpetas necesarias...
echo.

if not exist "logs" mkdir logs
if not exist "uploads" mkdir uploads
if not exist "uploads\soportes" mkdir uploads\soportes
if not exist "temp" mkdir temp

echo [OK] Carpetas creadas
echo.

REM ─────────────────────────────────────────────────────────────
REM 4. CONFIGURAR PERMISOS
REM ─────────────────────────────────────────────────────────────

echo Paso 4/5: Configurando permisos de carpetas...
echo.

REM --- IMPORTANTE: Cambia 'NT AUTHORITY\SYSTEM' por el usuario con el que corre tu Nginx/PHP-FPM si es diferente ---
set WEB_SERVER_USER="NT AUTHORITY\SYSTEM"

icacls "uploads" /grant %WEB_SERVER_USER%:(OI)(CI)F /T >nul 2>&1
icacls "logs" /grant %WEB_SERVER_USER%:(OI)(CI)F /T >nul 2>&1
icacls "temp" /grant %WEB_SERVER_USER%:(OI)(CI)F /T >nul 2>&1

echo [OK] Permisos configurados
echo.

REM ─────────────────────────────────────────────────────────────
REM 5. VERIFICAR CONFIGURACIÓN
REM ─────────────────────────────────────────────────────────────

echo Paso 5/5: Verificando configuración...
echo.

REM Verificar ENCRYPTION_KEY
set ENCRYPTION_KEY=
for /f "tokens=2*" %%a in ('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v ENCRYPTION_KEY 2^>nul') do set ENCRYPTION_KEY=%%b

if defined ENCRYPTION_KEY (
    echo [OK] ENCRYPTION_KEY configurada en el sistema
) else (
    echo [WARNING] ENCRYPTION_KEY no se detecta (requiere reinicio)
)

REM Ejecutar test de cifrado
if exist "tests\test_encryption.php" (
    echo.
    echo Ejecutando test de cifrado...
    php tests\test_encryption.php
) else (
    echo [INFO] Test de cifrado no disponible
)

echo.

REM ─────────────────────────────────────────────────────────────
REM RESUMEN
REM ─────────────────────────────────────────────────────────────

echo ═══════════════════════════════════════════════════════════════
echo   CONFIGURACIÓN COMPLETADA
echo ═══════════════════════════════════════════════════════════════
echo.
echo Variables configuradas:
echo   - ENCRYPTION_KEY: 64 caracteres
echo   - BIOMETRIC_MODE: simulation
echo   - TOLERANCE_MINUTES: 10
echo.
echo Carpetas creadas:
echo   - logs/
echo   - uploads/
echo   - temp/
echo.
echo Próximos pasos:
echo   1. Reiniciar el servidor o servicios web
echo   2. Ejecutar migraciones: vendor\bin\phinx migrate
echo   3. Verificar acceso: http://localhost/sistema_biometrico
echo   4. Crear usuario admin: http://localhost/sistema_biometrico/register
echo.
echo ═══════════════════════════════════════════════════════════════
echo.

pause
