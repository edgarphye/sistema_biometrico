@echo off
REM Script de instalación para Sistema Biométrico - Windows
REQUIRES: PHP, MySQL/MariaDB, Apache/Nginx

echo =============================================
echo Sistema Biométrico - Script de Instalación
echo =============================================
echo.

REM Verificar PHP
echo [1/6] Verificando instalación de PHP...
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP no está instalado o no está en el PATH
    echo Por favor instala PHP y agrega al PATH del sistema
    pause
    exit /b 1
)
echo PHP instalado correctamente

REM Verificar MySQL
echo [2/6] Verificando conexión a MySQL...
mysql --version >nul 2>&1
if errorlevel 1 (
    echo ADVERTENCIA: MySQL no está en el PATH
    echo Asegúrate de que MySQL esté instalado y corriendo
    echo.
) else (
    echo MySQL instalado correctamente
)

REM Crear directorios necesarios
echo [3/6] Creando directorios necesarios...
if not exist "uploads" mkdir uploads
if not exist "uploads\fotos_empleados" mkdir uploads\fotos_empleados
if not exist "uploads\documentos" mkdir uploads\documentos
if not exist "logs" mkdir logs
if not exist "soportes" mkdir soportes
echo Directorios creados correctamente

REM Verificar archivos de configuración
echo [4/6] Verificando archivos de configuración...
if not exist "config.php" (
    echo ERROR: Archivo config.php no encontrado
    pause
    exit /b 1
)
if not exist ".htaccess" (
    echo ERROR: Archivo .htaccess no encontrado
    pause
    exit /b 1
)
echo Archivos de configuración encontrados

REM Importar base de datos (opcional)
echo [5/6] Importando base de datos...
set /p importar="¿Deseas importar la base de datos? (s/n): "
if /i "%importar%"=="s" (
    if exist "database\setup_database.sql" (
        echo Importando database\setup_database.sql...
        mysql -u root -p sistema_biometrico < database\setup_database.sql
        if errorlevel 1 (
            echo ERROR: No se pudo importar la base de datos
            echo Verifica las credenciales de MySQL
        ) else (
            echo Base de datos importada correctamente
        )
    ) else (
        echo ERROR: Archivo database\setup_database.sql no encontrado
    )
) else (
    echo Importación de base de datos omitida
)

REM Verificar configuración de webserver
echo [6/6] Configuración del servidor web...
echo.
echo El sistema está configurado para funcionar con:
echo - Apache: Copiar .htaccess al directorio raíz
echo - Nginx: Usar nginx.conf como plantilla
echo.
echo URLs de acceso:
echo - http://localhost/sistema_biometrico
echo - http://127.0.0.1/sistema_biometrico
echo - http://192.168.100.1/sistema_biometrico
echo.

REM Usuarios por defecto
echo Usuarios de prueba:
echo - Usuario: admin, Contraseña: admin123
echo - Usuario: rh, Contraseña: rh123
echo.

echo =============================================
echo Instalación completada exitosamente
echo =============================================
echo.
echo Siguiente pasos:
echo 1. Configurar tu servidor web (Apache/Nginx)
echo 2. Acceder al sistema desde tu navegador
echo 3. Iniciar sesión con los usuarios de prueba
echo 4. Probar todas las funcionalidades
echo.
pause