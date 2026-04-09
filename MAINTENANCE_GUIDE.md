# 🔧 PANEL DE MANTENIMIENTO - SISTEMA BIOMÉTRICO

Esta guía explica el uso completo del sistema de mantenimiento unificado que integra todas las herramientas necesarias para administración del sistema biométrico.

## 🚀 INICIO RÁPIDO

### Ejecutar el Panel de Mantenimiento

#### Linux/Mac:
```bash
# Modo interactivo (recomendado)
php maintenance.php

# Backup automático
php maintenance.php --backup

# Diagnóstico completo
php maintenance.php --health-check
```

#### Windows:
```cmd
# Modo interactivo
maintenance.bat

# Backup automático
maintenance.bat --backup

# Diagnóstico completo
maintenance.bat --health-check
```

## 📋 MENÚ DE OPCIONES PRINCIPAL

### 🔍 1. ESTADO DEL SISTEMA
Muestra información completa sobre:
- **Base de Datos**: Versión, conexiones, tiempo activo
- **Tablas Principales**: Registro count de cada tabla crítica
- **Servicio Web**: PHP version, memoria usada
- **Logs Recientes**: Últimas entradas del sistema

### 💾 2. GESTIÓN DE BACKUPS
**Sub-opciones:**
- **Backup Completo**: Exportación full con compresión
- **Backup Diferencial**: Solo cambios desde último completo
- **Listar Backups**: Ver todos los backups disponibles
- **Verificar Integridad**: Validar hashes SHA256
- **Eliminar Antiguos**: Limpieza automática
- **Programar Automático**: Configurar backups programados

### 🔄 3. RESTAURACIÓN DE BASE DE DATOS
**Sub-opciones:**
- **Modo Seguro**: Con backup automático y verificación
- **Modo Rápido**: Sin verificación (emergencias)
- **Simulación (Dry-Run)**: Prueba sin modificar datos
- **Selección**: Elegir backup específico de lista
- **Verificación Post-Resto**: Validar datos restaurados

### 🧹 4. LIMPIEZA Y OPTIMIZACIÓN
**Sub-opciones:**
- **Limpiar Logs**: Eliminar logs antiguos
- **Optimizar BD**: `OPTIMIZE TABLE` para todas las tablas
- **Archivos Temporales**: Limpiar archivos temporales
- **Comprimir Backups**: Reducir tamaño de backups antiguos
- **Sesiones**: Limpiar sesiones expiradas
- **Reindexar**: Reconstruir índices para mejor rendimiento

### 🔍 5. DIAGNÓSTICO Y VERIFICACIÓN
**Sub-opciones:**
- **Integridad BD**: Verificar estructura y datos
- **Estructura Tablas**: Validar esquema completo
- **Foreign Keys**: Verificar relaciones
- **Análisis Rendimiento**: Identificar cuellos de botella
- **Configuración PHP**: Validar settings críticos
- **Diagnóstico Biométrico**: Test de dispositivos biométricos
- **Conexión Dispositivos**: Verificar conectividad

### ⚙️ 6. CONFIGURACIÓN AVANZADA
**Sub-opciones:**
- **Verificar Config Actual**: Estado actual del sistema
- **Actualizar BD**: Ejecutar migraciones pendientes
- **Variables Entorno**: Configurar variables de entorno
- **Cifrado Biométrico**: Configurar clave de encriptación
- **Notificaciones**: Configurar alertas y emails
- **Importar/Exportar**: Gestión de configuración
- **Reset Configuración**: Restaurar defaults

### 📈 7. REPORTES Y ESTADÍSTICAS
**Sub-opciones:**
- **Reporte Asistencia**: Últimos 30 días con gráficos
- **Retardos y Sanciones**: Estadísticas disciplinarias
- **Estadísticas Uso**: Métricas de utilización
- **Reporte Dispositivos**: Estado y actividad de dispositivos
- **Auditoría**: Bitácora de cambios importantes
- **Exportar (Excel/PDF)**: Descargar reportes en múltiples formatos
- **Análisis Tendencias**: Patrones y proyecciones

### 🔐 8. SEGURIDAD Y AUDITORÍA
**Sub-opciones:**
- **Permisos Archivos**: Verificar permisos críticos
- **Auditoría Usuarios**: Reporte de usuarios y roles
- **Contraseñas Débiles**: Scan de contraseñas inseguras
- **Logs Seguridad**: Análisis de eventos de seguridad
- **Configuración HTTPS**: Verificar configuración SSL/TLS
- **Vulnerabilidades**: Scan de seguridad básico
- **Reporte Seguridad**: Generar reporte completo

