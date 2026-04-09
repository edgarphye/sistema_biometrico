# Sistema Biométrico - Guía de Instalación Local

## Configuración de Entorno

### 1. Configuración de Servidores Web

#### Apache (XAMPP/WAMP/MAMP)
1. Copiar el archivo `.htaccess` al directorio raíz
2. Asegurar que `mod_rewrite` esté activo
3. Configurar `AllowOverride All` en el VirtualHost
4. Reiniciar Apache

#### Nginx
1. Copiar el archivo `nginx.conf` a la configuración de Nginx
2. Ajustar las rutas según tu instalación
3. Recargar configuración: `nginx -s reload`

### 2. Configuración de Acceso

#### Opciones de Acceso:
- **Localhost:** `http://localhost/sistema_biometrico` (o el subdirectorio definido en `BASE_URL`)
- **IP Local:** `http://127.0.0.1/sistema_biometrico`
- **Red Local:** `http://192.168.100.1/sistema_biometrico`

#### Configuración del archivo hosts:
1. Editar `C:\Windows\System32\drivers\etc\hosts` (como administrador)
2. Agregar: `127.0.0.1 sistema.local`
3. Acceder via: `http://sistema.local/sistema_biometrico` (o el subdirectorio definido en `BASE_URL`)

### 3. Base de Datos

#### MySQL/MariaDB
1. Crear base de datos: `sistema_biometrico`
2. Importar el archivo SQL de configuración
3. Verificar credenciales en `config.php`

#### Configuración de conexión:
```php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_biometrico');
```

### 4. Permisos y Directorios

#### Crear directorios necesarios:
```bash
mkdir uploads
mkdir uploads/fotos_empleados
mkdir uploads/documentos
mkdir logs
mkdir soportes
```

#### Permisos (Linux/Mac):
```bash
chmod 755 uploads
chmod 755 logs
chmod 755 soportes
chmod 644 *.php *.log
```

### 5. Configuración de PHP

#### Requisitos:
- PHP 7.4+ o 8.0+
- Extensiones: `pdo_mysql`, `gd`, `curl`, `mbstring`
- Límites de memoria: `256M`
- Upload: `20M`

#### Configuración en php.ini:
```ini
memory_limit = 256M
upload_max_filesize = 20M
post_max_size = 20M
max_execution_time = 300
```

## Verificación de Funcionalidades

### 1. Acceso al Sistema
- [ ] Cargar la página principal
- [ ] Verificar redirección al login
- [ ] Probar formulario de registro
- [ ] Verificar autenticación 2FA

### 2. CRUD de Empleados
- [ ] Crear nuevo empleado
- [ ] Editar empleado existente
- [ ] Subir foto de empleado
- [ ] Validar datos de RFC/CURP
- [ ] Eliminar empleado

### 3. Sistema Biométrico
- [ ] Conectar dispositivo biométrico
- [ ] Capturar huella dactilar
- [ ] Registrar entrada/salida
- [ ] Verificar identidad
- [ ] Sincronizar datos

### 4. Gestión de Asistencia
- [ ] Ver registros de asistencia
- [ ] Filtrar por fecha/empleado
- [ ] Exportar reportes
- [ ] Calcular horas laborales
- [ ] Justificar ausencias

### 5. Administración
- [ ] Gestionar horarios
- [ ] Crear comisiones
- [ ] Aplicar sanciones
- [ ] Generar reportes
- [ ] Gestionar usuarios

## Troubleshooting

### Problemas Comunes:

#### 1. Error 404
- Verificar que `mod_rewrite` esté activo (Apache)
- Verificar configuración de `nginx.conf` (Nginx)
- Revisar `.htaccess`

#### 2. Error de Base de Datos
- Verificar credenciales en `config.php`
- Confirmar que la base de datos exista
- Verificar permisos del usuario MySQL

#### 3. Errores de Permiso
- Verificar permisos de directorios
- Revisar ownership de archivos
- Comprobar configuración del webserver

#### 4. Sesiones No Funcionan
- Verificar `session.save_path` en php.ini
- Confirmar que exista el directorio de sesiones
- Revisar permisos del directorio

#### 5. Uploads No Funcionan
- Verificar `upload_max_filesize` y `post_max_size`
- Revisar permisos del directorio uploads
- Confirmar espacio en disco disponible

### Logs Importantes:
- `logs/biometric_system.log` - Logs del sistema
- `logs/php_errors.log` - Errores de PHP
- `logs/sistema_biometrico_error.log` - Errores del webserver

## URLs de Prueba

### URLs Principales:
```bash
# Reemplaza <BASE_URL> con el valor de tu config.php (ej. /sistema_biometrico)
http://localhost:8080<BASE_URL>/login
http://localhost:8080<BASE_URL>/dashboard
http://localhost:8080<BASE_URL>/empleados
http://localhost:8080<BASE_URL>/asistencia
```

### URLs de APIs:
```
http://localhost/sistema_biometrico/api/empleados
http://localhost/sistema_biometrico/api/asistencia
http://localhost/sistema_biometrico/api/dispositivos
```

## Checklist Final

Antes de poner en producción:

- [ ] Todos los tests funcionan
- [ ] HTTPS configurado
- [ ] Logs de errores revisados
- [ ] Backup de base de datos
- [ ] Documentación actualizada
- [ ] Monitoreo configurado
- [ ] Licencias verificadas
- [ ] Capacitación completada