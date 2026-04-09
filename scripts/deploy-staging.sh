#!/bin/bash

# Deploy to Staging Environment
# This script deploys the biometric system to staging

set -e  # Exit on any error

echo "🚀 Starting Staging Deployment..."
echo "Timestamp: $(date)"

# Variables de entorno
STAGING_DIR="/var/www/staging"
BACKUP_DIR="/var/backups/staging"
LOG_FILE="/var/log/staging_deploy.log"

# Crear log file
mkdir -p "$(dirname "$LOG_FILE")"
echo "=== STAGING DEPLOYMENT LOG ===" > "$LOG_FILE"
echo "Started: $(date)" >> "$LOG_FILE"

# Función para logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Validar que estamos en el servidor correcto
if [ ! -d "$STAGING_DIR" ]; then
    log "ERROR: Staging directory not found: $STAGING_DIR"
    exit 1
fi

log "📍 Iniciando despliegue a staging..."

# Realizar backup de archivos actuales
if [ -f "$STAGING_DIR/index.php" ]; then
    log "📦 Creating backup..."
    BACKUP_DIR_DATE="/var/backups/staging/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR_DATE"
    
    cp -r "$STAGING_DIR/"* "$BACKUP_DIR_DATE/"
    log "Backup created at: $BACKUP_DIR_DATE"
fi

# Pull latest changes from repository
log "📥 Pulling latest changes..."
cd "$STAGING_DIR"
git pull origin main >> "$LOG_FILE" 2>&1 || {
    log "ERROR: Failed to pull latest changes"
    exit 1
}

# Install/update dependencies
log "📦 Installing/updating dependencies..."
composer install --no-dev --optimize-autoloader >> "$LOG_FILE" 2>&1 || {
    log "ERROR: Failed to install dependencies"
    exit 1
}

# Run database migrations
log "🔄 Running database migrations..."
php migrations/run_migrations.php >> "$LOG_FILE" 2>&1 || {
    log "ERROR: Database migrations failed"
    exit 1
}

# Clear cache
log "🧹 Clearing cache..."
php cache/clear-cache.php >> "$LOG_FILE" 2>&1

# Set correct permissions
log "🔐 Setting file permissions..."
find "$STAGING_DIR" -type d -exec chmod 755 {} \; >> "$LOG_FILE" 2>&1
find "$STAGING_DIR" -type f -exec chmod 644 {} \; >> "$LOG_FILE" 2>&1

# Restart web server
log "🔄 Restarting web server..."
sudo systemctl reload nginx >> "$LOG_FILE" 2>&1 || {
    log "WARNING: Failed to restart nginx (continuing...)"
}
sudo systemctl restart php-fpm >> "$LOG_FILE" 2>&1 || {
    log "WARNING: Failed to restart php-fpm (continuing...)"
}

# Health check
log "🏥 Performing health check..."
sleep 5

# Check if the site is responding
if curl -f "http://localhost/staging/api/health" > /dev/null 2>&1; then
    log "✅ Health check passed"
    HEALTH_STATUS="healthy"
else
    log "❌ Health check failed"
    HEALTH_STATUS="unhealthy"
fi

# Check specific endpoints
log "🔍 Checking specific endpoints..."

# Check employees endpoint
if curl -f "http://localhost/staging/api/empleados" > /dev/null 2>&1; then
    log "✅ Employees API responding"
else
    log "❌ Employees API not responding"
fi

# Check authentication endpoint
if curl -f "http://localhost/staging/api/auth/login" > /dev/null 2>&1; then
    log "✅ Authentication API responding"
else
    log "❌ Authentication API not responding"
fi

# Check database connection
if php -r "
require_once 'config.php';
require_once 'models/Database.php';
\$db = new Database();
try {
    \$pdo = \$db->getConnection();
    \$pdo->query('SELECT 1');
    echo 'Database connection: OK' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . PHP_EOL;
    exit(1);
}
"  > /dev/null 2>&1; then
    log "✅ Database connection OK"
else
    log "❌ Database connection failed"
    HEALTH_STATUS="unhealthy"
fi

# Performance check
log "⚡ Performing performance check..."
START_TIME=$(date +%s)
curl -f "http://localhost/staging/api/performance" > /dev/null 2>&1
END_TIME=$(date +%s)
RESPONSE_TIME=$((END_TIME - START_TIME))

if [ $RESPONSE_TIME -lt 3000 ]; then
    log "✅ Performance OK (${RESPONSE_TIME}ms)"
else
    log "⚠️ Performance degraded (${RESPONSE_TIME}ms)"
fi

# Log deployment completion
log "🎉 Staging deployment completed!"
log "Health Status: $HEALTH_STATUS"
log "Performance: ${RESPONSE_TIME}ms"
log "Timestamp: $(date)"

# Send notification (if configured)
if [ -n "$NOTIFICATION_EMAIL" ]; then
    echo "📧 Sending deployment notification..."
    
    EMAIL_BODY="Sistema Biométrico - Staging Deployment
    
Timestamp: $(date)
Status: $HEALTH_STATUS
Performance: ${RESPONSE_TIME}ms

Changes: $(git log --oneline -1 --pretty=format:'%h - %s - %s')
Deployed by: $(git config user.name)
Repository: $(git remote get-url origin)

Web Server: nginx + PHP-FPM
Database: MySQL 8.0
PHP Version: $(php -v | head -n1 | awk '{print $2}')
    
Deployment Logs: $LOG_FILE
    "
    
    echo "$EMAIL_BODY" | mail -s "🚀 Staging Deploy Complete" \
        -a "From: deployments@staging.sistema-biométrico.com" \
        -h "MIME-Version: 1.0" \
        -H "Content-Type: text/plain; charset=UTF-8" \
        "$NOTIFICATION_EMAIL"
fi

# Success message
echo "🎉 Deploy to staging completed successfully!"
echo "✅ Health: $HEALTH_STATUS"
echo "⚡ Performance: ${RESPONSE_TIME}ms"
echo "📊 Logs: $LOG_FILE"
echo ""
echo "🌐 Staging URL: https://staging.sistema-biométrico.com"
echo "📊 Health: https://staging.sistema-biométrico.com/health"

exit 0