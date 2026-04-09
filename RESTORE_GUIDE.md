# 🔄 GUÍA COMPLETA DE RESTAURACIÓN - SISTEMA BIOMÉTRICO

Esta guía explica cómo restaurar completamente la base de datos del sistema biométrico usando todos los métodos disponibles.

## 🚨 ADVERTENCIAS IMPORTANTES

- ⚠️ **LA RESTAURACIÓN SOBRESCRIBE TODOS LOS DATOS ACTUALES**
- ⚠️ **Realice siempre un backup antes de restaurar**
- ⚠️ **Asegúrese de que el backup sea de confianza**
- ⚠️ **Detenga todas las aplicaciones conectadas a la BD**

## 📁 REQUISITOS PREVIOS

### 1. Verificar Disponibilidad
```bash
# Verificar PHP
php --version

# Verificar conexión a base de datos
php -r "require_once 'config.php'; require_once 'models/Database.php'; \$db = new Database(); echo 'Conexión OK';"
```

### 2. Permisos Necesarios
- Lectura del directorio `backups/database/`
- Escritura en directorio `logs/`
- Permisos de administrador en la base de datos

### 3. Verificar Estado Actual
```bash
# Verificar tablas actuales
php -r "
require_once 'config.php'; 
require_once 'models/Database.php'; 
\$db = new Database(); 
\$tables = \$db->getConnection()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); 
echo 'Tablas actuales: ' . implode(', ', \$tables);
"
```

## 🛠️ MÉTODOS DE RESTAURACIÓN

### MÉTODO 1: Script Interactivo (RECOMENDADO)

#### Linux/Mac:
```bash
./restore.sh
```

#### Windows:
```cmd
restore.bat
```

**Ventajas:**
- ✅ Menú amigable y visual
- ✅ Múltiples opciones de seguridad
- ✅ Verificación automática de integridad
- ✅ Backup de seguridad automático

**Opciones del Menú:**
1. **Listar backups disponibles** - Muestra todos los backups con información detallada
2. **Restaurar backup (modo seguro)** - Con backup automático y verificación
3. **Restaurar backup (modo rápido)** - Sin verificación (solo para emergencias)
4. **Simular restauración (dry-run)** - Prueba sin modificar datos
5. **Verificar integridad de backups** - Comprueba hashes SHA256
6. **Crear backup de seguridad** - Backup actual antes de cualquier cambio

---

### MÉTODO 2: Línea de Comandos Directa

#### Listar Backups:
```bash
php restore_database.php --list
```

#### Restauración Básica:
```bash
php restore_database.php --restore 2024-12-01
```

#### Restauración Interactiva:
```bash
php restore_database.php --restore-interactive 2024-12-01
```

#### Simulación (Dry-Run):
```bash
php restore_database.php --dry-run 2024-12-01
```

---

### MÉTODO 3: Script de Backup Original

#### Restauración Simple:
```bash
php backup_database.php --restore 2024-12-01
```

#### Listar Backups:
```bash
php backup_database.php --list
```

---

### MÉTODO 4: Restauración Manual (Avanzado)

#### Paso 1: Descomprimir Backup
```bash
gunzip -c backups/database/backup_completo_2024-12-01_10-30-00.sql.gz > restore_temp.sql
```

#### Paso 2: Verificar Integridad
```bash
# Comparar hash
sha256sum backups/database/backup_completo_2024-12-01_10-30-00.sql.gz
cat backups/database/backup_completo_2024-12-01_10-30-00.sql.gz.sha256
```

#### Paso 3: Restaurar con MySQL
```bash
mysql -u [usuario] -p [database] < restore_temp.sql

# Limpiar archivo temporal
rm restore_temp.sql
```

## 📋 PROCESO DE RESTAURACIÓN COMPLETO

### Paso 1: Preparación
```bash
# 1. Detener servicios del sistema biométrico
# 2. Notificar a usuarios sobre mantenimiento
# 3. Verificar backup a restaurar
php restore_database.php --list

# 4. Crear backup de seguridad
php backup_database.php
```

