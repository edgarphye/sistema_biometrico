<?php
/**
 * Script de Restauración Completa de Base de Datos
 * Sistema Biométrico
 * 
 * Este script proporciona una interfaz amigable para restaurar backups
 * con múltiples opciones de seguridad y verificación.
 * 
 * Uso:
 * php restore_database.php                    # Modo interactivo
 * php restore_database.php --restore 20241201    # Restaurar específico
 * php restore_database.php --list                  # Listar backups
 * php restore_database.php --dry-run 20241201     # Simular restauración
 */

require_once 'backup_database.php';

class DatabaseRestorer {
    private $backup;
    
    public function __construct() {
        $this->backup = new DatabaseBackup();
    }
    
    /**
     * Muestra menú principal interactivo
     */
    public function showMainMenu() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "🔧 SISTEMA DE RESTAURACIÓN DE BASE DE DATOS\n";
        echo "Sistema Biométrico v2.0+\n";
        echo str_repeat("=", 60) . "\n\n";
        
        while (true) {
            echo "Opciones disponibles:\n";
            echo "1. Listar backups disponibles\n";
            echo "2. Restaurar backup (modo interactivo)\n";
            echo "3. Restaurar backup (modo rápido)\n";
            echo "4. Simular restauración (dry-run)\n";
            echo "5. Verificar integridad de backups\n";
            echo "6. Crear backup de seguridad\n";
            echo "0. Salir\n\n";
            echo "Seleccione una opción [0-6]: ";
            
            $handle = fopen("php://stdin", "r");
            $choice = trim(fgets($handle));
            fclose($handle);
            
            switch ($choice) {
                case '1':
                    $this->listBackups();
                    break;
                    
                case '2':
                    $this->restoreInteractive();
                    break;
                    
                case '3':
                    $this->restoreQuick();
                    break;
                    
                case '4':
                    $this->dryRun();
                    break;
                    
                case '5':
                    $this->verifyBackups();
                    break;
                    
                case '6':
                    $this->createSafetyBackup();
                    break;
                    
                case '0':
                    echo "Saliendo del sistema de restauración.\n";
                    return;
                    
                default:
                    echo "❌ Opción inválida. Intente nuevamente.\n\n";
            }
        }
    }
    
    /**
     * Lista todos los backups disponibles
     */
    public function listBackups() {
        echo "\n📋 BACKUPS DISPONIBLES\n";
        echo str_repeat("-", 60) . "\n";
        
        try {
            $backups = $this->backup->listBackups();
            
            if (empty($backups)) {
                echo "⚠️  No se encontraron backups disponibles.\n\n";
                return;
            }
            
            printf("%-3s %-30s %-12s %-10s %-15s\n", 
                "#", "Archivo", "Tipo", "Tamaño", "Fecha");
            echo str_repeat("-", 60) . "\n";
            
            foreach ($backups as $index => $backup) {
                $size = $backup['size'] !== false ? number_format($backup['size'] / 1024 / 1024, 2) . ' MB' : 'N/A';
                $date = $backup['created'] !== false ? date('Y-m-d H:i', $backup['created']) : 'N/A';
                
                printf("%-3s %-30s %-12s %-10s %-15s\n",
                    $index + 1,
                    substr($backup['file'], 0, 30),
                    $backup['type'],
                    $size,
                    $date
                );
            }
            
            echo "\n";
            
        } catch (Exception $e) {
            echo "❌ Error al listar backups: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * Restauración interactiva completa
     */
    private function restoreInteractive() {
        $backupDate = $this->selectBackup();
        if (!$backupDate) {
            return;
        }
        
        try {
            echo "\n🔄 Iniciando restauración interactiva...\n";
            $result = $this->backup->fullRestoreInteractive($backupDate);
            
            if ($result['success']) {
                echo "\n✅ RESTAURACIÓN COMPLETADA EXITOSAMENTE\n";
                echo str_repeat("=", 50) . "\n";
                echo "📊 Details:\n";
                echo "- Statements ejecutados: " . ($result['statements_executed'] ?? 'N/A') . "\n";
                echo "- Tablas restauradas: " . ($result['tables_restored'] ?? 'N/A') . "\n";
                echo "- Tiempo de ejecución: " . ($result['execution_time'] ?? 'N/A') . " segundos\n";
            } else {
                echo "\n❌ Restauración fallida o cancelada: " . $result['message'] . "\n";
            }
            
        } catch (Exception $e) {
            echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
        }
        
        echo "\nPresione Enter para continuar...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
    }
    
    /**
     * Restauración rápida
     */
    private function restoreQuick() {
        $backupDate = $this->selectBackup();
        if (!$backupDate) {
            return;
        }
        
        echo "\n⚡ Modo de restauración rápida seleccionado\n";
        echo "⚠️  ADVERTENCIA: Esta opción no crea backup de seguridad ni verifica tablas\n";
        echo "¿Desea continuar? [s/N]: ";
        
        $handle = fopen("php://stdin", "r");
        $confirm = strtolower(trim(fgets($handle)));
        fclose($handle);
        
        if ($confirm !== 's') {
            echo "Restauración cancelada.\n\n";
            return;
        }
        
        try {
            echo "\n🚀 Iniciando restauración rápida...\n";
            $result = $this->backup->restore($backupDate, [
                'backup_before_restore' => false,
                'verify_tables' => false,
                'dry_run' => false,
                'ignore_errors' => true
            ]);
            
            echo "\n✅ Restauración rápida completada\n";
            echo "- Statements ejecutados: " . ($result['statements_executed'] ?? 'N/A') . "\n";
            echo "- Tiempo de ejecución: " . ($result['execution_time'] ?? 'N/A') . " segundos\n";
            
        } catch (Exception $e) {
            echo "\n❌ ERROR: " . $e->getMessage() . "\n";
        }
        
        echo "\nPresione Enter para continuar...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
    }
    
    /**
     * Simulación de restauración (dry-run)
     */
    private function dryRun() {
        $backupDate = $this->selectBackup();
        if (!$backupDate) {
            return;
        }
        
        try {
            echo "\n🎭 Iniciando simulación de restauración...\n";
            $result = $this->backup->restore($backupDate, [
                'backup_before_restore' => false,
                'verify_tables' => false,
                'dry_run' => true,
                'ignore_errors' => false
            ]);
            
            echo "\n✅ Simulación completada\n";
            echo "- Statements que se ejecutarían: " . ($result['statements'] ?? 'N/A') . "\n";
            echo "- 📋 Ningún dato será modificado\n";
            
        } catch (Exception $e) {
            echo "\n❌ ERROR en simulación: " . $e->getMessage() . "\n";
        }
        
        echo "\nPresione Enter para continuar...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
    }
    
    /**
     * Verifica integridad de backups
     */
    private function verifyBackups() {
        echo "\n🔍 Verificando integridad de todos los backups...\n";
        
        $backups = $this->backup->listBackups();
        if (empty($backups)) {
            echo "⚠️  No hay backups para verificar.\n\n";
            return;
        }
        
        $validCount = 0;
        $totalCount = count($backups);
        
        foreach ($backups as $backup) {
            $backupPath = "backups/database/{$backup['file']}";
            $hashPath = $backupPath . '.sha256';
            
            if (file_exists($hashPath)) {
                $storedHash = file_get_contents($hashPath);
                $actualHash = hash_file('sha256', $backupPath);
                
                if (hash_equals($storedHash, $actualHash)) {
                    echo "✅ {$backup['file']}\n";
                    $validCount++;
                } else {
                    echo "❌ {$backup['file']} (Hash inválido)\n";
                }
            } else {
                echo "⚠️  {$backup['file']} (Sin hash)\n";
            }
        }
        
        echo "\n📊 Resultado: {$validCount}/{$totalCount} backups válidos\n\n";
        
        echo "Presione Enter para continuar...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
    }
    
    /**
     * Crea un backup de seguridad
     */
    private function createSafetyBackup() {
        echo "\n💾 Creando backup de seguridad actual...\n";
        
        try {
            $result = $this->backup->backupFull();
            
            if ($result['success']) {
                echo "✅ Backup de seguridad creado exitosamente\n";
                echo "- Archivo: " . basename($result['file']) . "\n";
                echo "- Tamaño: " . number_format($result['size'] / 1024 / 1024, 2) . " MB\n";
                echo "- Tablas: {$result['tables']}\n";
            }
            
        } catch (Exception $e) {
            echo "\n❌ ERROR al crear backup: " . $e->getMessage() . "\n";
        }
        
        echo "\nPresione Enter para continuar...";
        $handle = fopen("php://stdin", "r");
        fgets($handle);
        fclose($handle);
    }
    
    /**
     * Permite al usuario seleccionar un backup de la lista
     */
    private function selectBackup() {
        $backups = $this->backup->listBackups();
        
        if (empty($backups)) {
            echo "⚠️  No hay backups disponibles.\n\n";
            return null;
        }
        
        $this->listBackups();
        
        echo "Ingrese el número del backup a restaurar (0 para cancelar): ";
        $handle = fopen("php://stdin", "r");
        $choice = trim(fgets($handle));
        fclose($handle);
        
        if ($choice === '0' || !is_numeric($choice)) {
            echo "Operación cancelada.\n\n";
            return null;
        }
        
        $index = (int)$choice - 1;
        
        if (!isset($backups[$index])) {
            echo "❌ Selección inválida.\n\n";
            return null;
        }
        
        // Extraer fecha del nombre del archivo
        $filename = $backups[$index]['file'];
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $filename, $matches)) {
            return $matches[1];
        }
        
        return $filename;
    }
}

