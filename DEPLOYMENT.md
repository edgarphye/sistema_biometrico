# ═══════════════════════════════════════════════════════════════
# GUÍA DE DESPLIEGUE A PRODUCCIÓN
# Sistema Biométrico - Multi-Sede (35 Dispositivos)
# ═══════════════════════════════════════════════════════════════

## 📋 PRE-REQUISITOS

- Windows Server 2016+ o Windows 10/11
- PHP 7.4+ con extensiones: pdo_mysql, openssl, json, mbstring
- MySQL 8.0+ o MariaDB 10.5+
- Nginx o Apache 2.4+
- Acceso administrativo al servidor

---

## 🚀 PROCESO DE INSTALACIÓN

### PASO 1: Preparar el Servidor

#### 1.1 Clonar/Copiar Archivos
```bash
# Copiar todos los archivos del proyecto a:
C:\inetpub\wwwroot\sistema_biometrico
# o
C:\xampp\htdocs\sistema_biometrico
```

#### 1.2 Verificar PHP y Extensiones
```cmd
php -v
php -m | findstr pdo_mysql
php -m | findstr openssl
php -m | findstr json
```

---

### PASO 2: Configurar Base de Datos

#### 2.1 Crear Base de Datos
```sql
-- Conectar a MySQL como root
mysql -u root -p

-- Crear base de datos
CREATE DATABASE sistema_biometrico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario (CAMBIAR contraseña en producción)
CREATE USER 'biometrico_user'@'localhost' IDENTIFIED BY 'TuContraseñaSegura123!';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON sistema_biometrico.* TO 'biometrico_user'@'localhost';
FLUSH PRIVILEGES;

-- Salir
EXIT;
```

#### 2.2 Importar Esquema Base
```cmd
cd C:\inetpub\wwwroot\sistema_biometrico
mysql -u root -p sistema_biometrico < database.sql
```

#### 2.3 Ejecutar Migraciones
```cmd
# Migración de sanciones y retardos
php migrations/run_migration.php migrations/20251119_add_sanciones_retardos_soporte.sql

# Migración de dispositivos multi-sede (35 dispositivos)
php migrations/setup_dispositivos.php
```

#### 2.4 Verificar Tablas
```cmd
php migrations/verify_tables.php
```

---

### PASO 3: Configurar Variables de Entorno

#### 3.1 Configurar ENCRYPTION_KEY (CRÍTICO)

**Opción A: Variables del Sistema (Recomendado)**
```cmd
# Ejecutar CMD como Administrador
setx ENCRYPTION_KEY "3d7e0f3fb2533d2d8c1f534ba0301632a298c17c3bb26fd7ce5db2ae2153d5d3" /M
```

**Opción B: Interfaz Gráfica**
1. `Win + R` → `sysdm.cpl` → Enter
2. Pestaña "Opciones avanzadas"
3. "Variables de entorno"
4. En "Variables del sistema" → "Nueva"
5. Nombre: `ENCRYPTION_KEY`
6. Valor: `3d7e0f3fb2533d2d8c1f534ba0301632a298c17c3bb26fd7ce5db2ae2153d5d3`

#### 3.2 Variables Adicionales (Opcional)
```cmd
setx BIOMETRIC_MODE "sdk" /M
setx BIOMETRIC_API_URL "https://api.zkteco.com" /M
setx TOLERANCE_MINUTES "10" /M
setx APP_NAME "Sistema Biométrico Producción" /M
```

#### 3.3 Verificar Configuración
```cmd
php -r "echo getenv('ENCRYPTION_KEY');"
php tests/test_encryption.php
```

---

### PASO 4: Configurar Servidor Web

#### 4.1 Apache (httpd.conf o .htaccess)
```apache
<Directory "C:/inetpub/wwwroot/sistema_biometrico">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
    
    # Habilitar mod_rewrite
    RewriteEngine On
    RewriteBase /sistema_biometrico/
    
    # Redirigir todo a index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [L,QSA]
</Directory>

# Habilitar módulos requeridos
LoadModule rewrite_module modules/mod_rewrite.so
LoadModule php_module "C:/php/php8apache2_4.dll"
PHPIniDir "C:/php"
```

#### 4.2 Nginx (nginx.conf)
```nginx
server {
    listen 80;
    server_name biometrico.tuempresa.com;
    root C:/inetpub/wwwroot/sistema_biometrico;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }

    location /uploads {
        internal;
        alias C:/inetpub/wwwroot/sistema_biometrico/uploads;
    }
}
```

---

### PASO 5: Configurar Permisos

```cmd
# Dar permisos de escritura a carpetas necesarias
icacls "uploads" /grant "IIS_IUSRS:(OI)(CI)F" /T
icacls "logs" /grant "IIS_IUSRS:(OI)(CI)F" /T
icacls "temp" /grant "IIS_IUSRS:(OI)(CI)F" /T
```

---

### PASO 6: Reiniciar Servicios

