#!/bin/bash

# Script de Performance Testing para Sistema Biométrico
# Utiliza Apache Bench y otras herramientas de testing

set -e  # Exit on any error

echo "🚀 Iniciando Performance Testing..."
echo "Timestamp: $(date)"

# Configuración
BASE_URL="http://localhost:8000"
OUTPUT_DIR="tests/performance/results"
LOG_FILE="tests/performance/performance.log"
TEMP_DIR="tests/performance/temp"

# Crear directorios necesarios
mkdir -p "$OUTPUT_DIR" "$TEMP_DIR"
mkdir -p "$(dirname "$LOG_FILE")"

# Función de logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Función para verificar que el servidor esté corriendo
check_server() {
    log "🔍 Verificando que el servidor esté corriendo..."
    
    if curl -f "$BASE_URL/health" > /dev/null 2>&1; then
        log "✅ Servidor corriendo correctamente"
        return 0
    else
        log "❌ Error: El servidor no está corriendo en $BASE_URL"
        return 1
    fi
}

# Función para ejecutar Apache Bench
run_apache_bench() {
    local url=$1
    local requests=$2
    local concurrency=$3
    local test_name=$4
    
    log "📊 Ejecutando Apache Bench: $test_name"
    log "URL: $url"
    log "Requests: $requests, Concurrency: $concurrency"
    
    local output_file="$OUTPUT_DIR/ab_${test_name}.txt"
    
    # Ejecutar Apache Bench
    ab -n "$requests" -c "$concurrency" -k -r "$url" > "$output_file" 2>&1
    
    # Verificar resultado
    if [ $? -eq 0 ]; then
        log "✅ Apache Bench completado: $test_name"
        
        # Extraer métricas clave
        local rps=$(grep "Requests per second" "$output_file" | awk '{print $4}')
        local time_per_request=$(grep "Time per request" "$output_file" | awk '{print $4}')
        local failed=$(grep "Failed requests" "$output_file" | awk '{print $3}')
        
        log "📈 RPS: $rps"
        log "⏱️ Time per request: ${time_per_request}ms"
        log "❌ Failed requests: $failed"
        
        # Guardar métricas en JSON
        local json_file="$OUTPUT_DIR/metrics_${test_name}.json"
        cat > "$json_file" << EOF
{
    "test_name": "$test_name",
    "url": "$url",
    "requests": $requests,
    "concurrency": $concurrency,
    "rps": $rps,
    "time_per_request_ms": $time_per_request,
    "failed_requests": $failed,
    "timestamp": "$(date -Iseconds)"
}
EOF
        
    else
        log "❌ Error en Apache Bench: $test_name"
        return 1
    fi
}

# Función para pruebas de carga progresivas
progressive_load_test() {
    local url=$1
    local base_requests=$2
    
    log "🔥 Ejecutando pruebas de carga progresivas"
    
    # Diferentes niveles de concurrencia
    for concurrency in 1 5 10 25 50 100; do
        local requests=$((base_requests * concurrency))
        local test_name="progressive_${concurrency}c"
        
        log "🧪 Probando concurrencia: $concurrency"
        run_apache_bench "$url" "$requests" "$concurrency" "$test_name"
        
        # Pequeña pausa entre pruebas
        sleep 2
    done
}

# Función para pruebas de estrés
stress_test() {
    local url=$1
    local duration=$2  # segundos
    
    log "💪 Ejecutando prueba de estrés por $duration segundos"
    
    local start_time=$(date +%s)
    local test_name="stress_${duration}s"
    
    # Calcular requests aproximadas (100 RPS)
    local total_requests=$((duration * 100))
    
    while [ $(($(date +%s) - start_time)) -lt $duration ]; do
        run_apache_bench "$url" "1000" "10" "${test_name}_$(date +%s)"
        
        # Pausa corta entre iteraciones
        sleep 5
    done
    
    log "✅ Prueba de estrés completada"
}

# Función para pruebas de endurance
endurance_test() {
    local url=$1
    local duration=$2  # minutos
    
    log "🏃 Ejecutando prueba de endurance por $duration minutos"
    
    local test_name="endurance_${duration}m"
    local start_time=$(date +%s)
    local end_time=$((start_time + (duration * 60)))
    
    while [ $(date +%s) -lt $end_time ]; do
        # 30 segundos de prueba seguidos de 10 de descanso
        run_apache_bench "$url" "3000" "10" "${test_name}_$(date +%s)"
        
        log "⏱️ Restantes: $(((end_time - $(date +%s)) / 60)) minutos"
        sleep 10
    done
    
    log "✅ Prueba de endurance completada"
}

# Función para pruebas de APIs específicas
api_performance_test() {
    log "🔌 Ejecutando pruebas de performance de APIs"
    
    # Test API health
    run_apache_bench "$BASE_URL/api/health" "1000" "10" "api_health"
    
    # Test API login (con datos mock)
    run_apache_bench "$BASE_URL/api/auth/login" "500" "5" "api_login"
    
    # Test API empleados
    run_apache_bench "$BASE_URL/api/empleados" "1000" "10" "api_empleados"
    
    # Test API reportes
    run_apache_bench "$BASE_URL/api/reportes/asistencia" "500" "5" "api_reportes"
}

# Función para pruebas de carga de archivos estáticos
static_assets_test() {
    log "📁 Ejecutando pruebas de assets estáticos"
    
    # Test CSS files
    run_apache_bench "$BASE_URL/public/css/style.css" "1000" "10" "css_style"
    
    # Test JS files
    run_apache_bench "$BASE_URL/assets/js/main.js" "1000" "10" "js_main"
    
    # Test images
    run_apache_bench "$BASE_URL/public/images/logo.png" "1000" "10" "image_logo"
}

