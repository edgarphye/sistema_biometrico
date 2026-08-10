<?php
require_once __DIR__ . '/../../helpers/Csrf.php';
$csrfToken = Csrf::generateToken();
$_SESSION['csrf_token'] = $csrfToken;
?>
<style>
:root {
    --pantone-primary: #9F2241;
    --pantone-primary-dark: #691C32;
    --pantone-secondary: #235B4E;
    --pantone-accent: #DDC9A3;
}
.val-emp-header {
    background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
    border-radius: 16px;
    color: white;
}
.val-emp-card {
    border-radius: 12px;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}
.val-emp-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}
.val-emp-card.pendiente {
    border-left: 4px solid #FFC107;
}
.val-emp-card.requiere_info {
    border-left: 4px solid #FFA000;
    animation: pulse-border 2s infinite;
}
.val-emp-card.aprobado {
    border-left: 4px solid #2E7D32;
}
.val-emp-card.rechazado {
    border-left: 4px solid #C62828;
}
@keyframes pulse-border {
    0% { box-shadow: 0 0 0 0 rgba(255, 160, 0, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(255, 160, 0, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 160, 0, 0); }
}
.chat-container {
    max-height: 400px;
    overflow-y: auto;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 12px;
}
.chat-msg {
    max-width: 80%;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    position: relative;
}
.chat-msg.empleado {
    background: #e3f2fd;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}
.chat-msg.jefe {
    background: #fff3e0;
    margin-right: auto;
    border-bottom-left-radius: 4px;
}
.chat-msg.sistema {
    background: #f5f5f5;
    margin: 0 auto;
    text-align: center;
    font-style: italic;
    font-size: 0.85rem;
}
.chat-msg .time {
    font-size: 0.7rem;
    color: #999;
    margin-top: 0.25rem;
}
.chat-msg .author {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--pantone-primary);
    margin-bottom: 0.25rem;
}
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #999;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ddd;
}
</style>

