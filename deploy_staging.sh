#!/bin/bash

# Deployment Script for Sistema Biométrico
# Staging Environment Deployment

set -e  # Exit on any error

# Configuration
STAGING_SERVER="staging.sistema-biometrico.com"
STAGING_USER="deploy"
STAGING_PATH="/var/www/staging.sistema-biometrico.com"
BACKUP_DIR="./backups/deploy_$(date +%Y%m%d_%H%M%S)"
LOCAL_PATH="$(pwd)"

echo "🚀 Iniciando Deployment a Staging"
echo "=================================="

# 1. Create backup directory
echo "📦 Creando directorio de backup..."
mkdir -p "$BACKUP_DIR"

# 2. Run database backup
echo "💾 Creando backup de base de datos..."
mysqldump --single-transaction --routines --triggers -u root -p sistema_biometrico > "$BACKUP_DIR/database.sql"

# 3. Code backup
echo "📂 Creando backup de código..."
tar -czf "$BACKUP_DIR/code.tar.gz" --exclude='node_modules' --exclude='.git' --exclude='backups' .

# 4. Update version info
echo "📝 Actualizando versión..."
echo "1.2.0-staging" > VERSION.txt
echo "Deploy: $(date)" >> deploy.log

# 5. Build assets (if applicable)
echo "🔨 Construyendo assets..."
if [ -f "package.json" ]; then
    npm run build || echo "⚠️  Warning: npm build failed"
fi

# 6. Upload files to staging
echo "📤 Subiendo archivos a staging..."
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='backups' \
    -e "ssh -i ~/.ssh/staging_key" \
    "$LOCAL_PATH/" "$STAGING_USER@$STAGING_SERVER:$STAGING_PATH"

# 7. Run database migrations on staging
echo "🗄️  Ejecutando migraciones en staging..."
ssh -i ~/.ssh/staging_key "$STAGING_USER@$STAGING_SERVER" "
    cd $STAGING_PATH
    php setup.php
    php vendor/bin/phinx migrate
"

# 8. Set correct permissions
echo "🔐 Configurando permisos..."
ssh -i ~/.ssh/staging_key "$STAGING_USER@$STAGING_SERVER" "
    cd $STAGING_PATH
    chown -R www-data:www-data .
    chmod -R 755 .
    chmod -R 777 cache logs uploads
"

# 9. Clear caches
echo "🧹 Limpiando caches..."
ssh -i ~/.ssh/staging_key "$STAGING_USER@$STAGING_SERVER" "
    cd $STAGING_PATH
    rm -rf cache/*
    php -r 'if (function_exists(\"opcache_reset\")) opcache_reset();'
"

# 10. Restart services
echo "🔄 Reiniciando servicios..."
ssh -i ~/.ssh/staging_key "$STAGING_USER@$STAGING_SERVER" "
    sudo systemctl reload nginx
    sudo systemctl reload php8.4-fpm
"

# 11. Health check
echo "🏥 Verificando salud del sistema..."
sleep 10
HEALTH_CHECK=$(curl -s -o /dev/null -w "%{http_code}" "https://$STAGING_SERVER/health")
if [ "$HEALTH_CHECK" = "200" ]; then
    echo "✅ Sistema operativo en staging"
else
    echo "❌ Error: Sistema no responde (HTTP $HEALTH_CHECK)"
    exit 1
fi

# 12. Run automated tests on staging
echo "🧪 Ejecutando tests automáticos en staging..."
ssh -i ~/.ssh/staging_key "$STAGING_USER@$STAGING_SERVER" "
    cd $STAGING_PATH
    vendor/bin/phpunit --testsuite Unit --log-junit tests-results.xml
"

echo ""
echo "✅ Deployment completado exitosamente"
echo "🌐 Staging URL: https://$STAGING_SERVER"
echo "📊 Dashboard: https://$STAGING_SERVER/dashboard"
echo "📚 API Docs: https://$STAGING_SERVER/docs/api"
echo ""
echo "📋 Próximos pasos:"
echo "1. Verificar funcionalidad manual en: https://$STAGING_SERVER"
echo "2. Ejecutar pruebas de UAT con usuarios reales"
echo "3. Monitorear logs y performance"
echo "4. Aprobar deployment para producción"
echo ""
echo "📦 Backup guardado en: $BACKUP_DIR"