```cmd
# Apache
net stop Apache2.4
net start Apache2.4

# Nginx + PHP-FPM
net stop nginx
net stop php-cgi
net start php-cgi
net start nginx

# IIS
iisreset
```

---

### PASO 7: Crear Usuario Administrador

```cmd
php -f scripts/create_admin.php
# O acceder a: http://tu-servidor/sistema_biometrico/register
```

---

### PASO 8: Verificación Final

#### 8.1 Health Check
```cmd
php tests/test_refactor_verification.php
php tests/test_encryption.php
php tests/test_retardos_sancion_flow.php
```

#### 8.2 Verificar Acceso Web
```
http://tu-servidor/sistema_biometrico/
http://tu-servidor/sistema_biometrico/login
http://tu-servidor/sistema_biometrico/dispositivos
```

#### 8.3 Verificar Dispositivos Biométricos
```
http://tu-servidor/sistema_biometrico/dispositivos
# Debe mostrar 35 dispositivos configurados
```

---

## 🔒 SEGURIDAD POST-INSTALACIÓN

### 1. Archivo .env.production
```cmd
# Mover fuera del webroot
move .env.production C:\config\sistema_biometrico\.env
icacls "C:\config\sistema_biometrico\.env" /inheritance:r
icacls "C:\config\sistema_biometrico\.env" /grant "Administrators:F"
```

### 2. Proteger Carpetas Sensibles
```cmd
# Denegar acceso web a carpetas sensibles
echo Order deny,allow > uploads\.htaccess
echo Deny from all >> uploads\.htaccess

echo Order deny,allow > logs\.htaccess
echo Deny from all >> logs\.htaccess
```

### 3. SSL/TLS (Recomendado)
```cmd
# Instalar certificado SSL
# Configurar redirección HTTPS en Apache/Nginx
```

### 4. Firewall
```cmd
# Permitir solo puertos necesarios
netsh advfirewall firewall add rule name="HTTP" dir=in action=allow protocol=TCP localport=80
netsh advfirewall firewall add rule name="HTTPS" dir=in action=allow protocol=TCP localport=443
```

---

## 📊 MONITOREO Y MANTENIMIENTO

### Logs a Monitorear
```
logs/sistema_biometrico.log
logs/apache_error.log (Apache)
logs/nginx_error.log (Nginx)
```

### Backup Automático (Script Windows)
```cmd
@echo off
set BACKUP_DIR=C:\backups\biometrico
set DATE=%date:~-4,4%%date:~-7,2%%date:~-10,2%

mkdir "%BACKUP_DIR%\%DATE%"

REM Backup Base de Datos
mysqldump -u root -p sistema_biometrico > "%BACKUP_DIR%\%DATE%\db_backup.sql"

REM Backup Archivos
xcopy "C:\inetpub\wwwroot\sistema_biometrico\uploads" "%BACKUP_DIR%\%DATE%\uploads" /E /I

echo Backup completado: %DATE%
```

### Actualizar Dispositivos
```
1. Acceder a /dispositivos
2. Editar dispositivo
3. Actualizar IP/Puerto/Configuración
4. Sincronizar empleados
```

---

## 🆘 TROUBLESHOOTING

### Problema: ENCRYPTION_KEY no se detecta
```cmd
# Verificar
echo %ENCRYPTION_KEY%

# Reconfigurar
setx ENCRYPTION_KEY "TU_CLAVE_AQUI" /M

# Reiniciar servicios
net stop Apache2.4 && net start Apache2.4
```

### Problema: Error de conexión a MySQL
```cmd
# Verificar servicio
sc query MySQL80

# Reiniciar
net stop MySQL80
net start MySQL80

# Verificar credenciales en config.php
```

### Problema: Dispositivos no se conectan
```
1. Verificar tabla: dispositivos_biometricos
2. Probar conexión desde /dispositivos (botón Test)
3. Verificar IPs y puertos
4. Modo: Cambiar a "simulation" si SDK no disponible
```

---

## 📞 SOPORTE

Para más información:
- Documentación: /docs
- Logs: /logs
- Tests: /tests

---

## ✅ CHECKLIST DE PRODUCCIÓN

- [ ] Base de datos creada e importada
- [ ] Migraciones ejecutadas (35 dispositivos creados)
- [ ] ENCRYPTION_KEY configurada en variables de sistema
- [ ] Variables de entorno configuradas
- [ ] Servidor web configurado (Apache/Nginx)
- [ ] Permisos de carpetas configurados
- [ ] Servicios reiniciados
- [ ] Usuario admin creado
- [ ] Tests ejecutados exitosamente
- [ ] Acceso web verificado
- [ ] 35 dispositivos visibles en /dispositivos
- [ ] SSL/TLS configurado (recomendado)
- [ ] Backup programado
- [ ] Monitoreo configurado

---

**Fecha de última actualización**: 2025-11-26
**Versión**: 1.0
**Sistema**: Windows Server / PHP / MySQL
