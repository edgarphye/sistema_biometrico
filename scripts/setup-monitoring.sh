#!/bin/bash

# Monitoring Setup Script for Sistema Biométrico
# Instala y configura Prometheus, Grafana y Exporters

set -e # Exit on any error

echo "🔧 Configurando Monitoring para Sistema Biométrico..."
echo "Timestamp: $(date)"

# Variables de configuración
PROMETHEUS_VERSION="2.45.0"
GRAFANA_VERSION="10.1.0"
NODE_EXPORTER_VERSION="1.6.1"
MYSQL_EXPORTER_VERSION="0.15.1"
NGINX_EXPORTER_VERSION="1.1.0"
REDIS_EXPORTER_VERSION="1.45.0"

PROMETHEUS_DIR="/opt/prometheus"
GRAFANA_DIR="/opt/grafana"
EXPORTERS_DIR="/opt/exporters"
CONFIG_DIR="/etc/monitoring"

# Función de logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a /var/log/monitoring-setup.log
}

# Función para verificar si es Debian/Ubuntu
is_debian() {
    [[ -f /etc/debian_version ]] || command -v apt-get &> /dev/null
}

# Función para verificar si es RHEL/CentOS
is_rhel() {
    [[ -f /etc/redhat-release ]] || command -v yum &> /dev/null
}

# Función para verificar sistema
detect_system() {
    if is_debian; then
        echo "debian"
    elif is_rhel; then
        echo "rhel"
    else
        echo "unknown"
    fi
}

# Función para instalar paquetes
install_package() {
    local package=$1
    local system=$(detect_system)
    
    log "📦 Instalando $package..."
    
    if [ "$system" = "debian" ]; then
        apt-get update && apt-get install -y "$package"
    elif [ "$system" = "rhel" ]; then
        yum install -y "$package"
    else
        log "❌ Sistema no soportado: $system"
        return 1
    fi
}

# Función para crear usuario de monitoring
create_monitoring_user() {
    log "👤 Creando usuario de monitoring..."
    
    if ! id "monitoring" &>/dev/null; then
        useradd --system --no-create-home --shell /bin/false monitoring
        log "✅ Usuario monitoring creado"
    else
        log "ℹ️ Usuario monitoring ya existe"
    fi
}

# Función para crear directorios
create_directories() {
    log "📁 Creando directorios de monitoring..."
    
    mkdir -p "$PROMETHEUS_DIR"/{bin,data,logs,config,rules}
    mkdir -p "$GRAFANA_DIR"/{bin,data,logs,etc,provisioning/{dashboards,datasources}}
    mkdir -p "$EXPORTERS_DIR"
    mkdir -p "$CONFIG_DIR"
    mkdir -p /var/lib/{prometheus,grafana}
    mkdir -p /var/log/{prometheus,grafana}
    
    log "✅ Directorios creados"
}

# Función para descargar y descomprimir
download_and_extract() {
    local url=$1
    local filename=$(basename "$url")
    local extract_to=$2
    local strip_components=${3:-1}
    
    log "📥 Descargando $filename..."
    
    cd /tmp
    wget -q "$url"
    
    log "📦 Extrayendo $filename..."
    tar -xzf "$filename" --strip-components="$strip_components" -C "$extract_to"
    
    rm -f "$filename"
}

