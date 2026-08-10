<?php
$pageTitle = 'Gestionar Dispositivos Biométricos';
$content = '
<style>
    :root {
        --pantone-primary: #9F2241;
        --pantone-primary-dark: #691C32;
        --pantone-secondary: #235B4E;
        --pantone-secondary-dark: #10312B;
        --pantone-accent: #DDC9A3;
        --pantone-accent-dark: #BC955C;
    }
    .gestionar-page {
        background-color: #f8f7f5;
        min-height: 100vh;
        padding: 2rem;
    }
    .gestionar-page .page-title {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        font-weight: 700;
        font-size: 1.8rem;
    }
    .device-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .device-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    }
    .device-card .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }
    .device-card .status-indicator.conectado { background: #28a745; }
    .device-card .status-indicator.desconectado { background: #dc3545; }
    .device-card .card-icon-device {
        font-size: 2.2rem;
        color: var(--pantone-primary);
        opacity: 0.7;
    }
    .btn-pantone-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 0.5rem 1.5rem;
    }
    .btn-pantone-primary:hover {
        opacity: 0.9;
        color: white;
    }
    .btn-pantone-outline {
        border: 2px solid var(--pantone-primary);
        color: var(--pantone-primary);
        border-radius: 10px;
        background: transparent;
    }
    .btn-pantone-outline:hover {
        background: var(--pantone-primary);
        color: white;
    }
    .empty-state {
        padding: 3rem;
        text-align: center;
        color: #98989A;
    }
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.4;
    }
</style>
<div class="gestionar-page">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title mb-0"><i class="fas fa-sliders-h me-2"></i> Gestionar Dispositivos</h1>
        <a href="' . BASE_URL . '/biometricos" class="btn btn-pantone-outline btn-sm">
            <i class="fas fa-chart-bar me-1"></i> Dashboard
        </a>
    </div>

    ' . (empty($dispositivos) ? '
    <div class="card device-card empty-state">
        <div>
            <i class="fas fa-server"></i>
            <h5 class="mt-3">No hay dispositivos biométricos registrados</h5>
            <p class="text-muted">Configure un dispositivo en el módulo de Dispositivos Biométricos.</p>
            <a href="' . BASE_URL . '/biometricos" class="btn btn-pantone-outline btn-sm">
                <i class="fas fa-arrow-right me-1"></i> Ir al Dashboard
            </a>
        </div>
    </div>
    ' : '') . '

    <div class="row g-4">
        ' . implode('', array_map(function($d) {
            $id = $d["dispositivo_id"] ?? $d["id"] ?? "?";
            $nombre = $d["nombre"] ?? "Dispositivo #$id";
            $status = $d["status"] ?? "desconectado";
            $type = $d["type"] ?? "N/A";
            $ip = $d["ip"] ?? "";
            $statsHtml = "";

            if (!empty($d["stats"])) {
                $statsHtml = "
                    <div class=\"mt-2 small text-muted\">
                        <span class=\"me-3\"><i class=\"fas fa-sign-in-alt\"></i> {$d["stats"]["entradas"]}</span>
                        <span class=\"me-3\"><i class=\"fas fa-sign-out-alt\"></i> {$d["stats"]["salidas"]}</span>
                        <span><i class=\"fas fa-fingerprint\"></i> {$d["stats"]["total"]}</span>
                    </div>";
            }

            return "
        <div class=\"col-md-6 col-lg-4\">
            <div class=\"card device-card\">
                <div class=\"card-body\">
                    <div class=\"d-flex justify-content-between align-items-start mb-3\">
                        <div class=\"d-flex align-items-center gap-3\">
                            <div class=\"card-icon-device\"><i class=\"fas fa-server\"></i></div>
                            <div>
                                <h5 class=\"mb-1\">$nombre</h5>
                                <p class=\"text-muted small mb-0\">
                                    <span class=\"status-indicator $status me-1\"></span>
                                    " . ucfirst($status) . "
                                    " . (!empty($ip) ? "&middot; $ip" : "") . "
                                    &middot; $type
                                </p>
                                $statsHtml
                            </div>
                        </div>
                        <span class=\"badge bg-secondary\">#$id</span>
                    </div>
                    <a href=\"" . BASE_URL . "/biometricos/gestionar/$id\" class=\"btn btn-pantone-primary w-100\">
                        <i class=\"fas fa-cog me-1\"></i> Gestionar
                    </a>
                </div>
            </div>
        </div>";
        }, $dispositivos ?? [])) . '
    </div>
</div>
';

include __DIR__ . '/../layout.php';