### Paso 2: Simulación (Recomendado)
```bash
# Probar restauración sin modificar datos
php restore_database.php --dry-run 2024-12-01
```

### Paso 3: Restauración Real
```bash
# Restauración completa con verificación
php restore_database.php --restore-interactive 2024-12-01
```

### Paso 4: Verificación Post-Restauración
```bash
# Verificar tablas
php -r "
require_once 'config.php'; 
require_once 'models/Database.php'; 
\$db = new Database(); 
\$tables = \$db->getConnection()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); 
echo 'Tablas restauradas: ' . count(\$tables);
echo PHP_EOL;
foreach (\$tables as \$table) {
    \$count = \$db->getConnection()->query(\"SELECT COUNT(*) FROM \$table\")->fetchColumn();
    echo \"- \$table: \$count registros\" . PHP_EOL;
}
"

# Verificar registros críticos
php -r "
require_once 'config.php'; 
require_once 'models/Database.php'; 
\$db = new Database(); 
\$conn = \$db->getConnection(); 
echo 'Usuarios: ' . \$conn->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() . PHP_EOL;
echo 'Empleados: ' . \$conn->query('SELECT COUNT(*) FROM empleados')->fetchColumn() . PHP_EOL;
echo 'Asistencia: ' . \$conn->query('SELECT COUNT(*) FROM asistencia')->fetchColumn() . PHP_EOL;
"
```

## 🔧 OPCIONES AVANZADAS

### Restauración con Opciones Personalizadas
```php
<?php
require_once 'backup_database.php';

$backup = new DatabaseBackup();

$result = $backup->restore('2024-12-01', [
    'force' => false,              // Forzar restauración sin confirmación
    'backup_before_restore' => true,  // Crear backup automático antes
    'verify_tables' => true,          // Verificar tablas después
    'dry_run' => false,              // Simulación vs real
    'ignore_errors' => false          // Ignorar errores de SQL
]);

if ($result['success']) {
    echo "✅ Restauración exitosa\n";
    echo "Statements ejecutados: " . $result['statements_executed'] . "\n";
    echo "Tiempo: " . $result['execution_time'] . " segundos\n";
} else {
    echo "❌ Error: " . $result['message'] . "\n";
}
?>
```

### Restauración Selectiva por Tablas
```sql
-- Si solo necesitas restaurar tablas específicas
-- Edita el archivo SQL y comenta las que no necesitas

-- Ejemplo: Solo restaurar usuarios y empleados
-- DROP TABLE IF EXISTS `asistencia`;  -- Comentado
DROP TABLE IF EXISTS `usuarios`;       -- Descomentado
DROP TABLE IF EXISTS `empleados`;      -- Descomentado
```

## 🚨 SOLUCIÓN DE PROBLEMAS

### Error Común: "No active transaction"
**Causa:** Intento de rollback sin transacción activa
**Solución:** El script ya está arreglado, pero si persiste:
```php
// Asegurar que la conexión use transacciones
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```

### Error Común: "Access denied for user"
**Causa:** Permisos insuficientes
**Solución:**
```sql
-- Otorgar todos los permisos al usuario de backup
GRANT ALL PRIVILEGES ON sistema_biometrico.* TO 'tu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Error Común: "Table doesn't exist"
**Causa:** El backup no contiene todas las tablas esperadas
**Solución:**
```bash
# Verificar contenido del backup
gunzip -c backups/database/backup_completo_YYYY-MM-DD_*.sql.gz | grep "CREATE TABLE"
```

### Error Común: "Foreign key constraint fails"
**Causa:** Orden incorrecto en la restauración
**Solución:** El backup maneja esto automáticamente con:
```sql
SET FOREIGN_KEY_CHECKS = 0;
-- ... restauración ...
SET FOREIGN_KEY_CHECKS = 1;
```

## 📊 VERIFICACIÓN POST-RESTAURACIÓN

### Checklist de Verificación:
- [ ] Todas las tablas existen
- [ ] Registros críticos presentes
- [ ] Usuarios pueden iniciar sesión
- [ ] Asistencia biométrica funciona
- [ ] Reportes generan correctamente
- [ ] Logs del sistema activos

### Comandos de Verificación:
```bash
# 1. Verificar estructura completa
php -r "
require_once 'config.php'; 
require_once 'models/Database.php'; 
\$db = new Database(); 
\$expected = ['empleados','usuarios','asistencia','retardos','sanciones'];
\$actual = \$db->getConnection()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
\$missing = array_diff(\$expected, \$actual);
if (empty(\$missing)) {
    echo '✅ Todas las tablas críticas existen' . PHP_EOL;
} else {
    echo '❌ Faltan tablas: ' . implode(', ', \$missing) . PHP_EOL;
}
"

