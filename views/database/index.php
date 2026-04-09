<div class="database-content" id="databaseContent">
    <div class="container-fluid db-container">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="db-title">
                <i class="fas fa-database me-2"></i>
                Gestión de Base de Datos
            </h2>
            <div class="d-flex gap-2">
                <button class="btn btn-pantone-outline" onclick="refreshStatus()">
                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                </button>
            </div>
        </div>

        <!-- Alertas -->
        <div id="alertSuccess" class="alert alert-pantone-success alert-dismissible fade show" style="display: none">
            <span id="alertSuccessMsg"></span>
            <button type="button" class="btn-close" onclick="hideAlert('alertSuccess')"></button>
        </div>
        <div id="alertDanger" class="alert alert-pantone-danger alert-dismissible fade show" style="display: none">
            <span id="alertDangerMsg"></span>
            <button type="button" class="btn-close" onclick="hideAlert('alertDanger')"></button>
        </div>
        <div id="alertWarning" class="alert alert-pantone-warning alert-dismissible fade show" style="display: none">
            <span id="alertWarningMsg"></span>
            <button type="button" class="btn-close" onclick="hideAlert('alertWarning')"></button>
        </div>
        <div id="alertInfo" class="alert alert-pantone-info alert-dismissible fade show" style="display: none">
            <span id="alertInfoMsg"></span>
            <button type="button" class="btn-close" onclick="hideAlert('alertInfo')"></button>
        </div>

        <!-- Estado del Sistema -->
        <div class="row mb-4 g-3">
            <div class="col-md-3 col-sm-6">
                <div class="card db-stat-card stat-primary">
                    <div class="card-body text-center">
                        <div class="stat-icon"><i class="fas fa-table"></i></div>
                        <div class="stat-number"><?= $stats['tables_count'] ?? 0 ?></div>
                        <div class="stat-label">Tablas</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card db-stat-card stat-success">
                    <div class="card-body text-center">
                        <div class="stat-icon"><i class="fas fa-save"></i></div>
                        <div class="stat-number"><?= $stats['backups_count'] ?? 0 ?></div>
                        <div class="stat-label">Backups</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card db-stat-card stat-info">
                    <div class="card-body text-center">
                        <div class="stat-icon"><i class="fas fa-link"></i></div>
                        <div class="stat-number"><?= $stats['foreign_keys_count'] ?? 0 ?></div>
                        <div class="stat-label">Relaciones FK</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card db-stat-card <?= ($stats['database_exists'] ?? false) ? 'stat-success' : 'stat-danger' ?>">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas <?= ($stats['database_exists'] ?? false) ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                        </div>
                        <div class="stat-number"><?= ($stats['database_exists'] ?? false) ? 'Conectada' : 'Sin conexión' ?></div>
                        <div class="stat-label">Estado BD</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs de navegación -->
        <ul class="nav nav-tabs db-tabs mb-4" id="dbTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup-panel" type="button" role="tab">
                    <i class="fas fa-save me-2"></i>Backup
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="restore-tab" data-bs-toggle="tab" data-bs-target="#restore-panel" type="button" role="tab">
                    <i class="fas fa-undo me-2"></i>Restaurar
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tables-tab" data-bs-toggle="tab" data-bs-target="#tables-panel" type="button" role="tab">
                    <i class="fas fa-table me-2"></i>Tablas
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="relations-tab" data-bs-toggle="tab" data-bs-target="#relations-panel" type="button" role="tab">
                    <i class="fas fa-link me-2"></i>Relaciones
                </button>
            </li>
        </ul>

        <!-- Contenido de tabs -->
        <div class="tab-content" id="dbTabsContent">
            <!-- Panel de Backup -->
            <div class="tab-pane fade show active" id="backup-panel" role="tabpanel">
                <div class="row">
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card db-card">
                            <div class="card-header db-card-header-primary">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-save me-2"></i>Crear Backup Completo
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="db-description">
                                    Crea una copia de seguridad completa de la base de datos incluyendo:
                                    estructura, datos, índices, llaves, relaciones, procedimientos almacenados y triggers.
                                </p>
                                <form id="backupForm" onsubmit="event.preventDefault(); createBackup();">
                                    <div class="mb-4">
                                        <label class="form-label db-label"><i class="fas fa-file-archive me-2"></i>Compresión</label>
                                        <div class="form-check db-radio">
                                            <input class="form-check-input" type="radio" name="compress" id="compressYes" value="1" checked>
                                            <label class="form-check-label" for="compressYes">
                                                <i class="fas fa-file-archive me-1"></i> Comprimir (GZIP)
                                            </label>
                                        </div>
                                        <div class="form-check db-radio">
                                            <input class="form-check-input" type="radio" name="compress" id="compressNo" value="0">
                                            <label class="form-check-label" for="compressNo">
                                                <i class="fas fa-file-code me-1"></i> Sin comprimir
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-pantone-primary" id="backupBtn">
                                        <i class="fas fa-save me-2"></i> Crear Backup
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-12 mb-4">
                        <div class="card db-card">
                            <div class="card-header db-card-header-info">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-info-circle me-2"></i>Información
                                </h5>
                            </div>
                            <div class="card-body">
                                <h6 class="db-subtitle">El backup incluye:</h6>
                                <ul class="db-list">
                                    <li><i class="fas fa-check-circle me-2"></i> Estructura de todas las tablas</li>
                                    <li><i class="fas fa-check-circle me-2"></i> Índices y llaves primarias</li>
                                    <li><i class="fas fa-check-circle me-2"></i> Relaciones (Foreign Keys)</li>
                                    <li><i class="fas fa-check-circle me-2"></i> Procedimientos almacenados</li>
                                    <li><i class="fas fa-check-circle me-2"></i> Triggers</li>
                                    <li><i class="fas fa-check-circle me-2"></i> Todos los datos</li>
                                </ul>
                                <hr class="db-divider">
                                <small class="db-location">
                                    <i class="fas fa-folder me-2"></i>
                                    Ubicación: <code>backups/database_sql/</code>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Backups Existentes -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card db-card">
                            <div class="card-header db-card-header-list">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Backups Existentes</h5>
                                <button class="btn btn-sm btn-pantone-outline" onclick="loadBackupList()">
                                    <i class="fas fa-sync me-1"></i> Actualizar
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 db-table" id="backupListTable">
                                        <thead class="db-table-header">
                                            <tr>
                                                <th><i class="fas fa-file-archive me-2"></i>Archivo</th>
                                                <th><i class="fas fa-folder me-2"></i>Ubicación</th>
                                                <th><i class="fas fa-weight-hanging me-2"></i>Tamaño</th>
                                                <th><i class="fas fa-calendar me-2"></i>Fecha</th>
                                                <th class="text-end"><i class="fas fa-cogs me-2"></i>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="backupListBody">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    <i class="fas fa-spinner fa-spin me-2"></i> Cargando...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Restaurar -->
            <div class="tab-pane fade" id="restore-panel" role="tabpanel">
                <div class="card db-card border-warning">
                    <div class="card-header db-card-header-warning">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>Restaurar Base de Datos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-pantone-warning">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <strong>Advertencia:</strong> Esta acción eliminará la base de datos actual y la reemplazará con el backup seleccionado.
                            Todos los datos actuales se perderán.
                        </div>
                        
                        <form id="restoreForm" onsubmit="event.preventDefault(); restoreDatabase();">
                            <div class="mb-3">
                                <label class="form-label db-label"><i class="fas fa-file-import me-2"></i>Seleccionar Backup</label>
                                <select class="form-select db-select" id="restoreFile" required>
                                    <option value="">-- Seleccionar archivo --</option>
                                    <?php if (!empty($backups)): ?>
                                        <?php foreach ($backups as $backup): ?>
                                            <option value="<?= htmlspecialchars($backup['filename']) ?>">
                                                <?= htmlspecialchars($backup['filename']) ?> 
                                                (<?= $backup['size_formatted'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input db-checkbox" id="confirmRestore" required>
                                <label class="form-check-label" for="confirmRestore">
                                    Confirmo que entiendo que esto eliminará todos los datos actuales
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-pantone-warning" id="restoreBtn">
                                <i class="fas fa-undo me-2"></i> Restaurar Base de Datos
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Panel de Tablas -->
            <div class="tab-pane fade" id="tables-panel" role="tabpanel">
                <div class="card db-card">
                    <div class="card-header db-card-header-primary d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-table me-2"></i>Tablas de la Base de Datos
                        </h5>
                        <button class="btn btn-sm btn-pantone-outline" onclick="loadTables()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped db-table" id="tablesTable">
                                <thead class="db-table-header">
                                    <tr>
                                        <th><i class="fas fa-table me-2"></i>Tabla</th>
                                        <th><i class="fas fa-list me-2"></i>Filas</th>
                                        <th><i class="fas fa-database me-2"></i>Datos</th>
                                        <th><i class="fas fa-key me-2"></i>Índices</th>
                                        <th><i class="fas fa-weight-hanging me-2"></i>Total</th>
                                        <th><i class="fas fa-cog me-2"></i>Motor</th>
                                        <th><i class="fas fa-font me-2"></i>Collation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($tables)): ?>
                                        <?php foreach ($tables as $table): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($table['name']) ?></strong></td>
                                                <td><?= number_format($table['rows']) ?></td>
                                                <td><?= $dbManager->formatBytes($table['data_length']) ?></td>
                                                <td><?= $dbManager->formatBytes($table['index_length']) ?></td>
                                                <td><strong><?= $dbManager->formatBytes($table['total_size']) ?></strong></td>
                                                <td><?= htmlspecialchars($table['engine']) ?></td>
                                                <td><small><?= htmlspecialchars($table['collation']) ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-5">
                                                <i class="fas fa-database fa-3x mb-3 d-block"></i>
                                                No hay tablas en la base de datos
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Relaciones -->
            <div class="tab-pane fade" id="relations-panel" role="tabpanel">
                <div class="card db-card">
                    <div class="card-header db-card-header-secondary d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-link me-2"></i>Relaciones de Base de Datos
                        </h5>
                        <button class="btn btn-sm btn-pantone-outline" onclick="loadForeignKeys()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label db-label"><i class="fas fa-filter me-2"></i>Filtrar por tabla</label>
                            <select class="form-select db-select" id="filterTable" onchange="filterForeignKeys()">
                                <option value="">Todas las tablas</option>
                                <?php if (!empty($tables)): ?>
                                    <?php foreach ($tables as $table): ?>
                                        <option value="<?= htmlspecialchars($table['name']) ?>">
                                            <?= htmlspecialchars($table['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm db-table" id="relationsTable">
                                <thead class="db-table-header">
                                    <tr>
                                        <th><i class="fas fa-table me-2"></i>Tabla</th>
                                        <th><i class="fas fa-columns me-2"></i>Columna</th>
                                        <th><i class="fas fa-link me-2"></i>Referencia</th>
                                        <th><i class="fas fa-share-alt me-2"></i>Tabla Ref.</th>
                                        <th><i class="fas fa-reply me-2"></i>Columna Ref.</th>
                                        <th><i class="fas fa-trash-alt me-2"></i>ON DELETE</th>
                                        <th><i class="fas fa-edit me-2"></i>ON UPDATE</th>
                                    </tr>
                                </thead>
                                <tbody id="relationsBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin me-2"></i>
                                            Cargando relaciones...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay" style="display: none;">
            <div class="spinner-pantone" role="status">
                <i class="fas fa-circle-notch fa-spin fa-3x"></i>
            </div>
            <p class="mt-3">Procesando...</p>
        </div>
    </div>
</div>

<style>
    :root {
        --pantone-primary: #9F2241;
        --pantone-primary-dark: #691C32;
        --pantone-secondary: #235B4E;
        --pantone-secondary-dark: #10312B;
        --pantone-accent: #DDC9A3;
        --pantone-accent-dark: #BC955C;
        --pantone-danger: #691C32;
        --pantone-gray: #98989A;
        --pantone-gray-dark: #6F7271;
    }
    
    .db-container {
        background-color: #f8f7f5;
        min-height: 100vh;
        padding: 20px;
    }
    
    .db-title {
        font-weight: 700;
        color: var(--pantone-primary);
        display: flex;
        align-items: center;
    }
    
    .db-title i {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    /* Botones */
    .btn-pantone-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-pantone-primary:hover {
        background: linear-gradient(135deg, var(--pantone-primary-dark) 0%, #501526 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(151, 34, 65, 0.4);
    }
    
    .btn-pantone-secondary {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-pantone-secondary:hover {
        background: linear-gradient(135deg, var(--pantone-secondary-dark) 0%, #0a211d 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(35, 91, 78, 0.4);
    }
    
    .btn-pantone-warning {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
        color: white;
        border: none;
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-pantone-warning:hover {
        background: linear-gradient(135deg, #8B6914 0%, #6a5210 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(188, 149, 92, 0.4);
    }
    
    .btn-pantone-outline {
        background: transparent;
        color: var(--pantone-primary);
        border: 2px solid var(--pantone-primary);
        border-radius: 25px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-pantone-outline:hover {
        background: var(--pantone-primary);
        color: white;
        transform: translateY(-2px);
    }
    
    /* Alerts */
    .alert-pantone-success {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
        border: none;
        border-radius: 12px;
    }
    
    .alert-pantone-danger {
        background: linear-gradient(135deg, var(--pantone-danger) 0%, #501526 100%);
        color: white;
        border: none;
        border-radius: 12px;
    }
    
    .alert-pantone-warning {
        background: linear-gradient(135deg, var(--pantone-accent) 0%, #c4a77d 100%);
        color: #333;
        border: none;
        border-radius: 12px;
        border-left: 4px solid var(--pantone-accent-dark);
    }
    
    .alert-pantone-info {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, #1a4a40 100%);
        color: white;
        border: none;
        border-radius: 12px;
    }
    
    /* Tarjetas de estadísticas */
    .db-stat-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }
    
    .db-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    }
    
    .db-stat-card .stat-icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    
    .db-stat-card .stat-number {
        font-size: 2rem;
        font-weight: 700;
    }
    
    .db-stat-card .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
    }
    
    .stat-primary {
        background: linear-gradient(135deg, #E8D5D9 0%, #C9A4AA 100%);
        color: #691C32;
    }
    
    .stat-primary .stat-icon { color: #9F2241; }
    .stat-primary .stat-number { color: #691C32; }
    .stat-primary .stat-label { color: #4a1424; }
    
    .stat-success {
        background: linear-gradient(135deg, #D5E5DE 0%, #B8CCBF 100%);
        color: #10312B;
    }
    
    .stat-success .stat-icon { color: #235B4E; }
    .stat-success .stat-number { color: #10312B; }
    .stat-success .stat-label { color: #10312B; }
    
    .stat-info {
        background: linear-gradient(135deg, #EDE4D3 0%, #DDC9A3 100%);
        color: #691C32;
    }
    
    .stat-info .stat-icon { color: #BC955C; }
    .stat-info .stat-number { color: #691C32; }
    .stat-info .stat-label { color: #4a1424; }
    
    .stat-danger {
        background: linear-gradient(135deg, #E8D5D5 0%, #CCB4B4 100%);
        color: #8E0000;
    }
    
    .stat-danger .stat-icon { color: #C62828; }
    .stat-danger .stat-number { color: #8E0000; }
    .stat-danger .stat-label { color: #691C32; }
    
    /* Cards */
    .db-card {
        border-radius: 16px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    
    .db-card-header-primary {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        color: white;
        padding: 15px 20px;
        border: none;
    }
    
    .db-card-header-secondary {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
        padding: 15px 20px;
        border: none;
    }
    
    .db-card-header-info {
        background: linear-gradient(135deg, var(--pantone-accent-dark) 0%, #8B6914 100%);
        color: white;
        padding: 15px 20px;
        border: none;
    }
    
    .db-card-header-warning {
        background: linear-gradient(135deg, var(--pantone-accent) 0%, #c4a77d 100%);
        color: #333;
        padding: 15px 20px;
        border: none;
    }
    
    .db-card-header-list {
        background: linear-gradient(135deg, #f8f7f5 0%, #e8e6e1 100%);
        color: var(--pantone-primary);
        padding: 15px 20px;
        border: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .db-description {
        color: var(--pantone-gray-dark);
        line-height: 1.6;
    }
    
    .db-subtitle {
        color: var(--pantone-primary);
        font-weight: 600;
        margin-bottom: 15px;
    }
    
    .db-list {
        list-style: none;
        padding: 0;
    }
    
    .db-list li {
        padding: 8px 0;
        color: var(--pantone-gray-dark);
    }
    
    .db-list li i {
        color: var(--pantone-secondary);
    }
    
    .db-divider {
        border-color: var(--pantone-accent);
        opacity: 0.5;
    }
    
    .db-location {
        color: var(--pantone-gray);
    }
    
    .db-location code {
        background: rgba(151, 34, 65, 0.1);
        color: var(--pantone-primary);
        padding: 3px 8px;
        border-radius: 5px;
    }
    
    /* Labels y Formularios */
    .db-label {
        color: var(--pantone-primary);
        font-weight: 600;
        margin-bottom: 10px;
    }
    
    .db-select {
        border-radius: 12px;
        border: 2px solid #e0e0e0;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }
    
    .db-select:focus {
        border-color: var(--pantone-primary);
        box-shadow: 0 0 0 4px rgba(151, 34, 65, 0.15);
    }
    
    .db-radio {
        padding: 10px 0;
    }
    
    .db-radio .form-check-input:checked {
        background-color: var(--pantone-primary);
        border-color: var(--pantone-primary);
    }
    
    .db-checkbox:checked {
        background-color: var(--pantone-secondary);
        border-color: var(--pantone-secondary);
    }
    
    /* Tabs */
    .db-tabs {
        background: linear-gradient(135deg, #f8f7f5 0%, #f0ede8 100%);
        border-bottom: 3px solid var(--pantone-primary);
        padding: 8px 8px 0 8px;
        border-radius: 10px 10px 0 0;
    }
    
    .db-tabs .nav-link {
        font-size: 0.9rem;
        font-weight: 600;
        padding: 12px 20px;
        border: none;
        border-radius: 10px 10px 0 0;
        color: var(--pantone-gray-dark);
        background: transparent;
        transition: all 0.3s ease;
    }
    
    .db-tabs .nav-link:hover {
        color: var(--pantone-primary);
        background: rgba(151, 34, 65, 0.08);
    }
    
    .db-tabs .nav-link.active {
        color: white;
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
        box-shadow: 0 -2px 10px rgba(151, 34, 65, 0.2);
    }
    
    /* Tablas */
    .db-table {
        margin-bottom: 0;
    }
    
    .db-table-header {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, var(--pantone-secondary-dark) 100%);
        color: white;
    }
    
    .db-table-header th {
        border: none;
        padding: 14px 12px;
        font-weight: 600;
    }
    
    .db-table td {
        padding: 12px;
        vertical-align: middle;
        border-bottom: 1px solid #e8e6e1;
    }
    
    .db-table tbody tr:hover {
        background-color: rgba(35, 91, 78, 0.05);
    }
    
    /* Loading */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .spinner-pantone {
        color: var(--pantone-accent);
    }
    
    .loading-overlay p {
        color: white;
        font-weight: 500;
    }
    
    /* Badges */
    .badge-pantone {
        background-color: var(--pantone-secondary);
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
    }
    
    /* ==================== TEMA OSCURO ==================== */
    body.dark-theme .db-container {
        background-color: #1a1a2e;
    }
    
    body.dark-theme .db-title {
        background: linear-gradient(135deg, var(--pantone-accent) 0%, var(--pantone-accent-dark) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    body.dark-theme .db-card {
        background-color: #252538;
        border: 1px solid #3a3a5c;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    
    body.dark-theme .db-card-header-list {
        background: linear-gradient(135deg, #1e1e32 0%, #1a1a2e 100%);
        border-bottom: 1px solid #3a3a5c;
    }
    
    body.dark-theme .db-description {
        color: #8a8aaa;
    }
    
    body.dark-theme .db-subtitle {
        color: var(--pantone-accent);
    }
    
    body.dark-theme .db-list li {
        color: #8a8aaa;
    }
    
    body.dark-theme .db-list li i {
        color: var(--pantone-secondary);
    }
    
    body.dark-theme .db-location {
        color: #6c6c8a;
    }
    
    body.dark-theme .db-location code {
        background: rgba(151, 34, 65, 0.2);
        color: var(--pantone-accent);
    }
    
    body.dark-theme .db-label {
        color: var(--pantone-accent);
    }
    
    body.dark-theme .db-select {
        background-color: #1a1a2e;
        border-color: #3a3a5c;
        color: #e0e0e0;
    }
    
    body.dark-theme .db-select:focus {
        border-color: var(--pantone-primary);
        box-shadow: 0 0 0 4px rgba(151, 34, 65, 0.2);
    }
    
    body.dark-theme .db-tabs {
        background: linear-gradient(180deg, #1e1e32 0%, #1a1a2e 100%);
        border-bottom-color: var(--pantone-primary);
    }
    
    body.dark-theme .db-tabs .nav-link {
        color: #8a8aaa;
    }
    
    body.dark-theme .db-tabs .nav-link:hover {
        color: var(--pantone-accent);
        background: rgba(151, 34, 65, 0.15);
    }
    
    body.dark-theme .db-tabs .nav-link.active {
        background: linear-gradient(135deg, var(--pantone-primary) 0%, var(--pantone-primary-dark) 100%);
    }
    
    body.dark-theme .db-table-header {
        background: linear-gradient(135deg, var(--pantone-secondary) 0%, #1a3d35 100%);
    }
    
    body.dark-theme .db-table td {
        background-color: #252538;
        color: #e0e0e0;
        border-color: #3a3a5c;
    }
    
    body.dark-theme .db-table tbody tr:hover td {
        background-color: #2d2d48;
    }
    
    body.dark-theme .form-check-label {
        color: #e0e0e0;
    }
    
    body.dark-theme .db-radio .form-check-input {
        background-color: #3a3a5c;
        border-color: #5a5a8c;
    }
    
    body.dark-theme .db-radio .form-check-input:checked {
        background-color: var(--pantone-primary);
        border-color: var(--pantone-primary);
    }
    
    /* Tarjetas de estadísticas - Tema Oscuro */
    body.dark-theme .stat-primary {
        background: linear-gradient(135deg, #2d1f22 0%, #1a1215 100%);
        color: #E8D5D9;
    }
    body.dark-theme .stat-primary .stat-icon { color: #C9A4AA; }
    body.dark-theme .stat-primary .stat-number { color: #E8D5D9; }
    body.dark-theme .stat-primary .stat-label { color: #C9A4AA; }
    
    body.dark-theme .stat-success {
        background: linear-gradient(135deg, #1a2e28 0%, #0f1a15 100%);
        color: #D5E5DE;
    }
    body.dark-theme .stat-success .stat-icon { color: #B8CCBF; }
    body.dark-theme .stat-success .stat-number { color: #D5E5DE; }
    body.dark-theme .stat-success .stat-label { color: #B8CCBF; }
    
    body.dark-theme .stat-info {
        background: linear-gradient(135deg, #2e2820 0%, #1a1812 100%);
        color: #EDE4D3;
    }
    body.dark-theme .stat-info .stat-icon { color: #DDC9A3; }
    body.dark-theme .stat-info .stat-number { color: #EDE4D3; }
    body.dark-theme .stat-info .stat-label { color: #DDC9A3; }
    
    body.dark-theme .stat-danger {
        background: linear-gradient(135deg, #2e1f1f 0%, #1a1212 100%);
        color: #E8D5D5;
    }
    body.dark-theme .stat-danger .stat-icon { color: #CCB4B4; }
    body.dark-theme .stat-danger .stat-number { color: #E8D5D5; }
    body.dark-theme .stat-danger .stat-label { color: #CCB4B4; }
</style>

<script>
// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    loadForeignKeys();
});

function showAlert(id, message, type = 'success') {
    const alert = document.getElementById(id);
    const msgSpan = document.getElementById(id + 'Msg');
    
    const typeClass = {
        'success': 'alert-pantone-success',
        'danger': 'alert-pantone-danger',
        'warning': 'alert-pantone-warning',
        'info': 'alert-pantone-info'
    };
    
    alert.className = `${typeClass[type]} alert-dismissible fade show`;
    msgSpan.textContent = message;
    alert.style.display = 'block';
    
    setTimeout(() => {
        alert.style.display = 'none';
    }, 10000);
}

function hideAlert(id) {
    document.getElementById(id).style.display = 'none';
}

function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function refreshStatus() {
    location.reload();
}

// Crear Backup
async function createBackup() {
    const btn = document.getElementById('backupBtn');
    const compress = document.querySelector('input[name="compress"]:checked').value;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';
    showLoading();
    
    try {
        const formData = new FormData();
        formData.append('compress', compress);
        
        // Agregar token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        const response = await fetch('<?= BASE_URL ?>/database/backup', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            const pathInfo = result.data.path ? ` | Ruta: ${result.data.path}` : '';
            showAlert('alertSuccess', `✅ ${result.message}: ${result.data.filename} (${result.data.size})${pathInfo}`);
            setTimeout(() => location.reload(), 2000);
        } else {
            showAlert('alertDanger', `❌ ${result.message}`, 'danger');
        }
    } catch (error) {
        showAlert('alertDanger', `❌ Error de conexión: ${error.message}`, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-1"></i> Crear Backup';
        hideLoading();
    }
}

// Restaurar Base de Datos
async function restoreDatabase() {
    const btn = document.getElementById('restoreBtn');
    const filename = document.getElementById('restoreFile').value;
    const confirm = document.getElementById('confirmRestore').checked;
    
    if (!filename) {
        showAlert('alertWarning', '⚠️ Seleccione un archivo de backup', 'warning');
        return;
    }
    
    const confirmChecked = document.getElementById('confirmRestore').checked;
    
    if (!confirmChecked) {
        showAlert('alertWarning', '⚠️ Debe confirmar que entiende que se eliminarán los datos', 'warning');
        return;
    }
    
    if (!window.confirm('¿Está completamente seguro de restaurar la base de datos? Todos los datos actuales se perderán.')) {
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Restaurando...';
    showLoading();
    
    try {
        const formData = new FormData();
        formData.append('filename', filename);
        formData.append('confirm', '1');
        
        // Agregar token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        const response = await fetch('<?= BASE_URL ?>/database/restore', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('alertSuccess', `✅ Restauración completada: ${result.data.tables_count} tablas, ${result.data.rows_count} filas`);
        } else {
            showAlert('alertDanger', `❌ ${result.message}`, 'danger');
        }
    } catch (error) {
        showAlert('alertDanger', `❌ Error de conexión: ${error.message}`, 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-undo me-1"></i> Restaurar Base de Datos';
        hideLoading();
    }

}

// Cargar lista de backups al iniciar
document.addEventListener('DOMContentLoaded', function() {
    loadBackupList();
});

// Cargar lista de backups
async function loadBackupList() {
    const tbody = document.getElementById('backupListBody');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i> Cargando...</td></tr>';
    
    try {
        const response = await fetch('<?= BASE_URL ?>/database/listBackups');
        const result = await response.json();
        
        if (result.success) {
            renderBackupList(result.data);
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">${result.message}</td></tr>`;
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-3">Error: ${error.message}</td></tr>`;
    }
}

function renderBackupList(backups) {
    const tbody = document.getElementById('backupListBody');
    
    if (!backups || backups.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No hay backups disponibles</td></tr>';
        return;
    }
    
    tbody.innerHTML = backups.map(backup => `
        <tr>
            <td><i class="fas fa-file-archive me-2" style="color: var(--pantone-primary);"></i><strong>${backup.filename}</strong></td>
            <td><small><code>${backup.location || '-'}</code></small></td>
            <td>${backup.size_formatted}</td>
            <td>${backup.created_formatted}</td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-danger" onclick="deleteBackupFile('${backup.filename}')" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Eliminar backup
async function deleteBackupFile(filename) {
    if (!window.confirm('¿Está seguro de eliminar el backup "' + filename + '"? Esta acción no se puede deshacer.')) {
        return;
    }
    
    console.log('Intentando eliminar:', filename);
    
    try {
        const formData = new FormData();
        formData.append('filename', filename);
        
        // Agregar token CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        
        const response = await fetch('<?= BASE_URL ?>/database/deleteBackup', {
            method: 'POST',
            body: formData
        });
        
        console.log('Response status:', response.status);
        
        const result = await response.json();
        console.log('Response result:', result);
        
        if (result.success) {
            showAlert('alertSuccess', '✅ ' + result.message);
            // Recargar lista inmediatamente
            await loadBackupList();
            await loadRestoreSelect();
        } else {
            console.error('Error al eliminar:', result);
            showAlert('alertDanger', '❌ ' + result.message, 'danger');
        }
    } catch (error) {
        console.error('Error exception:', error);
        showAlert('alertDanger', '❌ Error: ' + error.message, 'danger');
    }
}

// Cargar select de restaurar
async function loadRestoreSelect() {
    const select = document.getElementById('restoreFile');
    if (!select) return;
    
    select.innerHTML = '<option value="">-- Cargando... --</option>';
    
    try {
        const response = await fetch('<?= BASE_URL ?>/database/listBackups');
        const result = await response.json();
        
        if (result.success) {
            if (result.data.length === 0) {
                select.innerHTML = '<option value="">-- No hay backups disponibles --</option>';
            } else {
                select.innerHTML = '<option value="">-- Seleccionar archivo --</option>';
                result.data.forEach(backup => {
                    const option = document.createElement('option');
                    option.value = backup.filename;
                    option.textContent = `${backup.filename} (${backup.size_formatted})`;
                    select.appendChild(option);
                });
            }
        } else {
            select.innerHTML = '<option value="">-- Error al cargar --</option>';
        }
    } catch (error) {
        select.innerHTML = '<option value="">-- Error de conexión --</option>';
    }
}

// Cargar relaciones FK
async function loadForeignKeys() {
    const tbody = document.getElementById('relationsBody');
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4"><i class="fas fa-spinner fa-spin me-2"></i> Cargando...</td></tr>';
    
    try {
        const response = await fetch('<?= BASE_URL ?>/database/foreignKeys');
        const result = await response.json();
        
        if (result.success) {
            renderForeignKeys(result.data);
        } else {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${result.message}</td></tr>`;
        }
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">Error: ${error.message}</td></tr>`;
    }
}

function renderForeignKeys(data) {
    const tbody = document.getElementById('relationsBody');
    const filter = document.getElementById('filterTable').value;
    
    const filtered = filter ? data.filter(fk => fk.TABLE_NAME === filter) : data;
    
    if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay relaciones configuradas</td></tr>';
        return;
    }
    
    tbody.innerHTML = filtered.map(fk => `
        <tr>
            <td><strong>${fk.TABLE_NAME}</strong></td>
            <td><code>${fk.COLUMN_NAME}</code></td>
            <td><span class="badge-pantone">${fk.CONSTRAINT_NAME}</span></td>
            <td><strong>${fk.REFERENCED_TABLE_NAME}</strong></td>
            <td><code>${fk.REFERENCED_COLUMN_NAME}</code></td>
            <td><small>${fk.DELETE_RULE}</small></td>
            <td><small>${fk.UPDATE_RULE}</small></td>
        </tr>
    `).join('');
}

function filterForeignKeys() {
    loadForeignKeys();
}
</script>
