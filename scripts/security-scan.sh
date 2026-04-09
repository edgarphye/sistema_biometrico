#!/bin/bash

# Security Scanning Script con OWASP ZAP
# Sistema Biométrico - Pentesting Automatizado

set -e  # Exit on any error

echo "🔒 Iniciando Security Scanning con OWASP ZAP..."
echo "Timestamp: $(date)"

# Configuración
TARGET_URL="http://localhost:8000"
API_KEY="changeme123"  # Default ZAP API key
ZAP_PORT="8090"
ZAP_HOST="127.0.0.1"
OUTPUT_DIR="tests/security/results"
REPORT_DIR="tests/security/reports"
LOG_FILE="tests/security/security_scan.log"

# Crear directorios necesarios
mkdir -p "$OUTPUT_DIR" "$REPORT_DIR"
mkdir -p "$(dirname "$LOG_FILE")"

# Función de logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Función para verificar que ZAP esté instalado
check_zap() {
    log "🔍 Verificando instalación de OWASP ZAP..."
    
    if command -v zap.sh &> /dev/null; then
        log "✅ ZAP encontrado en: $(which zap.sh)"
        return 0
    elif command -v zaproxy &> /dev/null; then
        log "✅ ZAP encontrado en: $(which zaproxy)"
        return 0
    else
        log "❌ ZAP no encontrado. Instalación recomendada:"
        log "💡 Ubuntu/Debian: sudo apt-get install zaproxy"
        log "💡 macOS: brew install zap"
        log "💡 Windows: Download desde https://www.zaproxy.org/"
        return 1
    fi
}

# Función para iniciar ZAP
start_zap() {
    log "🚀 Iniciando OWASP ZAP..."
    
    # Detener instancias existentes
    pkill -f zaproxy 2>/dev/null || true
    pkill -f zap.sh 2>/dev/null || true
    
    sleep 2
    
    # Iniciar ZAP en modo headless
    if command -v zap.sh &> /dev/null; then
        nohup zap.sh -daemon -port "$ZAP_PORT" -host "$ZAP_HOST" -config api.addrs.addr.name=.* -config api.key="$API_KEY" > /dev/null 2>&1 &
    else
        nohup zaproxy -daemon -port "$ZAP_PORT" -host "$ZAP_HOST" -config api.addrs.addr.name=.* -config api.key="$API_KEY" > /dev/null 2>&1 &
    fi
    
    ZAP_PID=$!
    
    # Esperar a que ZAP inicie
    log "⏳ Esperando a que ZAP esté listo..."
    for i in {1..30}; do
        if curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/core/view/zapVersion/?apikey=$API_KEY" > /dev/null 2>&1; then
            log "✅ ZAP iniciado correctamente (PID: $ZAP_PID)"
            return 0
        fi
        sleep 1
    done
    
    log "❌ Error: ZAP no inició después de 30 segundos"
    return 1
}

# Función para detener ZAP
stop_zap() {
    log "🛑 Deteniendo OWASP ZAP..."
    
    if [ ! -z "$ZAP_PID" ]; then
        kill "$ZAP_PID" 2>/dev/null || true
        wait "$ZAP_PID" 2>/dev/null || true
    fi
    
    # Forzar detención
    pkill -f zaproxy 2>/dev/null || true
    pkill -f zap.sh 2>/dev/null || true
    
    log "✅ ZAP detenido"
}

