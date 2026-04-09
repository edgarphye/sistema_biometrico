#!/bin/bash

# Deploy to Production Environment
# Sistema Biométrico - Production Deployment Script

set -e  # Exit on any error

echo "🚀 Starting Production Deployment..."
echo "Timestamp: $(date)"
echo "Environment: PRODUCTION"

# Variables de entorno
PROD_DIR="/var/www/production"
BACKUP_DIR="/var/backups/production"
LOG_FILE="/var/log/production_deploy.log"
HEALTH_CHECK_URL="https://sistema-biometrico.com/health"
ROLLBACK_HEALTH_URL="http://localhost:8000/health"

# Crear directorios necesarios
mkdir -p "$(dirname "$LOG_FILE")"
mkdir -p "$BACKUP_DIR/$(date +%Y%m%d)"

# Función de logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Función de verificación de prerequisitos
verify_prerequisites() {
    log "🔍 Verificando prerequisitos..."
    
    # Verificar que estamos en el servidor correcto
    if [ ! -d "$PROD_DIR" ]; then
        log "❌ ERROR: Directorio de producción no encontrado: $PROD_DIR"
        exit 1
    fi
    
    # Verificar permisos
    if [ ! -w "$PROD_DIR" ]; then
        log "❌ ERROR: No hay permisos de escritura en $PROD_DIR"
        exit 1
    fi
    
    # Verificar espacio en disco
    local available_space=$(df "$PROD_DIR" | awk 'NR==2 {print $4}')
    if [ "$available_space" -lt 1048576 ]; then  # 1GB en KB
        log "⚠️ ADVERTENCIA: Espacio en disco bajo (< 1GB)"
    fi
    
    # Verificar servicios críticos
    if ! systemctl is-active --quiet nginx; then
        log "⚠️ ADVERTENCIA: Nginx no está corriendo"
    fi
    
    if ! systemctl is-active --quiet php-fpm; then
        log "⚠️ ADVERTENCIA: PHP-FPM no está corriendo"
    fi
    
    if ! systemctl is-active --quiet mysql; then
        log "⚠️ ADVERTENCIA: MySQL no está corriendo"
    fi
    
    log "✅ Prerequisitos verificados"
}

# Función de backup completo
create_full_backup() {
    log "📦 Creando backup completo del sistema..."
    
    local backup_date=$(date +%Y%m%d_%H%M%S)
    local backup_path="$BACKUP_DIR/$(date +%Y%m%d)/production_backup_${backup_date}"
    
    mkdir -p "$backup_path"
    
    # Backup de archivos
    log "📁 Respaldando archivos de la aplicación..."
    tar -czf "$backup_path/application_files.tar.gz" -C "$PROD_DIR" . 2>&1 || {
        log "❌ Error creando backup de archivos"
        return 1
    }
    
    # Backup de base de datos
    log "🗄️ Respaldando base de datos..."
    local db_backup_file="$backup_path/database_backup_${backup_date}.sql"
    
    mysqldump --single-transaction \
              --routines \
              --triggers \
              --quick \
              --hex-blob \
              --lock-tables=false \
              --default-character-set=utf8mb4 \
              sistema_biometrico > "$db_backup_file" 2>&1 || {
        log "❌ Error creando backup de base de datos"
        return 1
    }
    
    # Backup de configuración
    log "⚙️ Respaldando configuración..."
    if [ -f "$PROD_DIR/.env" ]; then
        cp "$PROD_DIR/.env" "$backup_path/.env.backup"
    fi
    
    # Backup de logs recientes
    if [ -d "$PROD_DIR/logs" ]; then
        tar -czf "$backup_path/recent_logs.tar.gz" -C "$PROD_DIR/logs" . 2>/dev/null || true
    fi
    
    # Crear archivo de metadata
    cat > "$backup_path/backup_info.txt" << EOF
Production Backup Information
==========================
Backup Date: $(date)
Backup Type: Full Deployment Backup
Server: $(hostname)
User: $(whoami)
Application Directory: $PROD_DIR
Database Dump: $db_backup_file
File Count: $(find "$PROD_DIR" -type f | wc -l)
Disk Usage: $(du -sh "$PROD_DIR" | cut -f1)
Uptime: $(uptime -p)

Git Information:
- Branch: $(cd "$PROD_DIR" && git branch --show-current 2>/dev/null || echo "N/A")
- Commit: $(cd "$PROD_DIR" && git rev-parse HEAD 2>/dev/null || echo "N/A")
- Remote: $(cd "$PROD_DIR" && git remote get-url origin 2>/dev/null || echo "N/A")

System Information:
- OS: $(uname -a)
- Memory: $(free -h | grep Mem)
- Disk: $(df -h "$PROD_DIR" | tail -1)
EOF

    log "✅ Backup completo creado en: $backup_path"
    echo "$backup_path" > /tmp/last_backup_path
}

