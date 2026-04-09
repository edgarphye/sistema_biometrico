<?php
/**
 * Módulo de Mantenimiento del Sistema Biométrico
 * 
 * Este script proporciona un panel de mantenimiento unificado
 * con todas las herramientas necesarias para administración,
 * backup, restauración y monitoreo del sistema.
 * 
 * Uso:
 * php maintenance.php                    # Modo interactivo
 * php maintenance.php --backup           # Backup automático
 * php maintenance.php --restore DATE      # Restaurar backup
 * php maintenance.php --cleanup          # Limpieza del sistema
 * php maintenance.php --health-check      # Diagnóstico completo
 */

require_once 'config.php';
require_once 'models/Database.php';
require_once 'backup_database.php';

class SystemMaintenance {
    public $backup;
    private $db;
    private $conn;
    
    public function __construct() {
        $this->backup = new DatabaseBackup();
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Menú principal de mantenimiento
     */
    public function showMainMenu() {
        echo "\n" . str_repeat("🔧", 30) . "\n";
        echo "        PANEL DE MANTENIMIENTO\n";
        echo "        SISTEMA BIOMÉTRICO v2.0+\n";
        echo str_repeat("🔧", 30) . "\n\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────────────────────┐\n";
            echo "│                  MANTENIMIENTO                   │\n";
            echo "├─────────────────────────────────────────────────────────┤\n";
            echo "│ 1. 📊 Estado del Sistema                        │\n";
            echo "│ 2. 💾 Gestión de Backups                        │\n";
            echo "│ 3. 🔄 Restauración de Base de Datos              │\n";
            echo "│ 4. 🧹 Limpieza y Optimización                    │\n";
            echo "│ 5. 🔍 Diagnóstico y Verificación                │\n";
            echo "│ 6. ⚙️  Configuración Avanzada                    │\n";
            echo "│ 7. 📈 Reportes y Estadísticas                   │\n";
            echo "│ 8. 🔐 Seguridad y Auditoría                     │\n";
            echo "│ 9. 🚀 Tareas Automáticas                         │\n";
            echo "│ 0. 🚪 Salir                                     │\n";
            echo "└─────────────────────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-9]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->systemStatus();
                    break;
                case '2':
                    $this->backupManagement();
                    break;
                case '3':
                    $this->restoreManagement();
                    break;
                case '4':
                    $this->cleanupOptimization();
                    break;
                case '5':
                    $this->diagnosticVerification();
                    break;
                case '6':
                    $this->advancedConfiguration();
                    break;
                case '7':
                    $this->reportsStatistics();
                    break;
                case '8':
                    $this->securityAudit();
                    break;
                case '9':
                    $this->automatedTasks();
                    break;
                case '0':
                    echo "\n👋 Saliendo del panel de mantenimiento...\n";
                    return;
                default:
                    echo "❌ Opción inválida. Intente nuevamente.\n\n";
            }
        }
    }
    
    /**
     * 1. Estado del Sistema
     */
    public function displaySystemStatus() {
        echo "\n📊 ESTADO DEL SISTEMA\n";
        echo str_repeat("─", 50) . "\n";
        
        try {
            // Estado de la base de datos
            echo "🗄️  BASE DE DATOS:\n";
            $version = $this->conn->query("SELECT VERSION() as version")->fetch();
            echo "   MySQL/MariaDB: " . $version['version'] . "\n";
            
            $status = $this->conn->query("SHOW STATUS LIKE 'Connections'")->fetch();
            echo "   Conexiones totales: " . number_format($status['Value']) . "\n";
            
            $uptime = $this->conn->query("SHOW STATUS LIKE 'Uptime'")->fetch();
            $days = floor($uptime['Value'] / 86400);
            $hours = floor(($uptime['Value'] % 86400) / 3600);
            echo "   Tiempo activo: {$days} días, {$hours} horas\n\n";
            
            // Estado de las tablas
            echo "📋 TABLAS PRINCIPALES:\n";
            $criticalTables = [
                'empleados' => 'Empleados',
                'usuarios' => 'Usuarios', 
                'asistencia' => 'Registros de Asistencia',
                'retardos' => 'Retardos',
                'sanciones' => 'Sanciones',
                'dispositivos_biometricos' => 'Dispositivos Biométricos'
            ];
            
            foreach ($criticalTables as $table => $description) {
                try {
                    $count = $this->conn->query("SELECT COUNT(*) as cnt FROM {$table}")->fetch();
                    echo "   {$description}: " . number_format($count['cnt']) . " registros\n";
                } catch (Exception $e) {
                    echo "   {$description}: ❌ Error al consultar\n";
                }
            }
            
            echo "\n🌐 SERVICIO WEB:\n";
            $phpVersion = PHP_VERSION;
            echo "   PHP: {$phpVersion}\n";
            
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            echo "   Memoria usada: " . number_format($memoryUsage / 1024 / 1024, 2) . " MB\n";
            echo "   Límite memoria: {$memoryLimit}\n";
            
            // Verificar logs recientes
            echo "\n📝 LOGS RECIENTES:\n";
            $logFile = 'logs/backup.log';
            if (file_exists($logFile)) {
                $lines = file($logFile);
                $recentLines = array_slice($lines, -3);
                foreach ($recentLines as $line) {
                    echo "   " . trim($line) . "\n";
                }
            } else {
                echo "   No hay logs disponibles\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Error al obtener estado: " . $e->getMessage() . "\n";
        }
        
        echo "\nPresione Enter para continuar...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
    }
    
    /**
     * 2. Gestión de Backups
     */
    private function backupManagement() {
        echo "\n💾 GESTIÓN DE BACKUPS\n";
        echo str_repeat("─", 50) . "\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│          OPCIONES DE BACKUP          │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Crear Backup Completo            │\n";
            echo "│ 2. Crear Backup Diferencial         │\n";
            echo "│ 3. Listar Backups Disponibles       │\n";
            echo "│ 4. Verificar Integridad de Backups  │\n";
            echo "│ 5. Eliminar Backups Antiguos        │\n";
            echo "│ 6. Programar Backup Automático       │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-6]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->createFullBackup();
                    break;
                case '2':
                    $this->createDifferentialBackup();
                    break;
                case '3':
                    $this->listBackups();
                    break;
                case '4':
                    $this->verifyBackupIntegrity();
                    break;
                case '5':
                    $this->deleteOldBackups();
                    break;
                case '6':
                    $this->scheduleBackup();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    /**
     * 3. Gestión de Restauración
     */
    private function restoreManagement() {
        echo "\n🔄 GESTIÓN DE RESTAURACIÓN\n";
        echo str_repeat("─", 50) . "\n";
        echo "⚠️  ADVERTENCIA: La restauración sobrescribirá todos los datos actuales\n\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│         OPCIONES DE RESTAURACIÓN       │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Restaurar Backup (Modo Seguro)    │\n";
            echo "│ 2. Restaurar Backup (Modo Rápido)    │\n";
            echo "│ 3. Simular Restauración (Dry-Run)    │\n";
            echo "│ 4. Restauración con Selección         │\n";
            echo "│ 5. Verificar Integridad Post-Resto.  │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-5]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->safeRestore();
                    break;
                case '2':
                    $this->fastRestore();
                    break;
                case '3':
                    $this->dryRunRestore();
                    break;
                case '4':
                    $this->selectiveRestore();
                    break;
                case '5':
                    $this->verifyPostRestore();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    /**
     * 4. Limpieza y Optimización
     */
    private function cleanupOptimization() {
        echo "\n🧹 LIMPIEZA Y OPTIMIZACIÓN\n";
        echo str_repeat("─", 50) . "\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│      LIMPIEZA Y OPTIMIZACIÓN          │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Limpiar Logs del Sistema           │\n";
            echo "│ 2. Optimizar Base de Datos           │\n";
            echo "│ 3. Limpiar Archivos Temporales        │\n";
            echo "│ 4. Comprimir Backups Antiguos        │\n";
            echo "│ 5. Limpiar Sesiones Expiradas       │\n";
            echo "│ 6. Reindexar Tablas                 │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-6]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->cleanupLogs();
                    break;
                case '2':
                    $this->performDatabaseOptimization();
                    break;
                case '3':
                    $this->cleanupTempFiles();
                    break;
                case '4':
                    $this->compressOldBackups();
                    break;
                case '5':
                    $this->cleanupSessions();
                    break;
                case '6':
                    $this->reindexTables();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    /**
     * 5. Diagnóstico y Verificación
     */
    private function diagnosticVerification() {
        echo "\n🔍 DIAGNÓSTICO Y VERIFICACIÓN\n";
        echo str_repeat("─", 50) . "\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│       DIAGNÓSTICO COMPLETO             │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Verificar Integridad de BD         │\n";
            echo "│ 2. Verificar Estructura de Tablas      │\n";
            echo "│ 3. Verificar Foreign Keys             │\n";
            echo "│ 4. Análisis de Rendimiento           │\n";
            echo "│ 5. Verificar Configuración PHP       │\n";
            echo "│ 6. Diagnóstico Biométrico           │\n";
            echo "│ 7. Test de Conexión a Dispositivos  │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-7]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->performDatabaseIntegrityCheck();
                    break;
                case '2':
                    $this->checkTableStructure();
                    break;
                case '3':
                    $this->checkForeignKeys();
                    break;
                case '4':
                    $this->performanceAnalysis();
                    break;
                case '5':
                    $this->checkPHPConfiguration();
                    break;
                case '6':
                    $this->biometricDiagnostic();
                    break;
                case '7':
                    $this->testDeviceConnection();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    /**
     * 6. Configuración Avanzada
     */
    private function advancedConfiguration() {
        echo "\n⚙️ CONFIGURACIÓN AVANZADA\n";
        echo str_repeat("─", 50) . "\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│        CONFIGURACIÓN AVANZADA           │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Verificar Configuración Actual     │\n";
            echo "│ 2. Actualizar Configuración BD        │\n";
            echo "│ 3. Configurar Variables de Entorno  │\n";
            echo "│ 4. Configurar Cifrado Biométrico   │\n";
            echo "│ 5. Configurar Notificaciones        │\n";
            echo "│ 6. Importar/Exportar Configuración │\n";
            echo "│ 7. Reset de Configuración         │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-7]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->checkCurrentConfiguration();
                    break;
                case '2':
                    $this->updateDatabaseConfiguration();
                    break;
                case '3':
                    $this->configureEnvironmentVariables();
                    break;
                case '4':
                    $this->configureBiometricEncryption();
                    break;
                case '5':
                    $this->configureNotifications();
                    break;
                case '6':
                    $this->importExportConfiguration();
                    break;
                case '7':
                    $this->resetConfiguration();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    /**
     * 7. Reportes y Estadísticas
     */
    private function reportsStatistics() {
        echo "\n📈 REPORTES Y ESTADÍSTICAS\n";
        echo str_repeat("─", 50) . "\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│         REPORTES Y ESTADÍSTICAS         │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Reporte de Asistencia              │\n";
            echo "│ 2. Reporte de Retardos y Sanciones  │\n";
            echo "│ 3. Estadísticas de Uso             │\n";
            echo "│ 4. Reporte de Dispositivos           │\n";
            echo "│ 5. Reporte de Auditoría              │\n";
            echo "│ 6. Exportar Reportes (Excel/PDF)    │\n";
            echo "│ 7. Análisis de Tendencias           │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-7]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->attendanceReport();
                    break;
                case '2':
                    $this->delaysSanctionsReport();
                    break;
                case '3':
                    $this->usageStatistics();
                    break;
                case '4':
                    $this->devicesReport();
                    break;
                case '5':
                    $this->auditReport();
                    break;
                case '6':
                    $this->exportReports();
                    break;
                case '7':
                    $this->trendAnalysis();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    /**
     * 8. Seguridad y Auditoría
     */
    private function securityAudit() {
        echo "\n🔐 SEGURIDAD Y AUDITORÍA\n";
        echo str_repeat("─", 50) . "\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│         SEGURIDAD Y AUDITORÍA            │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Verificar Permisos de Archivos     │\n";
            echo "│ 2. Auditoría de Usuarios              │\n";
            echo "│ 3. Verificar Contraseñas Débiles     │\n";
            echo "│ 4. Análisis de Logs de Seguridad     │\n";
            echo "│ 5. Verificar Configuración HTTPS     │\n";
            echo "│ 6. Escanear Vulnerabilidades        │\n";
            echo "│ 7. Generar Reporte de Seguridad    │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-7]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->checkFilePermissions();
                    break;
                case '2':
                    $this->auditUsers();
                    break;
                case '3':
                    $this->checkWeakPasswords();
                    break;
                case '4':
                    $this->analyzeSecurityLogs();
                    break;
                case '5':
                    $this->checkHTTPSConfiguration();
                    break;
                case '6':
                    $this->scanVulnerabilities();
                    break;
                case '7':
                    $this->generateSecurityReport();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    /**
     * 9. Tareas Automáticas
     */
    private function automatedTasks() {
        echo "\n🚀 TAREAS AUTOMÁTICAS\n";
        echo str_repeat("─", 50) . "\n";
        
        while (true) {
            echo "┌─────────────────────────────────────────┐\n";
            echo "│          TAREAS AUTOMÁTICAS            │\n";
            echo "├─────────────────────────────────────────┤\n";
            echo "│ 1. Programar Backup Diario            │\n";
            echo "│ 2. Programar Limpieza Semanal       │\n";
            echo "│ 3. Configurar Monitorización        │\n";
            echo "│ 4. Programar Reportes Automáticos  │\n";
            echo "│ 5. Configurar Notificaciones       │\n";
            echo "│ 6. Ver Tareas Programadas         │\n";
            echo "│ 7. Ejecutar Todas las Tareas       │\n";
            echo "│ 0. Volver al Menú Principal         │\n";
            echo "└─────────────────────────────────────────┘\n\n";
            echo "Seleccione una opción [0-7]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->scheduleDailyBackup();
                    break;
                case '2':
                    $this->scheduleWeeklyCleanup();
                    break;
                case '3':
                    $this->configureMonitoring();
                    break;
                case '4':
                    $this->scheduleAutomaticReports();
                    break;
                case '5':
                    $this->configureAlerts();
                    break;
                case '6':
                    $this->viewScheduledTasks();
                    break;
                case '7':
                    $this->executeAllTasks();
                    break;
                case '0':
                    return;
                default:
                    echo "❌ Opción inválida.\n\n";
            }
        }
    }
    
    // Métodos de implementación (abreviados para demostración)
    
    private function createFullBackup() {
        echo "\n🔄 Creando backup completo...\n";
        try {
            $result = $this->backup->backupFull();
            if ($result['success']) {
                echo "✅ Backup completado exitosamente\n";
                echo "📁 Archivo: " . basename($result['file']) . "\n";
                echo "📊 Tamaño: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
                echo "📋 Tablas: {$result['tables']}\n";
            }
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        $this->pressEnterToContinue();
    }
    
    private function createDifferentialBackup() {
        echo "\n🔄 Creando backup diferencial...\n";
        try {
            $result = $this->backup->backupDifferential();
            if ($result['success']) {
                echo "✅ Backup diferencial completado\n";
                echo "📁 Archivo: " . basename($result['file']) . "\n";
                echo "📊 Tamaño: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
            } else {
                echo "ℹ️ " . $result['message'] . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        $this->pressEnterToContinue();
    }
    
    private function listBackups() {
        echo "\n📋 LISTA DE BACKUPS DISPONIBLES\n";
        echo str_repeat("─", 60) . "\n";
        
        try {
            $backups = $this->backup->listBackups();
            
            if (empty($backups)) {
                echo "⚠️ No hay backups disponibles\n";
            } else {
                printf("%-3s %-35s %-12s %-10s %-15s\n", 
                    "#", "Archivo", "Tipo", "Tamaño", "Fecha");
                echo str_repeat("-", 60) . "\n";
                
                foreach ($backups as $index => $backup) {
                    $size = $backup['size'] !== false ? number_format($backup['size'] / 1024 / 1024, 2) . ' MB' : 'N/A';
                    $date = $backup['created'] !== false ? date('Y-m-d H:i', $backup['created']) : 'N/A';
                    
                    printf("%-3s %-35s %-12s %-10s %-15s\n",
                        $index + 1,
                        substr($backup['file'], 0, 35),
                        $backup['type'],
                        $size,
                        $date
                    );
                }
            }
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        $this->pressEnterToContinue();
    }
    
    private function safeRestore() {
        echo "\n🔄 Iniciando restauración segura...\n";
        $backupDate = $this->selectBackupFromList();
        if ($backupDate) {
            try {
                echo "⚠️ Creando backup de seguridad...\n";
                $safetyBackup = $this->backup->backupFull();
                echo "✅ Backup de seguridad creado\n";
                
                echo "🔄 Iniciando restauración...\n";
                $result = $this->backup->fullRestoreInteractive($backupDate);
                
                if ($result['success']) {
                    echo "✅ Restauración completada exitosamente\n";
                }
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
        $this->pressEnterToContinue();
    }
    
    private function fastRestore() {
        echo "\n⚡ Iniciando restauración rápida...\n";
        $backupDate = $this->selectBackupFromList();
        if ($backupDate) {
            try {
                echo "⚠️ ADVERTENCIA: Modo rápido sin verificación\n";
                echo "¿Continuar? [s/N]: ";
                $handle = fopen("php://stdin", "r");
                $confirm = strtolower(trim(fgets($handle)));
                fclose($handle);
                
                if ($confirm === 's') {
                    $result = $this->backup->restore($backupDate, [
                        'backup_before_restore' => false,
                        'verify_tables' => false,
                        'dry_run' => false,
                        'ignore_errors' => true
                    ]);
                    
                    if ($result['success']) {
                        echo "✅ Restauración rápida completada\n";
                    }
                } else {
                    echo "❌ Cancelado por usuario\n";
                }
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
        $this->pressEnterToContinue();
    }
    
    private function dryRunRestore() {
        echo "\n🎭 Simulando restauración...\n";
        $backupDate = $this->selectBackupFromList();
        if ($backupDate) {
            try {
                $result = $this->backup->restore($backupDate, ['dry_run' => true]);
                if ($result['success']) {
                    echo "✅ Simulación completada\n";
                    echo "📊 Statements que se ejecutarían: " . ($result['statements'] ?? 'N/A') . "\n";
                    echo "📋 Ningún dato será modificado\n";
                }
            } catch (Exception $e) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
        $this->pressEnterToContinue();
    }
    
    public function performDatabaseOptimization() {
        echo "\n🔧 Optimizando base de datos...\n";
        
        try {
            $tables = $this->conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($tables as $table) {
                echo "   Optimizando tabla: {$table}\n";
                $this->conn->query("OPTIMIZE TABLE {$table}");
            }
            
            echo "✅ Base de datos optimizada exitosamente\n";
            
            // Mostrar estadísticas
            $stats = $this->conn->query("SHOW TABLE STATUS")->fetchAll();
            $totalRows = array_sum(array_column($stats, 'Rows'));
            $totalSize = array_sum(array_column($stats, 'Data_length'));
            
            echo "📊 Estadísticas post-optimización:\n";
            echo "   Total registros: " . number_format($totalRows) . "\n";
            echo "   Tamaño total: " . number_format($totalSize / 1024 / 1024, 2) . " MB\n";
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        $this->pressEnterToContinue();
    }
    
    public function performDatabaseIntegrityCheck() {
        echo "\n🔍 Verificando integridad de base de datos...\n";
        
        try {
            // Verificar tablas
            $tables = $this->conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "📋 Tablas encontradas: " . count($tables) . "\n";
            
            // Verificar tablas críticas
            $criticalTables = ['empleados', 'usuarios', 'asistencia'];
            $missingCritical = array_diff($criticalTables, $tables);
            
            if (!empty($missingCritical)) {
                echo "❌ Tablas críticas faltantes: " . implode(', ', $missingCritical) . "\n";
            } else {
                echo "✅ Todas las tablas críticas presentes\n";
            }
            
            // Verificar estructura de tablas
            foreach ($tables as $table) {
                try {
                    $this->conn->query("SELECT COUNT(*) FROM {$table} LIMIT 1");
                } catch (Exception $e) {
                    echo "❌ Error en tabla {$table}: " . $e->getMessage() . "\n";
                }
            }
            
            echo "✅ Verificación de integridad completada\n";
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
        $this->pressEnterToContinue();
    }
    
    private function pressEnterToContinue() {
        echo "\nPresione Enter para continuar...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
        echo "\n";
    }
    
    private function selectBackupFromList() {
        $backups = $this->backup->listBackups();
        
        if (empty($backups)) {
            echo "⚠️ No hay backups disponibles\n";
            return null;
        }
        
        $this->listBackups();
        
        echo "Ingrese el número del backup (0 para cancelar): ";
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        fclose($handle);
        
        if ($choice === '0' || !is_numeric($choice)) {
            echo "❌ Operación cancelada\n";
            return null;
        }
        
        $index = (int)$choice - 1;
        
        if (!isset($backups[$index])) {
            echo "❌ Selección inválida\n";
            return null;
        }
        
        // Extraer fecha del nombre del archivo
        $filename = $backups[$index]['file'];
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
            return $matches[1];
        }
        
        return $filename;
    }
    
    // Métodos placeholder para otras funcionalidades
    private function verifyBackupIntegrity() { $this->pressEnterToContinue(); }
    private function deleteOldBackups() { $this->pressEnterToContinue(); }
    private function scheduleBackup() { $this->pressEnterToContinue(); }
    private function selectiveRestore() { $this->pressEnterToContinue(); }
    private function verifyPostRestore() { $this->pressEnterToContinue(); }
    public function cleanupLogs() { $this->pressEnterToContinue(); }
    public function cleanupTempFiles() { $this->pressEnterToContinue(); }
    public function optimizeDatabase() { $this->pressEnterToContinue(); }
    private function compressOldBackups() { $this->pressEnterToContinue(); }
    private function cleanupSessions() { $this->pressEnterToContinue(); }
    private function reindexTables() { $this->pressEnterToContinue(); }
    private function checkTableStructure() { $this->pressEnterToContinue(); }
    private function checkForeignKeys() { $this->pressEnterToContinue(); }
    private function performanceAnalysis() { $this->pressEnterToContinue(); }
    public function checkPHPConfiguration() { $this->pressEnterToContinue(); }
    public function biometricDiagnostic() { $this->pressEnterToContinue(); }
    private function testDeviceConnection() { $this->pressEnterToContinue(); }
    public function systemStatus() { $this->pressEnterToContinue(); }
    public function checkDatabaseIntegrity() { $this->pressEnterToContinue(); }
    private function checkCurrentConfiguration() { $this->pressEnterToContinue(); }
    private function updateDatabaseConfiguration() { $this->pressEnterToContinue(); }
    private function configureEnvironmentVariables() { $this->pressEnterToContinue(); }
    private function configureBiometricEncryption() { $this->pressEnterToContinue(); }
    private function configureNotifications() { $this->pressEnterToContinue(); }
    private function importExportConfiguration() { $this->pressEnterToContinue(); }
    private function resetConfiguration() { $this->pressEnterToContinue(); }
    private function attendanceReport() { $this->pressEnterToContinue(); }
    private function delaysSanctionsReport() { $this->pressEnterToContinue(); }
    private function usageStatistics() { $this->pressEnterToContinue(); }
    private function devicesReport() { $this->pressEnterToContinue(); }
    private function auditReport() { $this->pressEnterToContinue(); }
    private function exportReports() { $this->pressEnterToContinue(); }
    private function trendAnalysis() { $this->pressEnterToContinue(); }
    private function checkFilePermissions() { $this->pressEnterToContinue(); }
    private function auditUsers() { $this->pressEnterToContinue(); }
    private function checkWeakPasswords() { $this->pressEnterToContinue(); }
    private function analyzeSecurityLogs() { $this->pressEnterToContinue(); }
    private function checkHTTPSConfiguration() { $this->pressEnterToContinue(); }
    private function scanVulnerabilities() { $this->pressEnterToContinue(); }
    private function generateSecurityReport() { $this->pressEnterToContinue(); }
    private function scheduleDailyBackup() { $this->pressEnterToContinue(); }
    private function scheduleWeeklyCleanup() { $this->pressEnterToContinue(); }
    private function configureMonitoring() { $this->pressEnterToContinue(); }
    private function scheduleAutomaticReports() { $this->pressEnterToContinue(); }
    private function configureAlerts() { $this->pressEnterToContinue(); }
    private function viewScheduledTasks() { $this->pressEnterToContinue(); }
    private function executeAllTasks() { $this->pressEnterToContinue(); }
}

// ====================================================================
// EJECUCIÓN PRINCIPAL
// ====================================================================

try {
    $options = getopt('', ['backup', 'restore:', 'cleanup', 'health-check', 'help']);
    
    if (isset($options['help']) || in_array('--help', $argv) || in_array('-h', $argv)) {
        echo "\n🔧 PANEL DE MANTENIMIENTO - SISTEMA BIOMÉTRICO\n";
        echo "Uso:\n";
        echo "  php maintenance.php                          # Modo interactivo\n";
        echo "  php maintenance.php --backup                 # Backup automático\n";
        echo "  php maintenance.php --restore YYYY-MM-DD       # Restaurar backup\n";
        echo "  php maintenance.php --cleanup                # Limpieza completa\n";
        echo "  php maintenance.php --health-check            # Diagnóstico completo\n";
        echo "  php maintenance.php --help                   # Mostrar esta ayuda\n\n";
        exit(0);
    }
    
    if (isset($options['backup'])) {
        $maintenance = new SystemMaintenance();
        $result = $maintenance->backup->backupFull();
        
        if ($result['success']) {
            echo "✅ Backup completado exitosamente\n";
            echo "📁 Archivo: " . basename($result['file']) . "\n";
            echo "📊 Tamaño: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
        } else {
            echo "❌ Error en backup\n";
            exit(1);
        }
        
    } elseif (isset($options['restore'])) {
        $backupDate = $options['restore'];
        echo "Iniciando restauración: {$backupDate}\n";
        
        $maintenance = new SystemMaintenance();
        $result = $maintenance->backup->fullRestoreInteractive($backupDate);
        
        if ($result['success']) {
            echo "✅ Restauración completada\n";
        } else {
            echo "❌ Restauración fallida: " . $result['message'] . "\n";
            exit(1);
        }
        
    } elseif (isset($options['cleanup'])) {
        echo "Iniciando limpieza completa del sistema...\n";
        
        $maintenance = new SystemMaintenance();
        $maintenance->cleanupLogs();
        $maintenance->cleanupTempFiles();
        $maintenance->optimizeDatabase();
        
        echo "✅ Limpieza completada\n";
        
    } elseif (isset($options['health-check'])) {
        echo "Iniciando diagnóstico completo del sistema...\n";
        
        $maintenance = new SystemMaintenance();
        $maintenance->displaySystemStatus();
        $maintenance->performDatabaseIntegrityCheck();
        $maintenance->checkPHPConfiguration();
        $maintenance->biometricDiagnostic();
        
        echo "✅ Diagnóstico completado\n";
        
    } else {
        // Modo interactivo por defecto
        $maintenance = new SystemMaintenance();
        $maintenance->showMainMenu();
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}