// ====================================================================
// EJECUCIÓN PRINCIPAL
// ====================================================================

try {
    $options = getopt('', ['restore:', 'restore-interactive:', 'list', 'dry-run:', 'help']);
    
    if (isset($options['help']) || in_array('--help', $argv) || in_array('-h', $argv)) {
        echo "\n🔧 RESTAURACIÓN DE BASE DE DATOS - Sistema Biométrico\n";
        echo "Uso:\n";
        echo "  php restore_database.php                    # Modo interactivo\n";
        echo "  php restore_database.php --restore DATE      # Restaurar backup específico\n";
        echo "  php restore_database.php --restore-interactive DATE # Restauración interactiva\n";
        echo "  php restore_database.php --list              # Listar backups\n";
        echo "  php restore_database.php --dry-run DATE     # Simular restauración\n";
        echo "  php restore_database.php --help             # Mostrar esta ayuda\n\n";
        echo "Ejemplos:\n";
        echo "  php restore_database.php --restore 2024-12-01\n";
        echo "  php restore_database.php --dry-run 2024-12-01\n\n";
        exit(0);
    }
    
    if (isset($options['list'])) {
        $restorer = new DatabaseRestorer();
        $restorer->listBackups();
        
    } elseif (isset($options['restore'])) {
        $backupDate = $options['restore'];
        echo "Iniciando restauración del backup: {$backupDate}\n";
        
        $backup = new DatabaseBackup();
        $result = $backup->restore($backupDate);
        
        if ($result['success']) {
            echo "✅ " . $result['message'] . "\n";
            echo "📊 Statements ejecutados: " . ($result['statements_executed'] ?? 'N/A') . "\n";
            echo "📋 Tablas restauradas: " . ($result['tables_restored'] ?? 'N/A') . "\n";
            echo "⏱️  Tiempo: " . ($result['execution_time'] ?? 'N/A') . " segundos\n";
        } else {
            echo "❌ " . $result['message'] . "\n";
            exit(1);
        }
        
    } elseif (isset($options['restore-interactive'])) {
        $backupDate = $options['restore-interactive'];
        
        $backup = new DatabaseBackup();
        $result = $backup->fullRestoreInteractive($backupDate);
        
        if ($result['success']) {
            echo "✅ " . $result['message'] . "\n";
            echo "📊 Statements ejecutados: " . ($result['statements_executed'] ?? 'N/A') . "\n";
            echo "📋 Tablas restauradas: " . ($result['tables_restored'] ?? 'N/A') . "\n";
            echo "⏱️  Tiempo: " . ($result['execution_time'] ?? 'N/A') . " segundos\n";
        } else {
            echo "❌ " . $result['message'] . "\n";
            exit(1);
        }
        
    } elseif (isset($options['dry-run'])) {
        $backupDate = $options['dry-run'];
        echo "Iniciando simulación de restauración: {$backupDate}\n";
        
        $backup = new DatabaseBackup();
        $result = $backup->restore($backupDate, ['dry_run' => true]);
        
        if ($result['success']) {
            echo "✅ " . $result['message'] . "\n";
            echo "📊 Statements que se ejecutarían: " . ($result['statements'] ?? 'N/A') . "\n";
        } else {
            echo "❌ " . $result['message'] . "\n";
            exit(1);
        }
        
    } else {
        // Modo interactivo por defecto
        $restorer = new DatabaseRestorer();
        $restorer->showMainMenu();
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO: " . $e->getMessage() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    exit(1);
}