# Función de health check del sistema actual
health_check_current() {
    log "🏥 Realizando health check del sistema actual..."
    
    if curl -f "$HEALTH_CHECK_URL" > /dev/null 2>&1; then
        log "✅ Sistema actual funcionando correctamente"
        return 0
    else
        log "⚠️ Sistema actual no responde - procediendo con precaución"
        return 1
    fi
}

# Función para poner sitio en modo mantenimiento
enable_maintenance_mode() {
    log "🔧 Activando modo mantenimiento..."
    
    # Crear página de mantenimiento
    cat > "$PROD_DIR/maintenance.html" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Mantenimiento - Sistema Biométrico</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 100px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #9F2241; margin-bottom: 20px; }
        .timer { font-size: 24px; color: #235B4E; margin: 20px 0; }
        .progress { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-bar { height: 100%; background: linear-gradient(90deg, #9F2241, #235B4E); width: 0%; transition: width 0.3s ease; }
        .info { margin: 20px 0; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Mantenimiento Programado</h1>
        <p>Estamos actualizando el sistema para mejorar tu experiencia.</p>
        <p class="info">Tiempo estimado: <strong>10-15 minutos</strong></p>
        <div class="progress">
            <div class="progress-bar" id="progress"></div>
        </div>
        <p class="timer">Tiempo restante: <span id="timer">15:00</span></p>
        <div class="info">
            <p><strong>Fecha:</strong> $(date)</p>
            <p><strong>Motivo:</strong> Actualización del Sistema Biométrico</p>
            <p><strong>Contacto:</strong> soporte@sistema-biometrico.com</p>
        </div>
    </div>
    <script>
        let time = 15 * 60; // 15 minutos en segundos
        const progress = document.getElementById('progress');
        const timer = document.getElementById('timer');
        const totalTime = time;
        
        const updateTimer = () => {
            const minutes = Math.floor(time / 60);
            const seconds = time % 60;
            timer.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            progress.style.width = ((totalTime - time) / totalTime * 100) + '%';
            
            if (time > 0) {
                time--;
                setTimeout(updateTimer, 1000);
            } else {
                location.reload();
            }
        };
        
        updateTimer();
    </script>
</body>
</html>
EOF

    # Configurar Nginx para mostrar página de mantenimiento
    cat > /etc/nginx/sites-available/maintenance.conf << 'EOF'
server {
    listen 80;
    listen [::]:80;
    listen 443 ssl;
    listen [::]:443 ssl;
    
    server_name sistema-biometrico.com www.sistema-biometrico.com;
    
    ssl_certificate /etc/ssl/certs/sistema-biometrico.com.crt;
    ssl_certificate_key /etc/ssl/private/sistema-biometrico.com.key;
    
    root /var/www/production;
    index maintenance.html;
    
    location = / {
        try_files /maintenance.html =503;
    }
    
    location = /health {
        access_log off;
        return 200 "OK";
        add_header Content-Type text/plain;
    }
    
    error_page 503 @maintenance;
    
    location @maintenance {
        rewrite ^(.*)$ /maintenance.html break;
    }
}
EOF

    # Activar configuración de mantenimiento
    ln -sf /etc/nginx/sites-available/maintenance.conf /etc/nginx/sites-enabled/ 2>/dev/null || true
    rm -f /etc/nginx/sites-enabled/sistema-biometrico 2>/dev/null || true
    
    # Recargar Nginx
    systemctl reload nginx
    
    # Esperar a que se aplique
    sleep 3
    
    log "✅ Modo mantenimiento activado"
}

# Función para desactivar modo mantenimiento
disable_maintenance_mode() {
    log "🔄 Desactivando modo mantenimiento..."
    
    # Eliminar configuración de mantenimiento
    rm -f /etc/nginx/sites-enabled/maintenance.conf
    
    # Restaurar configuración normal
    ln -sf /etc/nginx/sites-available/sistema-biometrico /etc/nginx/sites-enabled/ 2>/dev/null || true
    
    # Recargar Nginx
    systemctl reload nginx
    
    # Eliminar página de mantenimiento
    rm -f "$PROD_DIR/maintenance.html"
    
    # Esperar a que se aplique
    sleep 3
    
    log "✅ Modo mantenimiento desactivado"
}

# Función para actualizar archivos
update_application_files() {
    log "📦 Actualizando archivos de la aplicación..."
    
    cd "$PROD_DIR"
    
    # Pull latest changes
    log "📥 Obteniendo últimos cambios del repositorio..."
    git fetch origin 2>&1 || {
        log "❌ Error haciendo fetch del repositorio"
        return 1
    }
    
    git pull origin main 2>&1 || {
        log "❌ Error haciendo pull del repositorio"
        return 1
    }
    
    # Verificar que se hayan actualizado los archivos
    local new_changes=$(git log --oneline origin/main..HEAD 2>/dev/null | wc -l)
    log "📝 Cambios nuevos: $new_changes commits"
    
    # Instalar/actualizar dependencias de PHP
    log "📦 Actualizando dependencias de PHP..."
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 || {
        log "❌ Error actualizando dependencias de PHP"
        return 1
    }
    
    # Instalar/actualizar dependencias de Node.js si existen
    if [ -f "package.json" ]; then
        log "📦 Actualizando dependencias de Node.js..."
        npm ci --production 2>&1 || {
            log "❌ Error actualizando dependencias de Node.js"
            return 1
        }
        
        # Build assets si es necesario
        if npm run | grep -q "build"; then
            log "🔨 Construyendo assets..."
            npm run build 2>&1 || {
                log "⚠️ Advertencia: Error construyendo assets (continuando)"
            }
        fi
    fi
    
    # Ejecutar migraciones de base de datos
    if [ -f "migrations/run_migrations.php" ]; then
        log "🔄 Ejecutando migraciones de base de datos..."
        php migrations/run_migrations.php 2>&1 || {
            log "⚠️ Advertencia: Error en migraciones (continuando)"
        }
    fi
    
    # Limpiar cache
    if [ -f "cache/clear-cache.php" ]; then
        log "🧹 Limpiando cache..."
        php cache/clear-cache.php 2>&1 || {
            log "⚠️ Advertencia: Error limpiando cache (continuando)"
        }
    fi
    
    # Setear permisos correctos
    log "🔐 Estableciendo permisos..."
    find "$PROD_DIR" -type d -exec chmod 755 {} \; 2>/dev/null || true
    find "$PROD_DIR" -type f -exec chmod 644 {} \; 2>/dev/null || true
    chmod 600 "$PROD_DIR/.env" 2>/dev/null || true
    chmod -R 775 "$PROD_DIR/storage" 2>/dev/null || true
    chmod -R 775 "$PROD_DIR/logs" 2>/dev/null || true
    chmod -R 775 "$PROD_DIR/cache" 2>/dev/null || true
    chown -R www-data:www-data "$PROD_DIR/storage" 2>/dev/null || true
    chown -R www-data:www-data "$PROD_DIR/logs" 2>/dev/null || true
    chown -R www-data:www-data "$PROD_DIR/cache" 2>/dev/null || true
    
    log "✅ Archivos de la aplicación actualizados"
}

# Función para restart de servicios
restart_services() {
    log "🔄 Reiniciando servicios..."
    
    # Restart PHP-FPM
    log "🔄 Reiniciando PHP-FPM..."
    systemctl restart php-fpm || {
        log "⚠️ Error reiniciando PHP-FPM (intentando recargar)"
        systemctl reload php-fpm
    }
    
    # Restart Nginx
    log "🔄 Reiniciando Nginx..."
    systemctl restart nginx || {
        log "⚠️ Error reiniciando Nginx (intentando recargar)"
        systemctl reload nginx
    }
    
    # Warm up Redis si está disponible
    if systemctl is-active --quiet redis-server; then
        log "🔥 Warm-up de Redis..."
        redis-cli FLUSHALL 2>/dev/null || true
    fi
    
    # Esperar a que servicios estabilicen
    sleep 10
    
    log "✅ Servicios reiniciados"
}

# Función para health check post-deployment
health_check_post_deployment() {
    log "🏥 Realizando health check post-deployment..."
    
    local max_attempts=30
    local attempt=1
    local wait_time=10
    
    while [ $attempt -le $max_attempts ]; do
        log "🔍 Intento $attempt/$max_attempts..."
        
        if curl -f "$HEALTH_CHECK_URL" > /dev/null 2>&1; then
            log "✅ Health check exitoso!"
            
            # Verificación adicional de APIs críticas
            log "🔍 Verificando APIs críticas..."
            
            if curl -f "$HEALTH_CHECK_URL/api/auth/health" > /dev/null 2>&1; then
                log "✅ API de autenticación funcionando"
            else
                log "⚠️ API de autenticación no responde"
            fi
            
            if curl -f "$HEALTH_CHECK_URL/api/empleados/health" > /dev/null 2>&1; then
                log "✅ API de empleados funcionando"
            else
                log "⚠️ API de empleados no responde"
            fi
            
            return 0
        fi
        
        log "⏳ Esperando $wait_time segundos antes del próximo intento..."
        sleep $wait_time
        attempt=$((attempt + 1))
        
        # Incrementar tiempo de espera exponencialmente
        wait_time=$((wait_time * 2))
        if [ $wait_time -gt 60 ]; then
            wait_time=60
        fi
    done
    
    log "❌ ERROR: Health check falló después de $max_attempts intentos"
    return 1
}

# Función para rollback automático
rollback_deployment() {
    log "🔙 Iniciando rollback automático..."
    
    local last_backup_path=$(cat /tmp/last_backup_path 2>/dev/null)
    
    if [ -z "$last_backup_path" ] || [ ! -d "$last_backup_path" ]; then
        log "❌ ERROR: No hay backup disponible para rollback"
        return 1
    fi
    
    log "📦 Restaurando desde backup: $last_backup_path"
    
    # Restaurar archivos
    if [ -f "$last_backup_path/application_files.tar.gz" ]; then
        log "📁 Restaurando archivos de la aplicación..."
        tar -xzf "$last_backup_path/application_files.tar.gz" -C "$PROD_DIR" || {
            log "❌ Error restaurando archivos"
            return 1
        }
    fi
    
    # Restaurar base de datos
    if [ -f "$last_backup_path/database_backup_"*".sql" ]; then
        log "🗄️ Restaurando base de datos..."
        local db_backup_file=$(ls "$last_backup_path"/database_backup_*.sql | head -1)
        
        mysql sistema_biometrico < "$db_backup_file" || {
            log "❌ Error restaurando base de datos"
            return 1
        }
    fi
    
    # Restaurar configuración
    if [ -f "$last_backup_path/.env.backup" ]; then
        log "⚙️ Restaurando configuración..."
        cp "$last_backup_path/.env.backup" "$PROD_DIR/.env"
    fi
    
    # Restart servicios
    restart_services
    
    # Health check del rollback
    if health_check_post_deployment; then
        log "✅ Rollback completado exitosamente"
        return 0
    else
        log "❌ ERROR: Rollback falló - intervención manual requerida"
        return 1
    fi
}

# Función para enviar notificaciones
send_notification() {
    local status=$1
    local message=$2
    
    log "📧 Enviando notificación de deployment..."
    
    # Preparar mensaje
    local email_body="Sistema Biométrico - Deployment $status
    
Fecha: $(date)
Servidor: $(hostname)
Usuario: $(whoami)

$message

Información del Sistema:
- Uptime: $(uptime -p)
- Memoria: $(free -h | grep Mem)
- Disco: $(df -h "$PROD_DIR" | tail -1)

Backup: $(cat /tmp/last_backup_path 2>/dev/null || echo 'N/A')
Logs: $LOG_FILE

---
Este es un mensaje automático del sistema de deployment.
"
    
    # Enviar email (configurar según tu servidor de email)
    echo "$email_body" | mail -s "🚀 Deployment $status - Sistema Biométrico" \
        -a "From: deployments@sistema-biometrico.com" \
        -a "Content-Type: text/plain; charset=UTF-8" \
        "${NOTIFICATION_EMAIL:-admin@sistema-biometrico.com}" 2>/dev/null || {
        log "⚠️ No se pudo enviar notificación por email"
    }
    
    # Enviar notificación a Slack si webhook está configurado
    if [ -n "$SLACK_WEBHOOK_URL" ]; then
        local color="good"
        [ "$status" = "FAILED" ] && color="danger"
        [ "$status" = "WARNING" ] && color="warning"
        
        curl -X POST "$SLACK_WEBHOOK_URL" \
             -H 'Content-type: application/json' \
             --data "{
                 \"text\": \"Sistema Biométrico - Deployment $status\",
                 \"attachments\": [{
                     \"color\": \"$color\",
                     \"fields\": [{
                         \"title\": \"Status\",
                         \"value\": \"$status\",
                         \"short\": true
                     }, {
                         \"title\": \"Message\",
                         \"value\": \"$message\",
                         \"short\": false
                     }, {
                         \"title\": \"Server\",
                         \"value\": \"$(hostname)\",
                         \"short\": true
                     }, {
                         \"title\": \"Time\",
                         \"value\": \"$(date)\",
                         \"short\": true
                     }]
                 }]
             }" 2>/dev/null || {
            log "⚠️ No se pudo enviar notificación a Slack"
        }
    fi
}