# Función para instalar Prometheus
install_prometheus() {
    log "🚀 Instalando Prometheus $PROMETHEUS_VERSION..."
    
    # Descargar Prometheus
    download_and_extract \
        "https://github.com/prometheus/prometheus/releases/download/v$PROMETHEUS_VERSION/prometheus-$PROMETHEUS_VERSION.linux-amd64.tar.gz" \
        "$PROMETHEUS_DIR/bin"
    
    # Crear archivo de configuración
    cat > "$CONFIG_DIR/prometheus.yml" << 'EOF'
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    monitor: 'sistema-biometrico-monitor'
    environment: 'production'

rule_files:
  - "/etc/monitoring/rules/*.yml"

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - alertmanager:9093
          - localhost:9093

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']
    scrape_interval: 30s
    metrics_path: /metrics

  - job_name: 'node-exporter'
    static_configs:
      - targets: ['localhost:9100']
    scrape_interval: 30s
    metrics_path: /metrics

  - job_name: 'mysql-exporter'
    static_configs:
      - targets: ['localhost:9104']
    scrape_interval: 30s
    metrics_path: /metrics

  - job_name: 'nginx-exporter'
    static_configs:
      - targets: ['localhost:9113']
    scrape_interval: 30s
    metrics_path: /metrics

  - job_name: 'php-fpm-exporter'
    static_configs:
      - targets: ['localhost:9253']
    scrape_interval: 30s
    metrics_path: /metrics

  - job_name: 'redis-exporter'
    static_configs:
      - targets: ['localhost:9121']
    scrape_interval: 30s
    metrics_path: /metrics

  - job_name: 'sistema-biometrico'
    static_configs:
      - targets: ['localhost:8000']
    scrape_interval: 15s
    metrics_path: /metrics
    params:
      format: ['prometheus']
EOF

    # Copiar configuración de alertas
    cp "$(dirname "$0")/monitoring/prometheus/rules/sistema-biometrico.yml" "$CONFIG_DIR/rules/" 2>/dev/null || {
        log "⚠️ No se encontró archivo de reglas, usando configuración básica"
    }
    
    # Crear systemd service
    cat > /etc/systemd/system/prometheus.service << 'EOF'
[Unit]
Description=Prometheus Monitoring Server
Documentation=https://prometheus.io/docs/introduction/overview/
After=network-online.target

[Service]
User=monitoring
Group=monitoring
Type=simple
ExecStart=/opt/prometheus/bin/prometheus \
    --config.file=/etc/monitoring/prometheus.yml \
    --storage.tsdb.path=/var/lib/prometheus/data \
    --storage.tsdb.retention.time=30d \
    --storage.tsdb.retention.size=10GB \
    --web.console.templates=/opt/prometheus/consoles \
    --web.console.libraries=/opt/prometheus/console_libraries \
    --web.listen-address=0.0.0.0:9090 \
    --web.enable-admin-api \
    --web.enable-lifecycle \
    --log.level=info \
    --log.format=logfmt

Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    log "✅ Prometheus instalado"
}

# Función para instalar Grafana
install_grafana() {
    log "📊 Instalando Grafana $GRAFANA_VERSION..."
    
    # Descargar Grafana
    download_and_extract \
        "https://dl.grafana.com/oss/release/grafana-$GRAFANA_VERSION.linux-amd64.tar.gz" \
        "$GRAFANA_DIR/bin"
    
    # Crear configuración básica
    cat > "$GRAFANA_DIR/etc/grafana.ini" << 'EOF'
[server]
protocol = http
http_addr = 0.0.0.0
http_port = 3000
domain = localhost
enforce_domain = false
root_url = http://localhost:3000/

[security]
admin_user = admin
admin_password = admin1234
secret_key = SW2YcwTIb9zpOOho98M

[database]
type = sqlite3
path = /var/lib/grafana/grafana.db

[users]
allow_sign_up = false

[log]
mode = console
level = info

[auth.anonymous]
enabled = false

[auth.basic]
enabled = true

[metrics]
enabled = true

[external_image_storage]
provider = local

[paths]
data = /var/lib/grafana
logs = /var/log/grafana
plugins = /var/lib/grafana/plugins
provisioning = /opt/grafana/etc/provisioning
EOF

    # Crear datasource provisioning
    cat > "$GRAFANA_DIR/etc/provisioning/datasources/prometheus.yml" << 'EOF'
apiVersion: 1

datasources:
  - name: Prometheus
    type: prometheus
    access: proxy
    url: http://localhost:9090
    isDefault: true
    editable: true
    jsonData:
      timeInterval: "15s"
      queryTimeout: "60s"
      httpMethod: "POST"
EOF

    # Crear dashboard provisioning
    cp "$(dirname "$0")/monitoring/grafana/dashboards/sistema-biometrico-general.json" \
       "$GRAFANA_DIR/etc/provisioning/dashboards/" 2>/dev/null || {
        log "⚠️ No se encontró dashboard, se creará manualmente"
        
        # Crear dashboard básico
        cat > "$GRAFANA_DIR/etc/provisioning/dashboards/basic-dashboard.yml" << 'EOF'
apiVersion: 1

providers:
  - name: 'basic'
    orgId: 1
    folder: ''
    type: file
    disableDeletion: false
    updateIntervalSeconds: 10
    allowUiUpdates: true
    options:
      path: /opt/grafana/etc/provisioning/dashboards
EOF
    }
    
    # Crear systemd service
    cat > /etc/systemd/system/grafana.service << 'EOF'
[Unit]
Description=Grafana Analytics Platform
Documentation=https://grafana.com/docs/
After=network-online.target

[Service]
User=monitoring
Group=monitoring
Type=simple
ExecStart=/opt/grafana/bin/grafana-server \
    --config=/opt/grafana/etc/grafana.ini \
    --pidfile=/var/run/grafana/grafana.pid \
    --homepath=/opt/grafana \
    --packaging=deb

Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    log "✅ Grafana instalado"
}

