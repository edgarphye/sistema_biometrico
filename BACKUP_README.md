# 📦 Sistema de Backup - Sistema Biométrico

Este sistema proporciona una solución completa de backup y restauración para la base de datos del sistema biométrico, con soporte para backups completos, diferenciales, verificación de integridad y limpieza automática.

## 🚀 Características Principales

- ✅ **Backups Completos**: Exportación completa de todas las tablas y datos
- ⚡ **Backups Diferenciales**: Solo cambios desde el último backup completo
- 🔐 **Verificación de Integridad**: Hash SHA256 para cada backup
- 🗜️ **Compresión GZIP**: Reducción del tamaño de archivo hasta 90%
- 🔄 **Restauración Automática**: Restauración con validación de datos
- 🧹 **Limpieza Automática**: Eliminación de backups antiguos
- 📊 **Metadatos**: Información detallada de cada backup
- 📝 **Logs Completos**: Registro detallado de todas las operaciones

## 📁 Estructura de Archivos

```
sistema_biometrico/
├── backup_database.php     # Script PHP principal de backup
├── backup.sh               # Script de automatización (Linux/Mac)
├── backup.bat              # Script de automatización (Windows)
├── backups/
│   └── database/
│       ├── backup_completo_2024-12-01_10-30-00.sql.gz
│       ├── backup_completo_2024-12-01_10-30-00.sql.gz.sha256
│       ├── backup_completo_2024-12-01_10-30-00.json
│       └── ... (otros backups)
└── logs/
    └── backup.log
```

## 🛠️ Instalación y Configuración

### Requisitos Previos

- PHP 8.0 o superior
- MySQL/MariaDB
- Extensiones PHP: `pdo_mysql`, `json`, `zip`
- Permisos de escritura en directorios `backups/` y `logs/`

### Configuración Inicial

1. **Verificar permisos**:
   ```bash
   chmod +x backup.sh  # Linux/Mac
   ```

2. **Crear directorios necesarios**:
   ```bash
   mkdir -p backups/database logs
   ```

3. **Verificar configuración de base de datos** en `config.php`:
   ```php
   'database' => [
       'host' => 'localhost',
       'name' => 'sistema_biometrico',
       'user' => 'tu_usuario',
       'password' => 'tu_contraseña'
   ]
   ```

## 📖 Uso del Sistema de Backup

### Script de Automatización (Recomendado)

#### Linux/Mac:
```bash
# Backup completo automático (default)
./backup.sh

# Backup completo explícito
./backup.sh full

# Backup diferencial
./backup.sh differential

# Listar backups disponibles
./backup.sh list

# Restaurar backup específico
./backup.sh restore 20241201

# Verificar integridad de backups
./backup.sh verify

# Limpiar backups antiguos
./backup.sh cleanup

# Mostrar ayuda
./backup.sh help
```

#### Windows:
```cmd
REM Backup completo automático (default)
backup.bat

REM Backup completo explícito
backup.bat full

REM Backup diferencial
backup.bat differential

REM Listar backups disponibles
backup.bat list

REM Restaurar backup específico
backup.bat restore 20241201

REM Verificar integridad de backups
backup.bat verify

REM Limpiar backups antiguos
backup.bat cleanup
```

### Uso Directo del Script PHP

```bash
# Backup completo
php backup_database.php

# Backup diferencial
php backup_database.php --differential

# Listar backups
php backup_database.php --list

# Restaurar backup
php backup_database.php --restore 20241201

# Limpiar backups antiguos
php backup_database.php --cleanup
```

## 📋 Ejemplos Prácticos

### Escenario 1: Backup Diario Automático

Configurar un cron job (Linux/Mac):
```bash
# Editar crontab
crontab -e

# Agregar línea para backup diario a las 2 AM
0 2 * * * /ruta/al/proyecto/backup.sh auto >/dev/null 2>&1
```

Configurar tarea programada (Windows):
```cmd
# Crear tarea programada con el Programador de Tareas
schtasks /create /sc daily /st 02:00 /tn "BackupBiometrico" /tr "C:\ruta\al\proyecto\backup.bat auto"
```

### Escenario 2: Backup Antes de Actualización

```bash
# 1. Crear backup completo
./backup.sh full

# 2. Verificar integridad
./backup.sh verify

# 3. Realizar actualización del sistema

# 4. Si algo sale mal, restaurar
./backup.sh restore 20241201
```

### Escenario 3: Rotación de Backups

```bash
# Script de rotación mensual
#!/bin/bash
# Mantener últimos 7 backups completos
find backups/database/ -name "backup_completo_*.gz" -mtime +7 -delete

# Mantener todos los diferenciales de los últimos 2 días
find backups/database/ -name "backup_diferencial_*.gz" -mtime +2 -delete

# Limpiar archivos de metadatos huérfanos
find backups/database/ -name "*.json" -mtime +30 -delete
find backups/database/ -name "*.sha256" -mtime +30 -delete
```