# Función principal
main() {
    log "🎯 Iniciando Production Deployment"
    log "======================================"
    
    # Verificar prerequisitos
    verify_prerequisites
    
    # Health check del sistema actual
    local current_health_ok=true
    health_check_current || current_health_ok=false
    
    # Crear backup completo
    if ! create_full_backup; then
        log "❌ ERROR CRÍTICO: No se pudo crear backup - abortando deployment"
        send_notification "FAILED" "No se pudo crear backup del sistema. Deployment abortado."
        exit 1
    fi
    
    # Activar modo mantenimiento
    enable_maintenance_mode
    
    # Actualizar archivos de la aplicación
    if ! update_application_files; then
        log "❌ ERROR CRÍTICO: Falló actualización de archivos - iniciando rollback"
        disable_maintenance_mode
        rollback_deployment
        send_notification "FAILED" "Falló actualización de archivos. Rollback ejecutado."
        exit 1
    fi
    
    # Restart servicios
    restart_services
    
    # Desactivar modo mantenimiento
    disable_maintenance_mode
    
    # Health check post-deployment
    if health_check_post_deployment; then
        log "🎉 DEPLOYMENT EXITOSO!"
        
        # Limpiar backups antiguos (mantener últimos 7 días)
        log "🧹 Limpiando backups antiguos..."
        find "$BACKUP_DIR" -type d -mtime +7 -exec rm -rf {} \; 2>/dev/null || true
        
        # Enviar notificación de éxito
        send_notification "SUCCESS" "Deployment completado exitosamente. Sistema funcionando correctamente."
        
        exit 0
    else
        log "❌ ERROR CRÍTICO: Falló health check post-deployment - iniciando rollback"
        
        # Rollback automático
        if rollback_deployment; then
            send_notification "ROLLBACK" "Deployment falló pero rollback fue exitoso. Sistema restaurado."
            exit 2
        else
            send_notification "FAILED" "Deployment falló y rollback también falló. Intervención manual URGENTE requerida."
            exit 3
        fi
    fi
}

# Capturar señales para cleanup
trap 'log "🛑 Deployment interrumpido"; exit 130' INT TERM

# Ejecutar función principal
main "$@"