# Función para instalar Node Exporter
install_node_exporter() {
    log "💻 Instalando Node Exporter $NODE_EXPORTER_VERSION..."
    
    download_and_extract \
        "https://github.com/prometheus/node_exporter/releases/download/v$NODE_EXPORTER_VERSION/node_exporter-$NODE_EXPORTER_VERSION.linux-amd64.tar.gz" \
        "$EXPORTERS_DIR/node_exporter"
    
    # Crear systemd service
    cat > /etc/systemd/system/node_exporter.service << 'EOF'
[Unit]
Description=Node Exporter
Documentation=https://github.com/prometheus/node_exporter
After=network-online.target

[Service]
User=monitoring
Type=simple
ExecStart=/opt/exporters/node_exporter/node_exporter \
    --collector.filesystem.mount-points-exclude=^/(sys|proc|dev|host|etc)($|/) \
    --collector.processes \
    --collector.systemd \
    --web.listen-address=0.0.0.0:9100

Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    log "✅ Node Exporter instalado"
}

# Función para instalar MySQL Exporter
install_mysql_exporter() {
    log "🗄️ Instalando MySQL Exporter $MYSQL_EXPORTER_VERSION..."
    
    download_and_extract \
        "https://github.com/prometheus/mysqld_exporter/releases/download/v$MYSQL_EXPORTER_VERSION/mysqld_exporter-$MYSQL_EXPORTER_VERSION.linux-amd64.tar.gz" \
        "$EXPORTERS_DIR/mysqld_exporter"
    
    # Crear archivo de configuración
    cat > "$CONFIG_DIR/.mysqld_exporter.cnf" << 'EOF'
[client]
user=exporter
password=exporter_password_123
host=localhost
port=3306
EOF
    
    # Crear systemd service
    cat > /etc/systemd/system/mysqld_exporter.service << 'EOF'
[Unit]
Description=MySQL Exporter
Documentation=https://github.com/prometheus/mysqld_exporter
After=network-online.target mysql.service

[Service]
User=monitoring
Type=simple
ExecStart=/opt/exporters/mysqld_exporter/mysqld_exporter \
    --config.my-cnf=/etc/monitoring/.mysqld_exporter.cnf \
    --web.listen-address=0.0.0.0:9104 \
    --collect.global_status \
    --collect.info_schema.innodb_metrics \
    --collect.info_schema.processlist \
    --collect.info_schema.tables \
    --collect.info_schema.tablestats \
    --collect.binlog_size \
    --collect.engine_innodb_status

Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    log "✅ MySQL Exporter instalado"
}

# Función para instalar Nginx Exporter
install_nginx_exporter() {
    log "🌐 Instalando Nginx Exporter $NGINX_EXPORTER_VERSION..."
    
    download_and_extract \
        "https://github.com/nginxinc/nginx-prometheus-exporter/releases/download/v$NGINX_EXPORTER_VERSION/nginx-prometheus-exporter-$NGINX_EXPORTER_VERSION.linux-amd64.tar.gz" \
        "$EXPORTERS_DIR/nginx_exporter"
    
    # Crear systemd service
    cat > /etc/systemd/system/nginx_exporter.service << 'EOF'
[Unit]
Description=Nginx Exporter
Documentation=https://github.com/nginxinc/nginx-prometheus-exporter
After=network-online.target nginx.service

[Service]
User=monitoring # Asegúrate de que este usuario exista o cámbialo a 'nginx' si no lo creas
Type=simple
ExecStart=/opt/exporters/nginx_exporter/nginx-prometheus-exporter \
    -nginx.scrape-uri=http://127.0.0.1:8081/nginx_status \
    -web.listen-address=0.0.0.0:9113 \
    -web.telemetry-path=/metrics

Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    log "✅ Nginx Exporter instalado"
}