# Función para pruebas de carga específicas de paginas
page_load_test() {
    log "📄 Ejecutando pruebas de carga de páginas"
    
    # Test página principal
    run_apache_bench "$BASE_URL/" "2000" "20" "page_home"
    
    # Test login
    run_apache_bench "$BASE_URL/login" "1000" "10" "page_login"
    
    # Test dashboard
    run_apache_bench "$BASE_URL/dashboard" "1000" "10" "page_dashboard"
    
    # Test empleados
    run_apache_bench "$BASE_URL/empleados" "2000" "20" "page_empleados"
    
    # Test reportes
    run_apache_bench "$BASE_URL/reportes" "1000" "10" "page_reportes"
}

# Función para generar reporte
generate_report() {
    log "📊 Generando reporte de performance"
    
    local report_file="$OUTPUT_DIR/performance_report_$(date +%Y%m%d_%H%M%S).html"
    
    cat > "$report_file" << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>Performance Test Report - Sistema Biométrico</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #9F2241; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .metric { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .chart-container { width: 800px; height: 400px; margin: 20px 0; }
        .good { color: #28a745; }
        .warning { color: #ffc107; }
        .critical { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 Performance Test Report</h1>
        <p>Sistema Biométrico - Generado: $(date)</p>
    </div>

    <div class="metric">
        <h3>📊 Resumen de Métricas</h3>
        <div id="summaryChart" class="chart-container"></div>
    </div>

    <div class="metric">
        <h3>⚡ Requests Per Second (RPS)</h3>
        <div id="rpsChart" class="chart-container"></div>
    </div>

    <div class="metric">
        <h3>⏱️ Time Per Request</h3>
        <div id="responseTimeChart" class="chart-container"></div>
    </div>

    <div class="metric">
        <h3>📋 Detalles de Pruebas</h3>
        <table id="resultsTable">
            <thead>
                <tr>
                    <th>Test Name</th>
                    <th>URL</th>
                    <th>Requests</th>
                    <th>Concurrency</th>
                    <th>RPS</th>
                    <th>Response Time (ms)</th>
                    <th>Failed Requests</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
EOF

    # Agregar resultados de las pruebas
    for json_file in "$OUTPUT_DIR"/metrics_*.json; do
        if [ -f "$json_file" ]; then
            python3 -c "
import json, sys
with open('$json_file', 'r') as f:
    data = json.load(f)
    
status_class = 'good'
if data['rps'] < 100:
    status_class = 'critical'
elif data['rps'] < 500:
    status_class = 'warning'

print('<tr>')
print(f\"<td>{data['test_name']}</td>\")
print(f\"<td>{data['url']}</td>\")
print(f\"<td>{data['requests']}</td>\")
print(f\"<td>{data['concurrency']}</td>\")
print(f\"<td>{data['rps']}</td>\")
print(f\"<td>{data['time_per_request_ms']}</td>\")
print(f\"<td>{data['failed_requests']}</td>\")
print(f\"<td class=\"{status_class}\">{status_class}</td>\")
print('</tr>')
"
        fi
    done >> "$report_file"

    cat >> "$report_file" << 'EOF'
            </tbody>
        </table>
    </div>

    <script>
        // Charts para visualización
        const ctx1 = document.getElementById('summaryChart').getContext('2d');
        const ctx2 = document.getElementById('rpsChart').getContext('2d');
        const ctx3 = document.getElementById('responseTimeChart').getContext('2d');
        
        // Agregar código JavaScript para generar gráficos
        // ... (se agregaría dinámicamente)
    </script>
</body>
</html>
EOF

    log "📄 Reporte generado: $report_file"
}

# Función para limpiar archivos temporales
cleanup() {
    log "🧹 Limpiando archivos temporales..."
    rm -rf "$TEMP_DIR"
    log "✅ Limpieza completada"
}

# Función principal
main() {
    log "🎯 Iniciando Performance Testing Suite"
    log "Base URL: $BASE_URL"
    
    # Verificar que el servidor esté corriendo
    if ! check_server; then
        exit 1
    fi
    
    # Verificar que Apache Bench esté instalado
    if ! command -v ab &> /dev/null; then
        log "❌ Error: Apache Bench (ab) no está instalado"
        log "💡 Instalar con: sudo apt-get install apache2-utils (Ubuntu/Debian)"
        log "💡 o: yum install httpd-tools (CentOS/RHEL)"
        exit 1
    fi
    
    # Ejecutar suite de pruebas
    log "\n=== Pruebas Básicas ==="
    page_load_test
    
    log "\n=== Pruebas de API ==="
    api_performance_test
    
    log "\n=== Pruebas de Carga Progresiva ==="
    progressive_load_test "$BASE_URL/dashboard" "100"
    
    log "\n=== Pruebas de Assets Estáticos ==="
    static_assets_test
    
    log "\n=== Pruebas de Estrés (2 minutos) ==="
    stress_test "$BASE_URL/api/health" "120"
    
    log "\n=== Generando Reporte ==="
    generate_report
    
    # Cleanup
    cleanup
    
    log "\n🎉 Performance Testing completado exitosamente!"
    log "📊 Resultados guardados en: $OUTPUT_DIR"
    log "📄 Reporte HTML disponible en: $(ls -t $OUTPUT_DIR/performance_report_*.html | head -1)"
    
    exit 0
}

# Capturar señales para cleanup
trap cleanup EXIT INT TERM

# Ejecutar función principal
main "$@"