# Función para escanear con Spider
spider_scan() {
    log "🕷️ Iniciando Spider Scan..."
    
    local target=$1
    local scan_id=$(curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/spider/action/scan/?apikey=$API_KEY&url=$target" | jq -r '.scan')
    
    log "📝 Spider Scan ID: $scan_id"
    
    # Esperar a que complete
    while true; do
        local status=$(curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/spider/view/status/?apikey=$API_KEY&scanId=$scan_id" | jq -r '.status')
        
        if [ "$status" = "100" ]; then
            log "✅ Spider Scan completado"
            break
        fi
        
        log "🕷️ Spider Scan progreso: $status%"
        sleep 5
    done
    
    # Guardar resultados del spider
    curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/spider/view/results/?apikey=$API_KEY&start=0&count=1000" > "$OUTPUT_DIR/spider_results.json"
    log "📁 Resultados del spider guardados en: $OUTPUT_DIR/spider_results.json"
}

# Función para escaneo activo
active_scan() {
    log "🔥 Iniciando Active Scan..."
    
    local target=$1
    local scan_id=$(curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/ascan/action/scan/?apikey=$API_KEY&url=$target" | jq -r '.scan')
    
    log "📝 Active Scan ID: $scan_id"
    
    # Esperar a que complete
    while true; do
        local status=$(curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/ascan/view/status/?apikey=$API_KEY&scanId=$scan_id" | jq -r '.status')
        
        if [ "$status" = "100" ]; then
            log "✅ Active Scan completado"
            break
        fi
        
        log "🔥 Active Scan progreso: $status%"
        sleep 10
    done
}

# Función para generar reportes
generate_reports() {
    log "📊 Generando reportes de seguridad..."
    
    local timestamp=$(date +%Y%m%d_%H%M%S)
    
    # Reporte HTML
    log "📄 Generando reporte HTML..."
    curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/core/view/htmlreport/?apikey=$API_KEY" > "$REPORT_DIR/security_report_${timestamp}.html"
    
    # Reporte JSON
    log "📄 Generando reporte JSON..."
    curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/core/other/jsonreport/?apikey=$API_KEY" > "$REPORT_DIR/security_report_${timestamp}.json"
    
    # Reporte XML
    log "📄 Generando reporte XML..."
    curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/core/other/xmlreport/?apikey=$API_KEY" > "$REPORT_DIR/security_report_${timestamp}.xml"
    
    # Reporte Markdown
    log "📄 Generando reporte Markdown..."
    curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/core/other/mdreport/?apikey=$API_KEY" > "$REPORT_DIR/security_report_${timestamp}.md"
    
    log "✅ Reportes generados en: $REPORT_DIR"
    log "📄 Reporte principal: $REPORT_DIR/security_report_${timestamp}.html"
}

# Función para analizar alertas
analyze_alerts() {
    log "🔍 Analizando alertas de seguridad..."
    
    local alert_file="$OUTPUT_DIR/alerts_${timestamp}.json"
    curl -s "http://$ZAP_HOST:$ZAP_PORT/JSON/alert/view/alerts/?apikey=$API_KEY&start=0&count=1000" > "$alert_file"
    
    # Contar alertas por riesgo
    local high=$(jq '[.alerts[] | select(.risk == "High")] | length' "$alert_file")
    local medium=$(jq '[.alerts[] | select(.risk == "Medium")] | length' "$alert_file")
    local low=$(jq '[.alerts[] | select(.risk == "Low")] | length' "$alert_file")
    local informational=$(jq '[.alerts[] | select(.risk == "Informational")] | length' "$alert_file")
    
    log "📊 Resumen de Alertas:"
    log "🔴 High: $high"
    log "🟡 Medium: $medium"
    log "🟢 Low: $low"
    log "ℹ️ Informational: $informational"
    
    # Guardar resumen
    cat > "$OUTPUT_DIR/alerts_summary_${timestamp}.txt" << EOF
Security Scan Summary - Sistema Biométrico
Generated: $(date)

Alert Summary:
- High Risk: $high
- Medium Risk: $medium  
- Low Risk: $low
- Informational: $informational
- Total Alerts: $((high + medium + low + informational))

Recommendations:
- Immediately fix all High Risk alerts
- Review and fix Medium Risk alerts within 7 days
- Address Low Risk alerts in next release cycle
- Review Informational alerts for potential improvements

Next Steps:
1. Review detailed report at: $REPORT_DIR/security_report_${timestamp}.html
2. Prioritize fixes by risk level
3. Implement security patches
4. Re-scan after fixes
5. Add to security regression testing
EOF
    
    log "📋 Resumen guardado en: $OUTPUT_DIR/alerts_summary_${timestamp}.txt"
}

# Función para escaneo de APIs específicas
api_security_scan() {
    log "🔌 Ejecutando escaneo de seguridad de APIs..."
    
    # Definir endpoints de la API
    local api_endpoints=(
        "$TARGET_URL/api/health"
        "$TARGET_URL/api/auth/login"
        "$TARGET_URL/api/empleados"
        "$TARGET_URL/api/asistencia"
        "$TARGET_URL/api/reportes"
    )
    
    for endpoint in "${api_endpoints[@]}"; do
        log "🔌 Escaneando endpoint: $endpoint"
        
        # Spider scan del endpoint
        spider_scan "$endpoint"
        
        # Active scan del endpoint  
        active_scan "$endpoint"
        
        # Pequeña pausa entre endpoints
        sleep 5
    done
}

# Función para pruebas de autenticación
auth_security_test() {
    log "🔐 Ejecutando pruebas de seguridad de autenticación..."
    
    # Test SQL Injection en login
    local sql_payloads=(
        "' OR '1'='1"
        "' UNION SELECT 'admin','admin' --"
        "admin'--"
        "admin'/*"
    )
    
    for payload in "${sql_payloads[@]}"; do
        log "🧪 Probando SQL Injection payload: $payload"
        
        curl -X POST "$TARGET_URL/api/auth/login" \
             -H "Content-Type: application/json" \
             -d "{\"username\":\"$payload\",\"password\":\"$payload\"}" \
             -o "$OUTPUT_DIR/auth_test_${timestamp}_sql.txt" \
             -w "%{http_code}\n" \
             -s >> "$OUTPUT_DIR/auth_test_${timestamp}_sql.txt"
    done
    
    # Test Brute Force básico
    log "🧪 Probando protección contra Brute Force..."
    
    for i in {1..10}; do
        curl -X POST "$TARGET_URL/api/auth/login" \
             -H "Content-Type: application/json" \
             -d "{\"username\":\"testuser$i\",\"password\":\"wrongpass\"}" \
             -o /dev/null \
             -w "%{http_code}\n" \
             -s >> "$OUTPUT_DIR/brute_force_test_${timestamp}.txt"
        
        sleep 0.5
    done
    
    log "✅ Pruebas de autenticación completadas"
}

# Función para pruebas de XSS
xss_security_test() {
    log "🕸️ Ejecutando pruebas de XSS..."
    
    local xss_payloads=(
        "<script>alert('XSS')</script>"
        "javascript:alert('XSS')"
        "<img src=x onerror=alert('XSS')>"
        "';alert('XSS');//"
        "<svg onload=alert('XSS')>"
    )
    
    for payload in "${xss_payloads[@]}"; do
        log "🧪 Probando XSS payload: $payload"
        
        # Test en búsqueda
        curl -X GET "$TARGET_URL/empleados?search=$(echo -n "$payload" | jq -sRr @uri)" \
             -o "$OUTPUT_DIR/xss_test_${timestamp}.txt" \
             -w "%{http_code}\n" \
             -s >> "$OUTPUT_DIR/xss_test_${timestamp}.txt"
    done
    
    log "✅ Pruebas de XSS completadas"
}

# Función para pruebas de CSRF
csrf_security_test() {
    log "🔄 Ejecutando pruebas de CSRF..."
    
    # Test básico de CSRF token
    curl -X GET "$TARGET_URL/login" \
         -o "$OUTPUT_DIR/csrf_test_${timestamp}.html" \
         -s
    
    # Verificar si hay tokens CSRF
    if grep -q "csrf\|token" "$OUTPUT_DIR/csrf_test_${timestamp}.html"; then
        log "✅ Token CSRF detectado"
    else
        log "⚠️ No se detectaron tokens CSRF - posible vulnerabilidad"
    fi
    
    log "✅ Pruebas de CSRF completadas"
}

# Función para pruebas de headers de seguridad
security_headers_test() {
    log "🛡️ Verificando headers de seguridad..."
    
    local response_headers=$(curl -I -s "$TARGET_URL" | tr -d '\r')
    
    local required_headers=(
        "X-Frame-Options"
        "X-Content-Type-Options" 
        "X-XSS-Protection"
        "Content-Security-Policy"
        "Strict-Transport-Security"
        "Referrer-Policy"
    )
    
    log "📋 Análisis de Headers de Seguridad:"
    
    for header in "${required_headers[@]}"; do
        if echo "$response_headers" | grep -iq "$header"; then
            log "✅ $header: Presente"
        else
            log "❌ $header: Ausente (Vulnerabilidad)"
        fi
    done
    
    # Guardar análisis completo
    echo "$response_headers" > "$OUTPUT_DIR/security_headers_${timestamp}.txt"
    log "📁 Headers guardados en: $OUTPUT_DIR/security_headers_${timestamp}.txt"
}

# Función principal
main() {
    log "🎯 Iniciando Security Scanning Suite"
    log "Target URL: $TARGET_URL"
    log "Output Directory: $OUTPUT_DIR"
    
    # Verificar prerequisitos
    if ! check_zap; then
        exit 1
    fi
    
    # Verificar que el target esté disponible
    if ! curl -f "$TARGET_URL" > /dev/null 2>&1; then
        log "❌ Error: El target $TARGET_URL no está disponible"
        exit 1
    fi
    
    # Iniciar ZAP
    if ! start_zap; then
        exit 1
    fi
    
    # Registrar cleanup al salir
    trap stop_zap EXIT INT TERM
    
    # Ejecutar suite de seguridad
    log "\n=== Spider Scan ==="
    spider_scan "$TARGET_URL"
    
    log "\n=== Active Scan ==="
    active_scan "$TARGET_URL"
    
    log "\n=== API Security Scan ==="
    api_security_scan
    
    log "\n=== Authentication Security Test ==="
    auth_security_test
    
    log "\n=== XSS Security Test ==="
    xss_security_test
    
    log "\n=== CSRF Security Test ==="
    csrf_security_test
    
    log "\n=== Security Headers Test ==="
    security_headers_test
    
    log "\n=== Generando Reportes ==="
    generate_reports
    
    log "\n=== Analizando Alertas ==="
    analyze_alerts
    
    # Cleanup será manejado por trap
    log "\n🎉 Security Scanning completado exitosamente!"
    log "📊 Resultados guardados en: $OUTPUT_DIR"
    log "📄 Reportes disponibles en: $REPORT_DIR"
    log "📋 Ver resumen en: $(ls -t "$OUTPUT_DIR"/alerts_summary_*.txt | head -1)"
    
    # Mostrar advertencia de próximos pasos
    log "\n⚠️ Próximos Pasos Recomendados:"
    log "1. Revisar reporte HTML para detalles completos"
    log "2. Priorizar correcciones por nivel de riesgo"
    log "3. Implementar patches de seguridad"
    log "4. Realizar re-scan después de correcciones"
    log "5. Agregar pruebas de regresión de seguridad al CI/CD"
    
    exit 0
}

# Ejecutar función principal
main "$@"