# Función para instalar Redis Exporter
install_redis_exporter() {
    log "💾 Instalando Redis Exporter $REDIS_EXPORTER_VERSION..."
    
    download_and_extract \
        "https://github.com/oliver006/redis_exporter/releases/download/v$REDIS_EXPORTER_VERSION/redis_exporter-v$REDIS_EXPORTER_VERSION.linux-amd64.tar.gz" \
        "$EXPORTERS_DIR/redis_exporter" \
        0
    
    # Crear systemd service
    cat > /etc/systemd/system/redis_exporter.service << 'EOF'
[Unit]
Description=Redis Exporter
Documentation=https://github.com/oliver006/redis_exporter
After=network-online.target redis.service

[Service]
User=monitoring
Type=simple
ExecStart=/opt/exporters/redis_exporter/redis_exporter \
    -redis.addr=redis://localhost:6379 \
    -web.listen-address=0.0.0.0:9121 \
    -web.telemetry-path=/metrics

Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

    log "✅ Redis Exporter instalado"
}

# Función para configurar permisos
setup_permissions() {
    log "🔐 Configurando permisos..."
    
    chown -R monitoring:monitoring "$PROMETHEUS_DIR"
    chown -R monitoring:monitoring "$GRAFANA_DIR"
    chown -R monitoring:monitoring "$EXPORTERS_DIR"
    chown -R monitoring:monitoring "$CONFIG_DIR"
    chown -R monitoring:monitoring /var/lib/prometheus
    chown -R monitoring:monitoring /var/lib/grafana
    chown -R monitoring:monitoring /var/log/prometheus
    chown -R monitoring:monitoring /var/log/grafana
    
    chmod +x "$PROMETHEUS_DIR/bin/prometheus"
    chmod +x "$GRAFANA_DIR/bin/grafana-server"
    chmod +x "$EXPORTERS_DIR/node_exporter/node_exporter"
    chmod +x "$EXPORTERS_DIR/mysqld_exporter/mysqld_exporter"
    chmod +x "$EXPORTERS_DIR/nginx_exporter/nginx-prometheus-exporter"
    chmod +x "$EXPORTERS_DIR/redis_exporter/redis_exporter"
    
    log "✅ Permisos configurados"
}

# Función para configurar Nginx para status
configure_nginx_status() {
    log "🌐 Configurando Nginx status..."
    
    # Asegurar que Nginx esté instalado
    install_package "nginx"

    local nginx_conf_dir="/etc/nginx/sites-available"
    local nginx_enabled_dir="/etc/nginx/sites-enabled"
    
    if is_rhel; then # Fedora se considera RHEL para estas rutas
        nginx_conf_dir="/etc/nginx/conf.d"
        nginx_enabled_dir="/etc/nginx/conf.d" # En Fedora, los archivos .conf van directamente aquí
    fi

    mkdir -p "$nginx_conf_dir"

    # Detectar dinámicamente el socket de PHP-FPM para el status
    # Fedora suele usar /run/php-fpm/www.sock o similar
    local php_socket=$(find /var/run /run -name "php*fpm.sock" | head -n 1 || echo "/var/run/php-fpm/www.sock")

    # Agregar configuración de status a Nginx
    cat > "$nginx_conf_dir/monitoring_status.conf" << EOF
server {
    listen 127.0.0.1:8081;
    server_name localhost_monitor;
    
    location /nginx_status {
        stub_status on;
        access_log off;
        allow 127.0.0.1;
        deny all;
    }
    
    location /php-fpm_status {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $fastcgi_script_name;
        access_log off;
        allow 127.0.0.1;
        deny all;
    }
}
EOF
    
    # Activar el site
    ln -sf /etc/nginx/sites-available/monitoring_status /etc/nginx/sites-enabled/ 2>/dev/null || true
    
    # Recargar Nginx
    systemctl reload nginx 2>/dev/null || {
        log "⚠️ Error recargando Nginx, continuar..."
    }
    
    log "✅ Nginx status configurado"
}

# Función para configurar MySQL user para exporter
configure_mysql_user() {
    log "🗄️ Configurando usuario MySQL para exporter..."
    
    # Crear usuario para exporter
    mysql -u root -e "
        CREATE USER IF NOT EXISTS 'exporter'@'localhost' IDENTIFIED BY 'exporter_password_123';
        GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'localhost';
        FLUSH PRIVILEGES;
    " 2>/dev/null || {
        log "⚠️ Error configurando usuario MySQL, configurar manualmente"
    }
    
    log "✅ Usuario MySQL configurado"
}

