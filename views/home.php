<?php
// Vista del Inicio - Presentación de Bienvenida de Recursos Humanos
ob_start();
?>
<style>
    .welcome-header {
        background: linear-gradient(135deg, #9F2241 0%, #691C32 50%, #4A1924 100%);
        color: white;
        padding: 60px 40px;
        border-radius: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(159, 34, 65, 0.3);
    }
    
    .welcome-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        animation: shimmer 3s infinite linear;
    }
    
    @keyframes shimmer {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .welcome-header h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
    }
    
    .welcome-header .subtitle {
        font-size: 1.4rem;
        opacity: 0.95;
        font-weight: 300;
        position: relative;
        z-index: 1;
    }
    
    .welcome-badge {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 12px 25px;
        border-radius: 50px;
        display: inline-block;
        margin-top: 25px;
        font-weight: 500;
        position: relative;
        z-index: 1;
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .info-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.08);
        border: none;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .info-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 60px rgba(0,0,0,0.12);
    }
    
    .info-card .icon-box {
        width: 70px;
        height: 70px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        margin-bottom: 20px;
    }
    
    .info-card h4 {
        font-weight: 600;
        color: #2D3436;
        margin-bottom: 12px;
    }
    
    .info-card p {
        color: #636E72;
        line-height: 1.7;
        margin-bottom: 0;
    }
    
    .section-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #2D3436;
        margin-bottom: 30px;
        position: relative;
        padding-bottom: 15px;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, #9F2241, #BC955C);
        border-radius: 2px;
    }
    
    .stat-card {
        background: linear-gradient(135deg, #F8F9FA 0%, #E9ECEF 100%);
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        border: none;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: scale(1.02);
    }
    
    .stat-card .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        color: #9F2241;
    }
    
    .stat-card .stat-label {
        font-size: 0.9rem;
        color: #636E72;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 5px;
    }
    
    .feature-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .feature-list li {
        padding: 15px 20px;
        border-bottom: 1px solid #E9ECEF;
        display: flex;
        align-items: center;
        transition: background 0.2s ease;
    }
    
    .feature-list li:last-child {
        border-bottom: none;
    }
    
    .feature-list li:hover {
        background: #F8F9FA;
    }
    
    .feature-list .feature-icon {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 18px;
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .goal-card {
        background: white;
        border-radius: 12px;
        padding: 25px;
        border-left: 4px solid #9F2241;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        margin-bottom: 20px;
        transition: transform 0.3s ease;
    }
    
    .goal-card:hover {
        transform: translateX(5px);
    }
    
    .goal-card h5 {
        font-weight: 700;
        color: #2D3436;
        margin-bottom: 10px;
        font-size: 1.1rem;
    }
    
    .goal-card p {
        color: #636E72;
        margin-bottom: 0;
        line-height: 1.6;
    }
    
    .org-badge {
        background: linear-gradient(135deg, #235B4E, #1a3d35);
        color: white;
        padding: 20px 30px;
        border-radius: 12px;
        display: inline-flex;
        align-items: center;
        gap: 15px;
    }
    
    .org-badge i {
        font-size: 2rem;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 40px;
        padding-bottom: 25px;
        border-left: 2px solid #E9ECEF;
    }
    
    .timeline-item:last-child {
        padding-bottom: 0;
        border-left: 2px solid transparent;
    }
    
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 5px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #9F2241;
        border: 3px solid white;
        box-shadow: 0 2px 8px rgba(159, 34, 65, 0.3);
    }
    
    .timeline-item h5 {
        font-weight: 600;
        color: #2D3436;
        margin-bottom: 5px;
    }
    
    .timeline-item p {
        color: #636E72;
        margin-bottom: 0;
    }
</style>

<div class="container-fluid py-4">
    <!-- Header de Bienvenida -->
    <div class="welcome-header mb-5">
        <h1><i class="fas fa-building me-3"></i>Departamento de Recursos Humanos</h1>
        <p class="subtitle">Sistema Biométrico de Control de Asistencia</p>
        <div class="welcome-badge">
            <i class="fas fa-fingerprint me-2"></i>Bienvenido al Sistema de Gestión de Personal
        </div>
    </div>

    <!-- Sobre el Sistema -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <h2 class="section-title">Sobre el Sistema</h2>
            <div class="info-card">
                <div class="icon-box" style="background: linear-gradient(135deg, #9F2241, #691C32); color: white;">
                    <i class="fas fa-info-circle"></i>
                </div>
                <h4>¿Qué es el Sistema Biométrico?</h4>
                <p>Es una plataforma integral para el control de asistencia del personal mediante tecnología biométrica de vanguardia, utilizando lectores de huella dactilar y reconocimiento facial para garantizar un registro preciso y seguro de las entradas y salidas de cada empleado.</p>
            </div>
        </div>
        <div class="col-lg-6">
            <h2 class="section-title">Misión</h2>
            <div class="info-card">
                <div class="icon-box" style="background: linear-gradient(135deg, #235B4E, #1a3d35); color: white;">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h4>Misión del Sistema</h4>
                <p>Proveer una herramienta tecnológica eficiente y confiable para el control automatizado de asistencia, que permita a Recursos Humanos gestionar de manera transparente los registros de entrada, salida, retardos y ausencias, generando reportes precisos para la toma de decisiones.</p>
            </div>
        </div>
    </div>

    <!-- Objetivos y Metas -->
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <h2 class="section-title">Objetivos</h2>
            <div class="goal-card">
                <h5><i class="fas fa-check-circle text-success me-2"></i>Automatización</h5>
                <p>Eliminar registros manuales y papelería mediante el registro automático biométrico de asistencia.</p>
            </div>
            <div class="goal-card">
                <h5><i class="fas fa-check-circle text-success me-2"></i>Transparencia</h5>
                <p>Garantizar registros exactos y verificables que permitan una evaluación objetiva del cumplimiento horario.</p>
            </div>
            <div class="goal-card">
                <h5><i class="fas fa-check-circle text-success me-2"></i>Eficiencia</h5>
                <p>Optimizar los procesos de cálculo de retardos, ausencias y comisiones para el área de nómina.</p>
            </div>
            <div class="goal-card">
                <h5><i class="fas fa-check-circle text-success me-2"></i>Control</h5>
                <p>Mantener un registro histórico completo para auditorías y seguimiento del personal.</p>
            </div>
        </div>
        <div class="col-lg-6">
            <h2 class="section-title">Metas</h2>
            <div class="info-card h-100">
                <ul class="feature-list">
                    <li>
                        <div class="feature-icon" style="background: #E8F5E9; color: #235B4E;">
                            <i class="fas fa-target"></i>
                        </div>
                        <div>
                            <strong>100% Digital</strong>
                            <p class="mb-0 small text-muted">Lograr registro 100% digital de todas las asistencia</p>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon" style="background: #E3F2FD; color: #1565C0;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <strong>Tiempo Real</strong>
                            <p class="mb-0 small text-muted">Monitoreo en tiempo real de asistencia del personal</p>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon" style="background: #FFF3E0; color: #E65100;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <strong>Reportes Precisos</strong>
                            <p class="mb-0 small text-muted">Generación automática de reportes para nómina</p>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon" style="background: #FCE4EC; color: #9F2241;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <strong>Seguridad Biométrica</strong>
                            <p class="mb-0 small text-muted">Garantizar identidad única de cada empleado</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Funcionalidades Principales -->
    <div class="row g-4 mb-5">
        <div class="col-12">
            <h2 class="section-title">Funcionalidades Principales</h2>
        </div>
        <div class="col-md-4">
            <div class="info-card h-100">
                <div class="icon-box" style="background: #E8F5E9; color: #235B4E;">
                    <i class="fas fa-users"></i>
                </div>
                <h4>Gestión de Empleados</h4>
                <p>Administración completa del personal: alta, modificación, baja, datos personales, área, puesto y jerarquía organizacional.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card h-100">
                <div class="icon-box" style="background: #E3F2FD; color: #1565C0;">
                    <i class="fas fa-fingerprint"></i>
                </div>
                <h4>Control Biométrico</h4>
                <p>Integración con dispositivos ZKTeco para registro mediante huella dactilar y reconocimiento facial con verificación automática de identidad.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card h-100">
                <div class="icon-box" style="background: #FFF3E0; color: #E65100;">
                    <i class="fas fa-clock"></i>
                </div>
                <h4>Control de Asistencia</h4>
                <p>Registro automático de entrada y salida con cálculo de retardos considerando tolerancia de 9 minutos y clasificación por tipo.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card h-100">
                <div class="icon-box" style="background: #FCE4EC; color: #9F2241;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h4>Gestión de Horarios</h4>
                <p>Definición de ciclos laborales, turnos, horarios por área y asignación flexible de horarios a empleados.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card h-100">
                <div class="icon-box" style="background: #F3E5F5; color: #7B1FA2;">
                    <i class="fas fa-file-excel"></i>
                </div>
                <h4>Reportes y Cálculos</h4>
                <p>Reportes detallados de retardos, comisiones, ausencias,-justificaciones y exportación a Excel para nómina.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-card h-100">
                <div class="icon-box" style="background: #E0F7FA; color: #00838F;">
                    <i class="fas fa-user-check"></i>
                </div>
                <h4>Validaciones</h4>
                <p>Sistema de validación de retardos por parte de jefe directo con flujo de aprobación y comentarios.</p>
            </div>
        </div>
    </div>

    <!-- Información Adicional -->
    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="section-title">Cómo Funciona</h2>
            <div class="info-card">
                <div class="timeline-item">
                    <h5><i class="fas fa-1 text-muted me-2"></i>Registro Biométrico</h5>
                    <p>El empleado registra su huella dactilar o rostro en el dispositivo biométrico al llegar.</p>
                </div>
                <div class="timeline-item">
                    <h5><i class="fas fa-2 text-muted me-2"></i>Verificación de Identidad</h5>
                    <p>El sistema verifica automáticamente la identidad comparando con la base de datos de empleados.</p>
                </div>
                <div class="timeline-item">
                    <h5><i class="fas fa-3 text-muted me-2"></i>Registro de Asistencia</h5>
                    <p>Se registra automáticamente hora de entrada, salida y se calcula si hay retardo.</p>
                </div>
                <div class="timeline-item">
                    <h5><i class="fas fa-4 text-muted me-2"></i>Generación de Reportes</h5>
                    <p>Recursos Humanos genera reportes para cálculo de nómina y seguimiento.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <h2 class="section-title">Contacto</h2>
            <div class="info-card">
                <div class="org-badge mb-4">
                    <i class="fas fa-building"></i>
                    <div>
                        <div class="fw-bold">Departamento de Recursos Humanos</div>
                        <small> Sistema de Control Biométrico</small>
                    </div>
                </div>
                <ul class="feature-list">
                    <li>
                        <div class="feature-icon" style="background: #E8F5E9; color: #235B4E;">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <strong>Correo Electrónico</strong>
                            <p class="mb-0 small text-muted">rh@empresa.com</p>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon" style="background: #E3F2FD; color: #1565C0;">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <strong>Teléfono</strong>
                            <p class="mb-0 small text-muted">Ext. 1234</p>
                        </div>
                    </li>
                    <li>
                        <div class="feature-icon" style="background: #FFF3E0; color: #E65100;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <strong>Horario de Atención</strong>
                            <p class="mb-0 small text-muted">Lunes a Viernes 9:00 - 18:00 hrs</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Footer Informativo -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="text-center py-4">
                <p class="text-muted mb-2">
                    <i class="fas fa-code me-2"></i>Sistema Biométrico de Control de Asistencia | Versión 1.0
                </p>
                <p class="text-muted small mb-0">
                    <strong>Desarrollado por Edgar Phye Parga</strong> - Ingeniero en Desarrollo de Software | &copy; <?php echo date('Y'); ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
