<?php
require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/ConfigOficio.php';
require_once __DIR__ . '/../services/OficioNotasMalasService.php';

class NotasMalasController extends BaseController {
    
    private const RETARDOS_MENORES_POR_NOTA = 2;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        
        $rol = $_SESSION['rol'] ?? '';
        if (!in_array($rol, ['superadmin', 'admin', 'rh'])) {
            $_SESSION['error'] = 'No tienes permiso para acceder a notas malas.';
            $this->redirect('/dashboard');
            return;
        }
    }
    
    public function index() {
        $db = Database::getInstance()->getConnection();
        
        $mes = $_GET['mes'] ?? date('Y-m');
        $anio = (int)substr($mes, 0, 4);
        $mesNum = (int)substr($mes, 5, 2);
        $area_id = $_GET['area_id'] ?? null;
        $empleado_id = $_GET['empleado_id'] ?? null;
        $busqueda = $_GET['busqueda'] ?? ''; // Nuevo: búsqueda por nombre, ID, RFC o CURP
        $quincena = (int)($_GET['quincena'] ?? 0); // 0 = Todas, 1 = 1ª, 2 = 2ª
        
        require_once __DIR__ . '/../models/PlantillaDocumento.php';
        $servicio = new OficioNotasMalasService();
        
        // Datos unificados (notas_malas + retardos sin justificar) por empleado
        $incidencias = $servicio->getIncidenciasPeriodo($mesNum, $anio, $quincena);
        
        // Documentos de oficio ya generados en el periodo
        $periodoIni = "$anio-" . str_pad($mesNum, 2, '0', STR_PAD_LEFT) . '-01';
        $stmtDocs = $db->prepare("
            SELECT empleado_id, id, titulo, archivo_path, fecha_generacion
            FROM documentos_generados
            WHERE tipo_documento = 'oficio_notas_malas' AND periodo = ?
        ");
        $stmtDocs->execute([$periodoIni]);
        $docsPorEmpleado = [];
        foreach ($stmtDocs->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $docsPorEmpleado[$d['empleado_id']] = $d;
        }
        
        // Adaptar los datos del servicio al formato de la vista
        $empleadosRetardos = [];
        foreach ($incidencias as $empId => $inc) {
            if ($area_id && ($inc['area'] ?? '') != $area_id) {
                continue;
            }
            if ($empleado_id && (int)$empId != (int)$empleado_id) {
                continue;
            }
            if ($busqueda) {
                $hay = stripos($inc['nombre'] ?? '', $busqueda) !== false
                    || stripos($inc['apellido'] ?? '', $busqueda) !== false
                    || stripos($inc['rfc'] ?? '', $busqueda) !== false
                    || (is_numeric($busqueda) && (int)$busqueda == (int)$empId);
                if (!$hay) {
                    continue;
                }
            }
            $empleadosRetardos[$empId] = [
                'empleado_id' => $empId,
                'nombre' => $inc['nombre'],
                'apellido' => $inc['apellido'],
                'rfc' => $inc['rfc'],
                'area' => $inc['area'],
                'retardos_menores' => $inc['menores'],
                'retardos_mayores' => $inc['mayores'],
                'faltas' => $inc['faltas'],
                'notas_menores' => $inc['notas_menores'] ?? floor($inc['menores'] / self::RETARDOS_MENORES_POR_NOTA),
                'notas_mayores' => $inc['notas_mayores'] ?? ($inc['mayores'] + $inc['faltas']),
                'total_notas' => $inc['notas_malas'],
                'dias_suspension' => $inc['dias_suspension'] ?? 0,
                'requiere_suspension' => $inc['requiere_suspension'],
                'detalle_retardos' => $inc['retardos'],
                'oficio' => $docsPorEmpleado[$empId] ?? null
            ];
        }
        
        // Ordenar alfabéticamente
        uasort($empleadosRetardos, function($a, $b) {
            return strcmp($a['apellido'] . ' ' . $a['nombre'], $b['apellido'] . ' ' . $b['nombre']);
        });
        
        // Estadísticas generales
        $totalRetardosMenores = 0;
        $totalRetardosMayores = 0;
        $totalNotasMenores = 0;
        $totalNotasMayores = 0;
        $notasPorArea = [];
        
        foreach ($empleadosRetardos as $emp) {
            $totalRetardosMenores += $emp['retardos_menores'];
            $totalRetardosMayores += $emp['retardos_mayores'];
            $totalNotasMenores += $emp['notas_menores'];
            $totalNotasMayores += $emp['notas_mayores'];
            
            $area = $emp['area'] ?: 'Sin área';
            if (!isset($notasPorArea[$area])) {
                $notasPorArea[$area] = ['area' => $area, 'notas_malas' => 0, 'retardos' => 0];
            }
            $notasPorArea[$area]['notas_malas'] += $emp['total_notas'];
            $notasPorArea[$area]['retardos'] += $emp['retardos_menores'] + $emp['retardos_mayores'];
        }
        
        // Lista de áreas para filtro
        $stmtAreas = $db->query("SELECT DISTINCT area FROM empleados WHERE area IS NOT NULL AND area != '' ORDER BY area");
        $areas = $stmtAreas->fetchAll(PDO::FETCH_COLUMN);
        
        $content = '
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Notas Malas e Incidencias</h2>
                    <p class="text-secondary">Cálculo en tiempo real: 2 retardos menores por quincena = 1 nota mala | 1 retardo mayor = 1 nota mala | 5 notas = 1 día de suspensión</p>
                </div>
                <div class="col-md-4 text-end">
                    <form method="GET" action="' . BASE_URL . '/notas-malas" class="d-flex gap-2">
                        <input type="text" name="busqueda" class="form-control" placeholder="Buscar por nombre, ID, RFC o CURP" value="' . htmlspecialchars($busqueda ?? '') . '">
                        <select name="area_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Todas las áreas</option>';
                            foreach ($areas as $area) {
                                $content .= '<option value="' . htmlspecialchars($area) . '"' . ($area_id == $area ? ' selected' : '') . '>' . htmlspecialchars($area) . '</option>';
                            }
        $content .= '</select>
                        <select name="mes" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los meses</option>';
                            foreach ($this->getMesesDisponibles($db) as $m) {
                                $content .= '<option value="' . $m['valor'] . '"' . ($mes == $m['valor'] ? ' selected' : '') . '>' . $m['label'] . '</option>';
                            }
        $content .= '</select>
                        <select name="quincena" class="form-select" onchange="this.form.submit()">
                            <option value="0">Todas las quincenas</option>
                            <option value="1"' . ($quincena == 1 ? ' selected' : '') . '>1ª Quincena</option>
                            <option value="2"' . ($quincena == 2 ? ' selected' : '') . '>2ª Quincena</option>
                        </select>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12 text-end">
                    <a href="' . BASE_URL . '/notas-malas/config" class="btn btn-outline-secondary me-2"><i class="fas fa-cog me-1"></i>Configuración</a>
                    <button type="button" class="btn btn-success" id="btnGenerarTodos"><i class="fas fa-file-archive me-1"></i>Generar todos los oficios (ZIP)</button>
                </div>
            </div>
            
            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #E8D5D9 0%, #C9A4AA 100%); border-left: 4px solid #9F2241;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #691C32;">' . ($totalNotasMenores + $totalNotasMayores) . '</h2>
                                    <small style="color: #4a1424;">Total Notas</small>
                                </div>
                                <i class="fas fa-exclamation-circle fa-2x" style="color: #9F2241;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #D5E5DE 0%, #B8CCBF 100%); border-left: 4px solid #235B4E;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #10312B;">' . $totalRetardosMenores . '</h2>
                                    <small style="color: #10312B;">Retardos Menores</small>
                                </div>
                                <i class="fas fa-clock fa-2x" style="color: #235B4E;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #E8D5D5 0%, #CCB4B4 100%); border-left: 4px solid #C62828;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #8E0000;">' . $totalRetardosMayores . '</h2>
                                    <small style="color: #8E0000;">Retardos Mayores</small>
                                </div>
                                <i class="fas fa-exclamation-triangle fa-2x" style="color: #C62828;"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm" style="background: linear-gradient(135deg, #EDE4D3 0%, #DDC9A3 100%); border-left: 4px solid #BC955C;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2 class="mb-0 fw-bold" style="color: #691C32;">' . count($empleadosRetardos) . '</h2>
                                    <small style="color: #691C32;">Empleados</small>
                                </div>
                                <i class="fas fa-users fa-2x" style="color: #691C32;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notas por Área -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Notas Malas por Área</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Área</th>
                                            <th class="text-center">Empleados</th>
                                            <th class="text-center">Total Retardos</th>
                                            <th class="text-center">Notas Malas</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
        if (!empty($notasPorArea)) {
            foreach ($notasPorArea as $areaData) {
                $content .= '
                                        <tr>
                                            <td>' . htmlspecialchars($areaData['area']) . '</td>
                                            <td class="text-center">' . count(array_filter($empleadosRetardos, fn($e) => ($e['area'] ?? '') == $areaData['area'])) . '</td>
                                            <td class="text-center">' . $areaData['retardos'] . '</td>
                                            <td class="text-center"><strong>' . $areaData['notas_malas'] . '</strong></td>
                                        </tr>';
            }
        } else {
            $content .= '<tr><td colspan="4" class="text-center text-muted">No hay retardos registrados</td></tr>';
        }
        $content .= '
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detalle de Empleados con Retardos -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Detalle de Retardos Sin Justificar</h5>
                </div>
                <div class="card-body">';
        
        if (!empty($empleadosRetardos)) {
            foreach ($empleadosRetardos as $emp) {
                $oficio = $emp['oficio'] ?? null;
                $estadoOficio = '';
                if ($oficio) {
                    $estadoOficio = '
                        <span class="badge bg-success me-1" title="' . htmlspecialchars($oficio['titulo']) . '">Oficio generado</span>
                        <button type="button" class="btn btn-sm btn-outline-success btn-ver-oficio me-1" data-id="' . (int)$oficio['id'] . '" data-titulo="' . htmlspecialchars($oficio['titulo']) . '"><i class="fas fa-eye me-1"></i>Ver</button>
                        <a href="' . BASE_URL . '/notas-malas/oficio/' . (int)$oficio['id'] . '" class="btn btn-sm btn-outline-primary me-1"><i class="fas fa-download me-1"></i>Descargar Word</a>
                        <button type="button" class="btn btn-sm btn-outline-warning btn-regenerar" data-id="' . (int)$emp['empleado_id'] . '"><i class="fas fa-redo me-1"></i>Regenerar</button>';
                } else {
                    $estadoOficio = '<button type="button" class="btn btn-sm btn-primary btn-generar" data-id="' . (int)$emp['empleado_id'] . '"><i class="fas fa-file-word me-1"></i>Generar oficio</button>';
                }
                $content .= '
                    <div class="mb-4 border rounded p-3">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="mb-1">' . htmlspecialchars($emp['nombre'] . ' ' . $emp['apellido']) . '</h5>
                                <small class="text-muted">RFC: ' . htmlspecialchars($emp['rfc'] ?? 'N/A') . ' | Área: ' . htmlspecialchars($emp['area'] ?? 'Sin área') . '</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-info me-1">Menores: ' . $emp['retardos_menores'] . ' → ' . $emp['notas_menores'] . ' nota(s)</span>
                                <span class="badge bg-danger me-1">Mayores: ' . $emp['retardos_mayores'] . ' → ' . $emp['notas_mayores'] . ' nota(s)</span>
                                <span class="badge bg-dark me-2">Total: ' . $emp['total_notas'] . ' nota(s)</span>
                                ' . ($emp['requiere_suspension'] ? '<span class="badge bg-danger me-1">Suspensión (' . (int)($emp['dias_suspension'] ?? 1) . ' día(s))</span>' : '') . '
                                <br><div class="mt-2">' . $estadoOficio . '</div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Hora Entrada</th>
                                        <th>Minutos Retraso</th>
                                        <th>Tipo</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>';
                foreach ($emp['detalle_retardos'] as $r) {
                    $tipo = $r['tipo_retraso'] ?? $r['tipo'] ?? '-';
                    $tipoClass = $tipo === 'retardo_menor' ? 'bg-info' : 'bg-danger';
                    $content .= '
                                    <tr>
                                        <td>' . $r['fecha'] . '</td>
                                        <td>' . substr($r['hora_entrada'] ?? '', 0, 5) . '</td>
                                        <td><span class="badge bg-warning">' . ($r['minutos_retardo'] ?? 0) . ' min</span></td>
                                        <td><span class="badge ' . $tipoClass . '">' . ucfirst(str_replace('_', ' ', $tipo)) . '</span></td>
                                        <td>' . htmlspecialchars($r['motivo_detalle'] ?? '-') . '</td>
                                    </tr>';
                }
                $content .= '
                                </tbody>
                            </table>
                        </div>
                    </div>';
            }
        } else {
            $content .= '
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>¡Sin retardos sin justificar!</h5>
                        <p class="text-muted">No hay retardos sin justificar en el período seleccionado.</p>
                    </div>';
        }
        
        $content .= '
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalVerOficio" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-2">
                        <h5 class="modal-title"><i class="fas fa-file-word me-2 text-primary"></i><span id="modalOficioTitulo"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-0 bg-light">
                        <iframe id="modalOficioIframe" src="about:blank" style="width:100%;height:70vh;border:0;background:#fff;"></iframe>
                    </div>
                    <div class="modal-footer py-2">
                        <a id="modalOficioDescargar" href="#" class="btn btn-primary"><i class="fas fa-download me-1"></i>Descargar Word</a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        $(function() {
            var mesActual = "' . htmlspecialchars($mes) . '";
            var quincenaActual = ' . (int)$quincena . ';

            function getCsrf() {
                return document.getElementById("csrf_token")?.value || "";
            }

            function getMesAnio() {
                var m = mesActual || (new Date().getFullYear() + "-" + String(new Date().getMonth() + 1).padStart(2, "0"));
                return { anio: parseInt(m.substring(0, 4)), mes: parseInt(m.substring(5, 7)) };
            }

            function postGenerar(empleadoId, regenerar) {
                var p = getMesAnio();
                var btn = $(regenerar ? ".btn-regenerar[data-id=\"" + empleadoId + "\"]" : ".btn-generar[data-id=\"" + empleadoId + "\"]");
                var textoOriginal = btn.html();
                btn.prop("disabled", true).html("<i class=\"fas fa-spinner fa-spin me-1\"></i>Generando...");

                $.ajax({
                    url: BASE_URL + "/notas-malas/generar-oficio",
                    method: "POST",
                    data: JSON.stringify({
                        empleado_id: empleadoId,
                        mes: p.mes,
                        anio: p.anio,
                        quincena: quincenaActual,
                        regenerar: regenerar ? 1 : 0
                    }),
                    contentType: "application/json",
                    headers: { "X-CSRF-Token": getCsrf() },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            alert(res.ya_existia
                                ? "El oficio ya existía. " + (res.folio || "")
                                : "Oficio generado: " + (res.folio || ""));
                            location.reload();
                        } else {
                            alert("Error: " + (res.error || "No se pudo generar."));
                        }
                    },
                    error: function(xhr) {
                        alert("Error al generar: " + (xhr.responseJSON?.error || xhr.status));
                    },
                    complete: function() {
                        btn.prop("disabled", false).html(textoOriginal);
                    }
                });
            }

            $(document).on("click", ".btn-generar", function() {
                postGenerar($(this).data("id"), false);
            });

            $(document).on("click", ".btn-regenerar", function() {
                if (!confirm("¿Regenerar el oficio? Se asignará un nuevo folio y se reemplazará el archivo.")) {
                    return;
                }
                postGenerar($(this).data("id"), true);
            });

            $(document).on("click", ".btn-ver-oficio", function() {
                var id = $(this).data("id");
                var titulo = $(this).data("titulo");
                $("#modalOficioTitulo").text(titulo);
                $("#modalOficioDescargar").attr("href", BASE_URL + "/notas-malas/oficio/" + id);
                $("#modalOficioIframe").attr("src", BASE_URL + "/notas-malas/oficio/" + id + "/preview");
                $("#modalVerOficio").modal("show");
            });

            $("#modalVerOficio").on("hidden.bs.modal", function() {
                $("#modalOficioIframe").attr("src", "about:blank");
            });

            $("#btnGenerarTodos").on("click", function() {
                if (!confirm("¿Generar oficios para todos los empleados con incidencias del periodo?")) {
                    return;
                }
                var p = getMesAnio();
                var btn = $(this);
                var textoOriginal = btn.html();
                btn.prop("disabled", true).html("<i class=\"fas fa-spinner fa-spin me-1\"></i>Generando...");

                $.ajax({
                    url: BASE_URL + "/notas-malas/generar-todos",
                    method: "POST",
                    data: JSON.stringify({ mes: p.mes, anio: p.anio, quincena: quincenaActual }),
                    contentType: "application/json",
                    headers: { "X-CSRF-Token": getCsrf() },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            alert("Generados: " + res.generados + " | Ya existentes: " + res.omitidos
                                + (res.errores && res.errores.length ? "\nErrores:\n" + res.errores.join("\n") : ""));
                            if (res.zip) {
                                window.location.href = BASE_URL + "/uploads/" + res.zip;
                            }
                        } else {
                            alert("Error: " + (res.error || "No se pudo generar."));
                        }
                    },
                    error: function(xhr) {
                        alert("Error al generar: " + (xhr.responseJSON?.error || xhr.status));
                    },
                    complete: function() {
                        btn.prop("disabled", false).html(textoOriginal);
                    }
                });
            });
        });
        </script>';
        
        include __DIR__ . '/../views/layout.php';
    }
    
    private function getMesesDisponibles($db) {
        $stmt = $db->query("
            SELECT DISTINCT DATE_FORMAT(fecha, '%Y-%m') as valor, 
                   DATE_FORMAT(fecha, '%M %Y') as label
            FROM retardos 
            WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ORDER BY valor DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function ajaxList() {
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->query("
                SELECT nm.*, 
                       CONCAT(e.nombre, ' ', e.apellido) as empleado_nombre,
                       e.area as area_nombre
                FROM notas_malas nm
                LEFT JOIN empleados e ON nm.empleado_id = e.id
                ORDER BY nm.created_at DESC
                LIMIT 100
            ");
            
            $this->jsonResponse(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET/POST /notas-malas/generar-oficio
     * Genera el oficio .docx de un empleado
     */
    public function generarOficio() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $empleado_id = (int)($input['empleado_id'] ?? ($_POST['empleado_id'] ?? 0));
            $mes = (int)($input['mes'] ?? ($_POST['mes'] ?? date('n')));
            $anio = (int)($input['anio'] ?? ($_POST['anio'] ?? date('Y')));
            $quincena = (int)($input['quincena'] ?? ($_POST['quincena'] ?? 0));
            $regenerar = !empty($input['regenerar'] ?? ($_POST['regenerar'] ?? false));

            if ($empleado_id <= 0) {
                $this->jsonResponse(['success' => false, 'error' => 'ID de empleado requerido'], 400);
            }

            $servicio = new OficioNotasMalasService();
            $resultado = $servicio->generarOficio($empleado_id, $mes, $anio, $quincena, $regenerar);
            $this->jsonResponse($resultado, $resultado['success'] ? 200 : 400);
        } catch (Exception $e) {
            $this->logException($e);
            $this->jsonResponse(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * POST /notas-malas/generar-todos
     * Genera oficios para todos los empleados con incidencias y devuelve ZIP
     */
    public function generarTodos() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $mes = (int)($input['mes'] ?? ($_POST['mes'] ?? date('n')));
            $anio = (int)($input['anio'] ?? ($_POST['anio'] ?? date('Y')));
            $quincena = (int)($input['quincena'] ?? ($_POST['quincena'] ?? 0));

            $servicio = new OficioNotasMalasService();
            $resultado = $servicio->generarMasivo($mes, $anio, $quincena);
            $this->jsonResponse($resultado, $resultado['success'] ? 200 : 400);
        } catch (Exception $e) {
            $this->logException($e);
            $this->jsonResponse(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * GET /notas-malas/oficio/{id}
     * Descarga/visualiza un oficio guardado en documentos_generados
     */
    public function descargarOficio($id) {
        require_once __DIR__ . '/../models/PlantillaDocumento.php';
        $doc = (new DocumentoGenerado())->getById((int)$id);
        if (!$doc || $doc['tipo_documento'] !== 'oficio_notas_malas') {
            http_response_code(404);
            echo 'Documento no encontrado';
            return;
        }

        $path = __DIR__ . '/../' . $doc['archivo_path'];
        if (!file_exists($path) || !is_file($path)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . basename($doc['archivo_path']) . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    /**
     * GET /notas-malas/oficio/{id}/preview
     * Vista previa del oficio en HTML autocontenido (convierte el docx con LibreOffice y cachea)
     */
    public function verOficioHtml($id) {
        require_once __DIR__ . '/../models/PlantillaDocumento.php';
        $doc = (new DocumentoGenerado())->getById((int)$id);
        if (!$doc || $doc['tipo_documento'] !== 'oficio_notas_malas') {
            http_response_code(404);
            echo 'Documento no encontrado';
            return;
        }

        $docx = __DIR__ . '/../' . $doc['archivo_path'];
        if (!file_exists($docx) || !is_file($docx)) {
            http_response_code(404);
            echo 'Archivo no encontrado';
            return;
        }

        $dir = dirname($docx) . '/.preview';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $html = $dir . '/' . pathinfo($docx, PATHINFO_FILENAME) . '.html';

        if (!file_exists($html) || filemtime($docx) > filemtime($html)) {
            $cmd = 'soffice --headless --norestore --convert-to "html:HTML:EmbedImages" --outdir '
                . escapeshellarg($dir)
                . ' -env:UserInstallation=file:///tmp/lo_oficios_www '
                . escapeshellarg($docx) . ' 2>&1';
            shell_exec($cmd);
            if (!file_exists($html)) {
                http_response_code(500);
                echo 'No fue posible generar la vista previa.';
                return;
            }
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex');
        readfile($html);
        exit;
    }

    /**
     * GET /notas-malas/config
     * Vista/JSON de la configuración de oficios
     */
    public function configuracion() {
        $rol = $_SESSION['rol'] ?? '';
        $config = (new ConfigOficio())->getConfig();
        if (!$config) {
            $this->jsonResponse(['success' => false, 'error' => 'No existe configuración de oficios'], 404);
        }

        if ($this->isAjax()) {
            $this->jsonResponse(['success' => true, 'data' => $config]);
        }

        $content = '
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col">
                    <h2><i class="fas fa-cog me-2 text-primary"></i>Configuración de Oficios</h2>
                    <p class="text-secondary">Parámetros del oficio "ATENTA NOTA" (folio, firma, membrete).</p>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="formConfigOficio">
                        <input type="hidden" name="_token" value="' . htmlspecialchars(Csrf::token()) . '">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prefijo de folio</label>
                                <input type="text" class="form-control" name="prefijo" value="' . htmlspecialchars($config['prefijo']) . '" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Año</label>
                                <input type="number" class="form-control" name="anio" value="' . (int)$config['anio'] . '" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Último folio usado</label>
                                <input type="number" class="form-control" name="ultimo_folio" value="' . (int)$config['ultimo_folio'] . '" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre del firmante</label>
                                <input type="text" class="form-control" name="nombre_firmante" value="' . htmlspecialchars($config['nombre_firmante']) . '" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cargo del firmante</label>
                                <input type="text" class="form-control" name="cargo_firmante" value="' . htmlspecialchars($config['cargo_firmante']) . '" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Iniciales</label>
                                <input type="text" class="form-control" name="iniciales" value="' . htmlspecialchars($config['iniciales']) . '">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ruta del membrete</label>
                                <input type="text" class="form-control" name="membrete_path" value="' . htmlspecialchars($config['membrete_path']) . '">
                            </div>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
        $(function() {
            $("#formConfigOficio").on("submit", function(e) {
                e.preventDefault();
                var datos = $(this).serializeArray();
                var body = {};
                datos.forEach(function(d) { body[d.name] = d.value; });
                $.ajax({
                    url: BASE_URL + "/notas-malas/config",
                    method: "POST",
                    data: JSON.stringify(body),
                    contentType: "application/json",
                    headers: { "X-CSRF-Token": document.getElementById("csrf_token")?.value || "" },
                    dataType: "json",
                    success: function(res) {
                        if (res.success) {
                            alert("Configuración guardada correctamente.");
                            location.reload();
                        } else {
                            alert("Error: " + (res.error || "No se pudo guardar."));
                        }
                    },
                    error: function(xhr) {
                        alert("Error al guardar: " + (xhr.responseJSON?.error || xhr.status));
                    }
                });
            });
        });
        </script>';

        include __DIR__ . '/../views/layout.php';
    }

    /**
     * POST /notas-malas/config
     * Actualiza la configuración de oficios
     */
    public function guardarConfiguracion() {
        if (!in_array($_SESSION['rol'] ?? '', ['superadmin', 'admin', 'rh'])) {
            $this->jsonResponse(['success' => false, 'error' => 'Sin permisos'], 403);
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                $input = $_POST;
            }
            $anio = (int)($input['anio'] ?? date('Y'));
            $guardado = (new ConfigOficio())->guardar($anio, $input);
            if (!$guardado) {
                $this->jsonResponse(['success' => false, 'error' => 'No se pudo actualizar la configuración'], 500);
            }
            $this->jsonResponse(['success' => true]);
        } catch (Exception $e) {
            $this->logException($e);
            $this->jsonResponse(['success' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    private function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