## 🔍 Verificación y Restauración

### Verificación de Integridad

El sistema genera automáticamente:
- **Hash SHA256**: Para verificar que el backup no esté corrupto
- **Metadatos JSON**: Con información detallada del backup
- **Logs**: Registro de todas las operaciones

Comando de verificación:
```bash
./backup.sh verify
```

### Restauración

```bash
# Listar backups disponibles primero
./backup.sh list

# Restaurar backup específico
./backup.sh restore 20241201
```

El proceso de restauración:
1. ✅ Descomprime el archivo backup
2. ✅ Verifica integridad con hash SHA256
3. ✅ Inicia transacción en base de datos
4. ✅ Ejecuta todos los statements SQL
5. ✅ Confirma transacción si todo es exitoso
6. ✅ Rollback automático si hay error

## 📊 Formato de Nombres de Archivos

```
backup_[TIPO]_[FECHA]_[HORA].sql.gz
```

Ejemplos:
- `backup_completo_2024-12-01_10-30-00.sql.gz`
- `backup_diferencial_2024-12-01_14-15-22.sql.gz`

Archivos asociados:
- `.sha256`: Hash de verificación
- `.json`: Metadatos del backup

## 🗃️ Metadatos del Backup

Cada backup incluye un archivo JSON con:
```json
{
    "filename": "backup_completo_2024-12-01_10-30-00.sql",
    "type": "completo",
    "created_at": "2024-12-01 10:30:00",
    "size": 15728640,
    "tables": 15,
    "database": "sistema_biometrico",
    "server": "MySQL 8.0.25",
    "since_timestamp": null
}
```

## ⚠️ Consideraciones Importantes

### Rendimiento
- **Backups completos**: Toman más tiempo y espacio
- **Backups diferenciales**: Más rápidos y ligeros
- **Compresión**: Reduce tamaño en 70-90%
- **Índices**: Optimizados para consultas frecuentes

### Seguridad
- Los backups contienen **todos los datos del sistema**
- Almacenar en ubicación segura y accesible
- Considerar encriptación adicional si es necesario
- Restringir permisos de acceso a archivos de backup

### Almacenamiento
- **Espacio requerido**: Variable según tamaño de BD
- **Retención recomendada**: 7-30 días para backups completos
- **Limpieza automática**: Configurada por defecto

## 🆘 Solución de Problemas

### Error Comunes

**1. "PHP no está disponible"**
```bash
# Verificar instalación
php --version

# En Linux: instalar PHP
sudo apt-get install php php-mysql php-json

# En Windows: agregar PHP al PATH
```

**2. "Espacio en disco insuficiente"**
```bash
# Liberar espacio o mover backups a otro disco
./backup.sh cleanup

# O cambiar directorio de backups en backup_database.php
```

**3. "Error de conexión a base de datos"**
```bash
# Verificar configuración en config.php
# Verificar que el servicio MySQL esté activo
systemctl status mysql  # Linux
services.msc           # Windows
```

**4. "Hash inválido"**
```bash
# El backup puede estar corrupto, eliminarlo
./backup.sh verify
# Identificar archivos con hash inválido y eliminarlos manualmente
```

### Debug y Logs

Todos los errores se registran en `logs/backup.log`:
```bash
# Ver últimas líneas del log
tail -f logs/backup.log

# Buscar errores
grep "ERROR" logs/backup.log
```

## 🔄 Automatización Avanzada

### Script de Backup con Notificación

```bash
#!/bin/bash
./backup.sh auto

# Enviar notificación por email si falla
if [ $? -ne 0 ]; then
    echo "Backup fallido. Verificar logs: logs/backup.log" | \
    mail -s "ERROR: Backup Biométrico Fallido" admin@empresa.com
fi
```

### Backup a Nube

```bash
#!/bin/bash
# Después del backup exitoso, copiar a la nube
./backup.sh full

if [ $? -eq 0 ]; then
    # Copiar a AWS S3 (requiere AWS CLI configurada)
    aws s3 sync backups/database/ s3://mi-bucket/backups/ --delete
    
    # O a Google Drive (requiere rclone configurado)
    # rclone sync backups/database/ gdrive:backups/ --delete
fi
```

## 📞 Soporte

Para problemas o preguntas:
1. Verificar los logs en `logs/backup.log`
2. Revisar la configuración en `config.php`
3. Asegurar permisos adecuados en los directorios
4. Verificar requisitos de PHP y extensiónes

---

**Versión**: 1.0  
**Última Actualización**: 2024-12-01  
**Compatible con**: Sistema Biométrico v2.0+