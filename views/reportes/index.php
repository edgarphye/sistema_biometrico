<?php
require_once 'config.php';
require_once 'models/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->query("SELECT id, nombre, apellido FROM empleados ORDER BY apellido, nombre");
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$content = '
<div class="container mt-4">
    <h2>Reportes del Sistema Biométrico</h2>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Generar Reporte</h5>
                </div>
                <div class="card-body">
                    <form id="reporteForm">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                                <select class="form-select" id="tipo_reporte" name="tipo_reporte" required onchange="toggleFilters()">
                                    <option value="">Seleccionar...</option>
                                    <optgroup label="No Justificados">
                                        <option value="falta">Faltas</option>
                                    </optgroup>
                                    <optgroup label="Justificados">
                                        <option value="vacaciones">Vacaciones</option>
                                        <option value="licencia_medica">Licencia Médica</option>
                                        <option value="dia_economico">Día Económico</option>
                                        <option value="comision_todo_dia">Comisión Todo el Día</option>
                                        <option value="constancia_tiempo">Constancia de Tiempo</option>
                                        <option value="PDSEP-SNTE">PDSEP-SNTE</option>
                                        <option value="EYR">EYR</option>
                                        <option value="CLIDDA">CLIDDA</option>
                                        <option value="cuidados_maternos">Cuidados Maternos</option>
                                    </optgroup>
                                    <optgroup label="Retardos">
                                        <option value="retardos">Retardos</option>
                                    </optgroup>
                                    <optgroup label="Resumen">
                                        <option value="resumen">Resumen General</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="empleado_id" class="form-label">Empleado (ID o Nombre)</label>
                                <select class="form-select" id="empleado_id" name="empleado_id">
                                    <option value="">Todos los empleados</option>
                                    ' . implode('', array_map(function($emp) {
                                        return '<option value="' . $emp['id'] . '">' . htmlspecialchars($emp['apellido'] . ' ' . $emp['nombre']) . ' (ID: ' . $emp['id'] . ')</option>';
                                    }, $empleados)) . '
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                            </div>
                            <div class="col-md-2">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-primary" onclick="generarReporte()">
                                    <i class="fas fa-chart-bar"></i> Generar Reporte
                                </button>
                                <button type="button" class="btn btn-success" onclick="exportarExcel()">
                                    <i class="fas fa-file-excel"></i> Exportar a Excel
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="imprimirReporte()">
                                    <i class="fas fa-print"></i> Imprimir
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Resultados del Reporte</h5>
                    <span id="total-registros" class="badge bg-primary"></span>
                </div>
                <div class="card-body">
                    <div id="reporte-resultados">
                        <p class="text-muted">Seleccione los filtros y genere el reporte para ver los resultados.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleFilters() {
    const tipo = document.getElementById("tipo_reporte").value;
    const empleadoSelect = document.getElementById("empleado_id");
    
    if (tipo === "resumen") {
        empleadoSelect.value = "";
        empleadoSelect.disabled = true;
    } else {
        empleadoSelect.disabled = false;
    }
}

function generarReporte() {
    const formData = new FormData(document.getElementById("reporteForm"));
    const params = new URLSearchParams(formData);

    document.getElementById("reporte-resultados").innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Generando reporte...</div>';

    fetch("/sistema_biometrico/api/reportes.php?" + params.toString())
        .then(response => {
            if (!response.ok) {
                throw new Error("HTTP " + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success === false) {
                document.getElementById("reporte-resultados").innerHTML = '<div class="alert alert-danger">' + (data.error || 'Error desconocido') + '</div>';
                return;
            }
            mostrarResultados(data);
        })
        .catch(error => {
            console.error("Error:", error);
            document.getElementById("reporte-resultados").innerHTML =
                \'<div class="alert alert-danger">Error al generar el reporte: \' + error.message + \'</div>';
        });
}

function mostrarResultados(data) {
    let html = "";

    if (data.tipo === "resumen") {
        html = generarResumen(data.datos);
    } else if (data.datos && data.datos.length > 0) {
        html = generarTabla(data.datos, data.tipo);
    } else {
        html = \'<div class="alert alert-info">No se encontraron registros con los filtros seleccionados.</div>\';
    }

    document.getElementById("reporte-resultados").innerHTML = html;
    document.getElementById("total-registros").textContent = (data.datos ? data.datos.length : 0) + " registros";
}

function generarResumen(datos) {
    let html = \'<table class="table table-bordered table-striped"><thead><tr>\';
    html += \'<th>Tipo</th><th>Total</th><th>Porcentaje</th>\';
    html += \'</tr></thead><tbody>\';
    
    const total = datos.reduce((sum, item) => sum + item.total, 0);
    
    datos.forEach(item => {
        const pct = total > 0 ? ((item.total / total) * 100).toFixed(1) : 0;
        const badgeClass = item.tipo === "falta" ? "bg-danger" : (item.tipo === "retardos" ? "bg-warning" : "bg-success");
        html += `<tr>
            <td><span class="badge ${badgeClass}">${item.tipo}</span></td>
            <td>${item.total.toLocaleString()}</td>
            <td>${pct}%</td>
        </tr>`;
    });
    
    html += \'<tr class="table-primary"><td><strong>Total</strong></td><td><strong>\' + total.toLocaleString() + \'</strong></td><td>100%</td></tr>\';
    html += \'</tbody></table>\';
    return html;
}

function generarTabla(datos, tipo) {
    const columns = {
        "falta": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "vacaciones": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "licencia_medica": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "dia_economico": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "comision_todo_dia": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "constancia_tiempo": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "PDSEP-SNTE": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "EYR": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "CLIDDA": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "cuidados_maternos": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida"],
        "retardos": ["Empleado", "ID", "Fecha", "Hora Entrada", "Hora Salida", "Minutos"]
    };
    
    const cols = columns[tipo] || columns["falta"];
    
    let html = \'<div class="table-responsive"><table class="table table-striped table-hover"><thead><tr>\';
    cols.forEach(col => html += \'<th>\' + col + \'</th>\');
    html += \'</tr></thead><tbody>\';
    
    datos.forEach(item => {
        html += \'<tr>\';
        html += \'<td>\' + item.empleado + \'</td>\';
        html += \'<td>\' + item.empleado_id + \'</td>\';
        html += \'<td>\' + item.fecha + \'</td>\';
        html += \'<td>\' + (item.hora_entrada || "-") + \'</td>\';
        html += \'<td>\' + (item.hora_salida || "-") + \'</td>\';
        if (tipo === "retardos" && item.minutos_retardo) {
            html += \'<td><span class="badge bg-warning">\' + item.minutos_retardo + \' min</span></td>\';
        }
        html += \'</tr>\';
    });
    
    html += \'</tbody></table></div>\';
    return html;
}

function exportarExcel() {
    const formData = new FormData(document.getElementById("reporteForm"));
    const params = new URLSearchParams(formData);

    window.open("/sistema_biometrico/api/reportes.php?exportar=excel&" + params.toString(), "_blank");
}

function imprimirReporte() {
    window.print();
}
</script>
';

include __DIR__ . '/../layout.php';
?>