### 🚀 9. TAREAS AUTOMÁTICAS
**Sub-opciones:**
- **Backup Diario**: Programar backup automático (2 AM)
- **Limpieza Semanal**: Mantenimiento programado (Domingo 3 AM)
- **Monitoreo**: Configurar vigilancia continua
- **Reportes Automáticos**: Generar reportes periódicos
- **Notificaciones**: Configurar alertas por email/SMS
- **Ver Tareas**: Mostrar tareas programadas
- **Ejecutar Todas**: Forzar ejecución inmediata

## 🛠️ USO AVANZADO

### Línea de Comandos Directa

#### Backup y Restauración:
```bash
# Backup completo
php maintenance.php --backup

# Restaurar backup específico
php maintenance.php --restore 2024-12-01

# Limpieza completa
php maintenance.php --cleanup

# Diagnóstico completo
php maintenance.php --health-check
```

#### Automatización con Cron (Linux):
```bash
# Editar crontab
crontab -e

# Agregar tareas
0 2 * * * /ruta/al/proyecto/php maintenance.php --backup >/dev/null 2>&1
0 3 * * 0 /ruta/al/proyecto/php maintenance.php --cleanup >/dev/null 2>&1
0 6 * * 1 /ruta/al/proyecto/php maintenance.php --health-check >/dev/null 2>&1
```

#### Tareas Programadas (Windows):
```cmd
# Backup diario a las 2 AM
schtasks /create /tn "BackupBiometrico" /tr "C:\ruta\al\proyecto\maintenance.bat --backup" /sc daily /st 02:00 /ru SYSTEM

# Limpieza semanal domingo 3 AM
schtasks /create /tn "LimpiezaBiometrico" /tr "C:\ruta\al\proyecto\maintenance.bat --cleanup" /sc weekly /d SUN /st 03:00 /ru SYSTEM
```

### Configuración de Variables de Entorno

#### Archivo `.env`:
```bash
# Base de Datos
DB_HOST=localhost
DB_USER=sistema_user
DB_PASS=secure_password
DB_NAME=sistema_biometrico

# Seguridad
ENCRYPTION_KEY=tu_llave_32_bytes_aqui
JWT_SECRET=tu_secreto_jwt

# Email
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=tu_email@gmail.com
SMTP_PASS=tu_app_password
EMAIL_FROM= Sistema Biométrico <noreply@empresa.com>
EMAIL_TO=admin@empresa.com

# Logs
LOG_LEVEL=INFO
LOG_MAX_SIZE=10485760
LOG_ROTATE_COUNT=5
```

## 📊 MÉTRICAS Y MONITOREO

### Indicadores Clave de Rendimiento (KPIs)

#### Disponibilidad:
- **Uptime del sistema**: > 99.5%
- **Base de datos activa**: Conexiones simultáneas < 100
- **Dispositivos biométricos**: > 95% online

#### Rendimiento:
- **Tiempo respuesta**: < 2 segundos
- **Backup completado**: < 5 minutos
- **Memoria usada**: < 512 MB

#### Calidad de Datos:
- **Registros de asistencia**: > 95% completos
- **Errores de biométrico**: < 1%
- **Integridad de backups**: 100% verificados

### Alertas Configurables

#### Críticas (Notificación inmediata):
- Sistema caído
- Base de datos no disponible
- Backup fallido 3 días consecutivos
- Dispositivo biométrico offline > 1 hora

#### Advertencias (Notificación diaria):
- Memoria > 80%
- Espacio en disco < 10%
- Backup con errores de integridad
- Tasa de errores biométricos > 2%

#### Informativas (Notificación semanal):
- Reporte de uso del sistema
- Estadísticas de asistencia
- Mantenimiento programado completado

## 🔧 TRUCOS Y TIPS AVANZADOS

### Optimización de Rendimiento:
```bash
# Limpiar y optimizar todas las tablas
php -r "
require_once 'config.php'; require_once 'models/Database.php';
\$db = new Database(); \$conn = \$db->getConnection();
\$tables = \$conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach (\$tables as \$table) {
    echo \"Optimizando: \$table\\n\";
    \$conn->query(\"OPTIMIZE TABLE \$table\");
}
"
```

### Diagnóstico Rápido:
```bash
# Verificar estado completo en un comando
php maintenance.php --health-check | grep -E "(✅|❌|⚠️)"
```

### Backup y Restauración en Pipeline:
```bash
# Backup y verificación en un solo comando
php maintenance.php --backup && \
php restore_database.php --dry-run $(date +%Y-%m-%d) && \
echo "✅ Sistema listo para restauración"
```