# Función para iniciar servicios
start_services() {
    log "🚀 Iniciando servicios de monitoring..."
    
    # Recargar systemd
    systemctl daemon-reload
    
    # Iniciar y habilitar servicios
    services=(
        "prometheus"
        "grafana-server"
        "node_exporter"
        "mysqld_exporter"
        "nginx_exporter"
        "redis_exporter"
    )
    
    for service in "${services[@]}"; do
        log "🔄 Iniciando $service..."
        
        systemctl enable "$service"
        systemctl restart "$service"
        
        # Esperar a que el servicio inicie
        sleep 2
        
        if systemctl is-active --quiet "$service"; then
            log "✅ $service iniciado correctamente"
        else
            log "❌ Error iniciando $service"
        fi
    done
}

# Función para verificar instalación
verify_installation() {
    log "🔍 Verificando instalación..."
    
    local services_ok=true
    
    # Verificar servicios
    services=(
        "prometheus:9090"
        "grafana:3000"
        "node_exporter:9100"
        "mysqld_exporter:9104"
        "nginx_exporter:9113"
        "redis_exporter:9121"
    )
    
    for service_port in "${services[@]}"; do
        IFS=':' read -r service port <<< "$service_port"
        
        if curl -f "http://localhost:$port" > /dev/null 2>&1; then
            log "✅ $service está funcionando en puerto $port"
        else
            log "❌ $service no está respondiendo en puerto $port"
            services_ok=false
        fi
    done
    
    if [ "$services_ok" = true ]; then
        log "🎉 Todos los servicios de monitoring están funcionando correctamente!"
        
        log ""
        log "📊 URLs de Acceso:"
        log "   Prometheus: http://localhost:9090"
        log "   Grafana: http://localhost:3000 (admin/admin1234)"
        log "   Node Exporter: http://localhost:9100/metrics"
        log "   MySQL Exporter: http://localhost:9104/metrics"
        log "   Nginx Exporter: http://localhost:9113/metrics"
        log "   Redis Exporter: http://localhost:9121/metrics"
        
        log ""
        log "📋 Próximos Pasos:"
        log "1. Ingresar a Grafana: http://localhost:3000"
        log "2. Cambiar contraseña de admin"
        log "3. Importar dashboards desde: /opt/grafana/etc/provisioning/dashboards/"
        log "4. Configurar alertas en Grafana"
        log "5. Revisar alertas en Prometheus: http://localhost:9090/alerts"
        
        return 0
    else
        log "❌ Algunos servicios no están funcionando correctamente"
        log "📋 Verificar logs en: /var/log/prometheus/ y /var/log/grafana/"
        return 1
    fi
}

# Función principal
main() {
    log "🎯 Iniciando configuración de Monitoring completo"
    log "========================================================="
    
    # Verificar que se ejecute como root
    if [ "$EUID" -ne 0 ]; then
        log "❌ Este script debe ejecutarse como root"
        exit 1
    fi
    
    # Instalar dependencias básicas
    log "📦 Instalando dependencias básicas..."
    
    if is_debian; then
        apt-get update
        apt-get install -y nginx wget tar curl systemctl
    elif is_rhel; then
        yum update -y
        yum install -y nginx wget tar curl systemctl
    else
        log "❌ Sistema no soportado"
        exit 1
    fi
    
    # Crear estructura
    create_monitoring_user
    create_directories
    
    # Instalar componentes
    install_prometheus
    install_grafana
    install_node_exporter
    install_mysql_exporter
    install_nginx_exporter
    install_redis_exporter
    
    # Configuraciones adicionales
    setup_permissions
    configure_nginx_status
    configure_mysql_user
    
    # Iniciar servicios
    start_services
    
    # Verificar instalación
    if verify_installation; then
        log "🎉 Configuración de monitoring completada exitosamente!"
        exit 0
    else
        log "❌ Error en la configuración de monitoring"
        exit 1
    fi
}

# Capturar señales
trap 'log "🛑 Configuración interrumpida"; exit 130' INT TERM

# Ejecutar función principal
main "$@"