# 2. Verificar datos básicos
php -r "
require_once 'config.php'; 
require_once 'models/Database.php'; 
\$db = new Database(); 
\$checks = [
    'usuarios' => 'SELECT COUNT(*) FROM usuarios WHERE rol = \"admin\"',
    'empleados' => 'SELECT COUNT(*) FROM empleados WHERE activo = 1',
    'asistencia' => 'SELECT COUNT(*) FROM asistencia WHERE created_at >= CURDATE() - INTERVAL 7 DAY'
];
foreach (\$checks as \$table => \$sql) {
    \$count = \$db->getConnection()->query(\$sql)->fetchColumn();
    echo \"- \$table: \$count registros\" . PHP_EOL;
}
"
```

## 🔄 AUTOMATIZACIÓN DE RESTAURACIÓN

### Script de Restauración Automática:
```bash
#!/bin/bash
# auto_restore.sh

BACKUP_DATE=${1:-$(date +%Y-%m-%d)}

echo "Iniciando restauración automática: $BACKUP_DATE"

# 1. Backup de seguridad
php backup_database.php

# 2. Simulación
php restore_database.php --dry-run $BACKUP_DATE

# 3. Restauración real
php restore_database.php --restore-interactive $BACKUP_DATE

# 4. Verificación
php -r "
require_once 'config.php'; 
require_once 'models/Database.php'; 
\$db = new Database(); 
\$count = \$db->getConnection()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
echo 'Usuarios restaurados: ' . \$count . PHP_EOL;
"

echo "Restauración automática completada: $BACKUP_DATE"
```

### Tarea Programada (Cron):
```bash
# Restauración nocturna automática (ej. para ambiente de pruebas)
0 2 * * * /ruta/al/proyecto/auto_restore.sh $(date -d yesterday +%Y-%m-%d)
```

## 📞 SOPORTE Y CONTACTO

### Si la restauración falla:
1. **Verificar logs:** `tail -100 logs/backup.log`
2. **Verificar integridad:** `php restore_database.php --list`
3. **Probar simulación:** `php restore_database.php --dry-run YYYY-MM-DD`
4. **Verificar espacio en disco:** `df -h`
5. **Verificar memoria:** `free -m`

### Comandos de depuración:
```bash
# Verificar conexión a BD
php -r "
require_once 'config.php'; 
try {
    \$db = new Database(); 
    \$db->getConnection()->query('SELECT 1');
    echo '✅ Conexión a BD OK' . PHP_EOL;
} catch (Exception \$e) {
    echo '❌ Error BD: ' . \$e->getMessage() . PHP_EOL;
}
"

# Verificar archivos de backup
ls -la backups/database/*.gz
sha256sum backups/database/*.gz
```

---

## 🎯 RESUMEN DE COMANDOS RÁPIDOS

```bash
# Lista completa de comandos útiles

# 1. Listar backups
php restore_database.php --list

# 2. Simular restauración
php restore_database.php --dry-run 2024-12-01

# 3. Restaurar con seguridad
php restore_database.php --restore-interactive 2024-12-01

# 4. Restaurar directo
php restore_database.php --restore 2024-12-01

# 5. Crear backup de seguridad
php backup_database.php

# 6. Verificar integridad
php backup_database.php --cleanup

# 7. Ver logs
tail -f logs/backup.log
```

---

**Nota Importante:** Esta guía está diseñada para el Sistema Biométrico v2.0+. Asegúrese de tener la versión correcta del sistema antes de proceder con cualquier restauración.