<div class="container-fluid py-4">
    <input type="hidden" id="csrf_token" value="<?php echo $csrfToken; ?>">
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <script>
        window.BASE_URL = "<?php echo rtrim(BASE_URL, '/'); ?>";
        if (window.BASE_URL === '.' || window.BASE_URL === '') {
            const path = window.location.pathname;
            window.BASE_URL = path.split('/').length > 2 ? '/' + path.split('/')[1] : '';
        }
    </script>
    <script src="/assets/js/mis_validaciones.js?v=20260602"></script>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="val-emp-header p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h3 class="mb-1">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Mis Validaciones
                        </h3>
                        <small class="opacity-75">
                            <?php echo htmlspecialchars($empleado['nombre_completo'] ?? 'Empleado'); ?>
                            <?php if (!empty($empleado['area'])): ?>
                                - <?php echo htmlspecialchars($empleado['area']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                    <div>
                        <span class="badge bg-warning text-dark fs-6" id="contadorNoLeidas">
                            <i class="fas fa-bell me-1"></i>
                            <span id="noLeidasCount"><?php echo $contadorNoLeidas; ?></span> no leídas
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card val-emp-card">
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <a href="?estado=requiere_info" class="btn btn-warning btn-sm <?php echo ($filters['estado'] ?? '') === 'requiere_info' ? 'active' : ''; ?>">
                            <i class="fas fa-question-circle me-1"></i>Requieren Respuesta
                        </a>
                        <a href="?estado=pendientes" class="btn btn-outline-primary btn-sm <?php echo ($filters['estado'] ?? 'pendientes') === 'pendientes' ? 'active' : ''; ?>">
                            <i class="fas fa-clock me-1"></i>Pendientes
                        </a>
                        <a href="?estado=aprobado" class="btn btn-outline-success btn-sm <?php echo ($filters['estado'] ?? '') === 'aprobado' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle me-1"></i>Aprobados
                        </a>
                        <a href="?estado=rechazado" class="btn btn-outline-danger btn-sm <?php echo ($filters['estado'] ?? '') === 'rechazado' ? 'active' : ''; ?>">
                            <i class="fas fa-times-circle me-1"></i>Rechazados
                        </a>
                        <a href="?estado=todos" class="btn btn-outline-secondary btn-sm <?php echo ($filters['estado'] ?? '') === 'todos' ? 'active' : ''; ?>">
                            <i class="fas fa-list me-1"></i>Todos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de validaciones -->
    <div class="row" id="validacionesContainer">
        <?php if (empty($validaciones)): ?>
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h5 class="mt-3">No tienes validaciones pendientes</h5>
                    <p class="text-muted">Todas tus incidencias están al día</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($validaciones as $v): 
                $estado = $v['estado'] ?? 'pendiente';
                $requiereRespuesta = ($estado === 'requiere_info' && ($v['esperando_respuesta_de'] ?? '') === 'empleado');
                $cardClass = $requiereRespuesta ? 'requiere_info' : $estado;
            ?>
            <div class="col-md-6 mb-3">
                <div class="card val-emp-card <?php echo $cardClass; ?>" 
                     data-validacion-id="<?php echo $v['id']; ?>"
                     data-es-externa="<?php echo ($v['id'] < 0) ? '1' : '0'; ?>"
                     onclick="abrirConversacion(<?php echo $v['id']; ?>, <?php echo ($v['id'] < 0) ? 'true' : 'false'; ?>)"
                     style="cursor: pointer;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    <?php echo htmlspecialchars(ucfirst($v['tipo_incidencia'] ?? 'Incidencia')); ?>
                                    <small class="text-muted">#<?php echo $v['incidencia_id']; ?></small>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($v['fecha_incidencia'] ?? $v['fecha_solicitud'])); ?>
                                </small>
                            </div>
                            <div>
                                <?php if ($requiereRespuesta): ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-bell me-1"></i>Requiere respuesta
                                    </span>
                                <?php elseif ($estado === 'pendiente'): ?>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-clock me-1"></i>Pendiente
                                    </span>
                                <?php elseif ($estado === 'aprobado'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Aprobado
                                    </span>
                                <?php elseif ($estado === 'rechazado'): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times me-1"></i>Rechazado
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <p class="mb-2 small">
                            <?php echo htmlspecialchars($v['descripcion_incidencia'] ?? 'Sin descripción'); ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-user-tie me-1"></i>
                                Jefe: <?php echo htmlspecialchars($v['jefe_nombre'] ?? 'N/A'); ?>
                            </small>
                            <?php if (!empty($v['ultimo_mensaje_texto'])): ?>
                                <small class="text-primary">
                                    <i class="fas fa-comment-dots me-1"></i>
                                    Ver conversación
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($v['ultimo_mensaje_texto'])): ?>
                            <div class="mt-2 p-2 rounded bg-light">
                                <small class="text-muted">
                                    <strong><?php echo htmlspecialchars($v['ultimo_mensaje_remitente_nombre'] ?? ''); ?>:</strong>
                                    <?php echo htmlspecialchars(mb_substr($v['ultimo_mensaje_texto'], 0, 100)); ?>
                                    <?php if (mb_strlen($v['ultimo_mensaje_texto']) > 100): ?>...<?php endif; ?>
                                </small>
                                <br>
                                <small class="text-muted" style="font-size: 0.65rem;">
                                    <?php echo date('d/m/Y H:i', strtotime($v['ultimo_mensaje_fecha'])); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de conversación -->
<div class="modal fade" id="modalConversacion" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #9F2241 0%, #691C32 100%); color: white;">
                <div>
                    <h5 class="modal-title">
                        <i class="fas fa-comments me-2"></i>
                        Conversación - Validación
                    </h5>
                    <small class="opacity-75" id="modalValidacionInfo"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Estado de la validación -->
                <div class="mb-3 p-3 rounded bg-light" id="modalEstadoValidacion"></div>
                
                <!-- Chat -->
                <div class="chat-container mb-3" id="chatMensajes">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin me-2"></i>Cargando mensajes...
                    </div>
                </div>
                
                <!-- Formulario de respuesta -->
                <div class="card" id="formRespuesta">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                <i class="fas fa-reply me-1"></i>Responder:
                            </label>
                            <textarea class="form-control" id="mensajeRespuesta" rows="3" 
                                      placeholder="Escribe tu mensaje aquí..."></textarea>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Tu respuesta será notificada a tu jefe
                            </small>
                            <button type="button" class="btn btn-primary" id="btnEnviarRespuesta" disabled>
                                <i class="fas fa-paper-plane me-1"></i>Enviar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