### Monitoreo Continuo:
```bash
# Script para monitoreo continuo
#!/bin/bash
while true; do
    php maintenance.php --health-check > /tmp/health_status.txt
    
    if grep -q "❌" /tmp/health_status.txt; then
        echo "⚠️ Problemas detectados - Enviando alerta"
        # Envía email o notificación aquí
    fi
    
    sleep 300  # Verificar cada 5 minutos
done
```

## 🚨 SOLUCIÓN DE PROBLEMAS COMUNES

### Error: "PHP no está disponible"
```bash
# Verificar instalación PHP
php --version

# Si no está instalado (Ubuntu/Debian):
sudo apt-get update
sudo apt-get install php php-mysql php-cli

# En Windows: descargar desde php.net o usar XAMPP/WAMP
```

### Error: "Base de datos no accesible"
```bash
# Verificar configuración en config.php
php -r "
require_once 'config.php';
try {
    \$db = new Database();
    \$db->getConnection()->query('SELECT 1');
    echo '✅ Conexión OK';
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage();
}
"

# Verificar servicio MySQL
systemctl status mysql  # Linux
services.msc           # Windows
```

### Error: "Permisos insuficientes"
```bash
# Corregir permisos (Linux/Mac)
chmod 755 maintenance.php
chmod 755 backup_database.php
chmod 755 restore_database.php
chmod -R 755 backups/
chmod -R 755 logs/

# Verificar propietario
ls -la backups/ logs/
```

### Error: "Espacio en disco insuficiente"
```bash
# Verificar espacio disponible
df -h

# Limpiar backups antiguos
php maintenance.php --cleanup

# O mover backups a otro disco
sudo mv backups/database/* /otro/disco/backups/
```

## 📈 MEJORAS CONTINUAS

### Mantenimiento Preventivo Semanal:
1. **Domingo 2 AM**: Backup completo automático
2. **Domingo 3 AM**: Limpieza y optimización
3. **Domingo 4 AM**: Diagnóstico completo
4. **Domingo 5 AM**: Generación de reportes semanales

### Mantenimiento Mensual:
1. **Primero día**: Verificación de seguridad completa
2. **Segundo día**: Actualización de sistema y dependencias
3. **Tercer día**: Revisión de logs y ajustes de configuración
4. **Último día**: Reporte mensual de mantenimiento

### Checklist de Verificación Mensual:
- [ ] Todos los backups completados exitosamente
- [ ] Integridad de backups verificada
- [ ] Espacio en disco > 20% disponible
- [ ] Logs sin errores críticos
- [ ] Dispositivos biométricos funcionando
- [ ] Alertas configuradas funcionando
- [ ] Reportes generados correctamente

## 📞 SOPORTE Y CONTACTO

### Archivos Importantes:
- **Logs del sistema**: `logs/backup.log`
- **Logs de mantenimiento**: `logs/maintenance.log`
- **Configuración**: `config.php`, `.env`
- **Backups**: `backups/database/`

### Comandos de Emergencia:
```bash
# Backup de emergencia rápido
php maintenance.php --backup

# Estado crítico del sistema
php maintenance.php --health-check > emergency_status.txt
cat emergency_status.txt

# Restauración de último backup conocido
php restore_database.php --restore-interactive $(ls -t backups/database/backup_completo_*.sql.gz | head -1 | grep -o '[0-9]\{4\}-[0-9]\{2\}-[0-9]\{2\}')
```

### Guía de Escalado:
1. **Nivel 1**: Administrador del sistema - Usar herramientas locales
2. **Nivel 2**: Equipo de soporte - Verificar logs y configuración
3. **Nivel 3**: Desarrollo del sistema - Modificaciones de código o configuración avanzada

---

**Versión**: 1.0  
**Última Actualización**: 2024-12-01  
**Compatible con**: Sistema Biométrico v2.0+

## 🎯 COMANDOS RÁPIDOS DE REFERENCIA

```bash
# Panel interactivo completo
php maintenance.php

# Operaciones comunes
php maintenance.php --backup
php maintenance.php --cleanup
php maintenance.php --health-check

# Restauración
php restore_database.php --list
php restore_database.php --restore-interactive YYYY-MM-DD
php restore_database.php --dry-run YYYY-MM-DD

# Scripts Windows
maintenance.bat
restore.bat
backup.bat

# Verificación y diagnóstico
tail -f logs/backup.log
tail -f logs/maintenance.log
df -h
php --version
mysql --version
```

---

**Nota importante**: Antes de ejecutar cualquier operación de mantenimiento, asegúrese de tener acceso de administrador y notificar a los usuarios sobre posibles interrupciones del servicio.