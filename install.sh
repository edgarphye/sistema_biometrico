#!/bin/bash
# Script de instalación para Sistema Biométrico - Linux/Mac

echo "============================================="
echo "Sistema Biométrico - Script de Instalación"
echo "============================================="
echo

# Verificar PHP
echo "[1/6] Verificando instalación de PHP..."
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP no está instalado"
    echo "Por favor instala PHP 7.4+ o superior"
    exit 1
fi
echo "PHP $(php --version | cut -d ' ' -f 2) instalado correctamente"

# Verificar MySQL/MariaDB
echo "[2/6] Verificando conexión a MySQL..."
if command -v mysql &> /dev/null; then
    echo "MySQL/MariaDB disponible"
else
    echo "ADVERTENCIA: MySQL/MariaDB no encontrado en PATH"
    echo "Asegúrate de que MySQL esté instalado y corriendo"
fi

# Crear directorios necesarios
echo "[3/6] Creando directorios necesarios..."
mkdir -p uploads/fotos_empleados
mkdir -p uploads/documentos
mkdir -p logs
mkdir -p soportes
echo "Directorios creados correctamente"

# Configurar permisos
echo "[4/6] Configurando permisos..."
chmod 755 uploads
chmod 755 logs
chmod 755 soportes
find uploads -type f -exec chmod 644 {} \;
find logs -type f -exec chmod 644 {} \;
echo "Permisos configurados correctamente"

# Verificar archivos de configuración
echo "[5/6] Verificando archivos de configuración..."
if [ ! -f "config.php" ]; then
    echo "ERROR: Archivo config.php no encontrado"
    exit 1
fi

if [ ! -f ".htaccess" ]; then
    echo "ERROR: Archivo .htaccess no encontrado"
    exit 1
fi

echo "Archivos de configuración encontrados"

# Importar base de datos (opcional)
echo "[6/6] Importando base de datos..."
read -p "¿Deseas importar la base de datos? (s/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Ss]$ ]]; then
    if [ -f "database/setup_database.sql" ]; then
        echo "Importando database/setup_database.sql..."
        read -p "Usuario MySQL (por defecto: root): " mysql_user
        mysql_user=${mysql_user:-root}
        mysql -u "$mysql_user" -p sistema_biometrico < database/setup_database.sql
        if [ $? -eq 0 ]; then
            echo "Base de datos importada correctamente"
        else
            echo "ERROR: No se pudo importar la base de datos"
            echo "Verifica las credenciales de MySQL"
        fi
    else
        echo "ERROR: Archivo database/setup_database.sql no encontrado"
    fi
else
    echo "Importación de base de datos omitida"
fi

echo
echo "============================================="
echo "Instalación completada exitosamente"
echo "============================================="
echo
echo "Configuración del servidor web:"
echo "- Apache: Asegurar que mod_rewrite esté activo"
echo "- Nginx: Usar nginx.conf como plantilla"
echo
echo "URLs de acceso:"
echo "- http://localhost/sistema_biometrico"
echo "- http://127.0.0.1/sistema_biometrico"
echo "- http://192.168.100.1/sistema_biometrico"
echo
echo "Usuarios de prueba:"
echo "- Usuario: admin, Contraseña: admin123"
echo "- Usuario: rh, Contraseña: rh123"
echo
echo "Siguiente pasos:"
echo "1. Configurar tu servidor web"
echo "2. Acceder al sistema desde tu navegador"
echo "3. Iniciar sesión con los usuarios de prueba"
echo "4. Probar todas las funcionalidades"
echo

# Verificar si podemos acceder al sistema
echo "Verificando acceso al sistema..."
if command -v curl &> /dev/null; then
    echo "Intentando conectar al sistema..."
    if curl -s -o /dev/null -w "%{http_code}" http://localhost/sistema_biometrico | grep -q "200\|302"; then
        echo "✓ Sistema accesible en http://localhost/sistema_biometrico"
    else
        echo "⚠ No se pudo acceder al sistema en http://localhost/sistema_biometrico"
        echo "  Verifica la configuración de tu servidor web"
    fi
else
    echo "curl no disponible para verificar el acceso al sistema"
fi

echo "¡Instalación completada!"