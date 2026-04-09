#!/bin/bash

# ====================================================================
# Script de Backup Automatizado - Sistema Biométrico
# ====================================================================

# Configuración
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/backups/database"
LOG_FILE="$SCRIPT_DIR/logs/backup.log"
TIMESTAMP=$(date +"%Y-%m-%d_%H-%M-%S")

# Crear directorios necesarios
mkdir -p "$BACKUP_DIR"
mkdir -p "$SCRIPT_DIR/logs"

# Función para registrar logs
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Función para verificar espacio disponible
check_disk_space() {
    local available=$(df "$SCRIPT_DIR" | awk 'NR==2 {print $4}')
    local required=1048576  # 1GB in KB
    
    if [ "$available" -lt "$required" ]; then
        log_message "ERROR: Espacio en disco insuficiente. Disponible: $available KB, Requerido: $required KB"
        exit 1
    fi
}

# Función para ejecutar backup PHP
run_backup() {
    local backup_type="$1"
    
    log_message "Iniciando backup $backup_type..."
    
    cd "$SCRIPT_DIR"
    
    if command -v php >/dev/null 2>&1; then
        php backup_database.php --$backup_type 2>&1 | tee -a "$LOG_FILE"
        local exit_code=${PIPESTATUS[0]}
        
        if [ $exit_code -eq 0 ]; then
            log_message "Backup $backup_type completado exitosamente"
            return 0
        else
            log_message "ERROR: Backup $backup_type falló con código $exit_code"
            return 1
        fi
    else
        log_message "ERROR: PHP no está disponible en el sistema"
        return 1
    fi
}

# Función para verificar integridad de backups
verify_backups() {
    log_message "Verificando integridad de backups..."
    
    local backup_count=$(find "$BACKUP_DIR" -name "*.gz" | wc -l)
    log_message "Se encontraron $backup_count archivos de backup"
    
    # Verificar hashes SHA256
    local valid_hashes=0
    local total_hashes=0
    
    for hash_file in "$BACKUP_DIR"/*.sha256; do
        if [ -f "$hash_file" ]; then
            ((total_hashes++))
            local backup_file="${hash_file%.sha256}.gz"
            
            if [ -f "$backup_file" ]; then
                local stored_hash=$(cat "$hash_file")
                local actual_hash=$(sha256sum "$backup_file" | cut -d' ' -f1)
                
                if [ "$stored_hash" = "$actual_hash" ]; then
                    ((valid_hashes++))
                else
                    log_message "ERROR: Hash inválido para $backup_file"
                fi
            fi
        fi
    done
    
    log_message "Hashes válidos: $valid_hashes/$total_hashes"
    
    if [ $valid_hashes -eq $total_hashes ] && [ $total_hashes -gt 0 ]; then
        log_message "Todos los hashes son válidos"
        return 0
    else
        log_message "WARNING: Algunos hashes son inválidos o faltan"
        return 1
    fi
}

# Función para limpiar backups antiguos
cleanup_old_backups() {
    log_message "Iniciando limpieza de backups antiguos..."
    
    cd "$SCRIPT_DIR"
    
    if command -v php >/dev/null 2>&1; then
        php backup_database.php --cleanup 2>&1 | tee -a "$LOG_FILE"
        local exit_code=${PIPESTATUS[0]}
        
        if [ $exit_code -eq 0 ]; then
            log_message "Limpieza completada exitosamente"
        else
            log_message "ERROR: La limpieza falló con código $exit_code"
            return 1
        fi
    else
        log_message "ERROR: PHP no está disponible para la limpieza"
        return 1
    fi
}

# Función para mostrar uso
show_usage() {
    echo "Uso: $0 [OPCIÓN]"
    echo ""
    echo "Opciones disponibles:"
    echo "  full         Realizar backup completo"
    echo "  differential Realizar backup diferencial"
    echo "  list         Listar backups disponibles"
    echo "  restore DATE Restaurar backup específico (formato: YYYYMMDD)"
    echo "  cleanup      Limpiar backups antiguos"
    echo "  verify       Verificar integridad de backups"
    echo "  auto         Backup completo automático (modo predeterminado)"
    echo ""
    echo "Ejemplos:"
    echo "  $0                    # Backup completo automático"
    echo "  $0 full               # Backup completo"
    echo "  $0 differential       # Backup diferencial"
    echo "  $0 restore 20241201    # Restaurar backup del 2024-12-01"
    echo "  $0 list               # Listar todos los backups"
}

# ====================================================================
# LÓGICA PRINCIPAL
# ====================================================================

# Verificar que estamos en el directorio correcto
if [ ! -f "backup_database.php" ]; then
    echo "ERROR: backup_database.php no encontrado en el directorio actual"
    echo "Ejecuta este script desde el directorio raíz del proyecto"
    exit 1
fi

# Procesar argumentos
case "${1:-auto}" in
    "full")
        check_disk_space
        run_backup ""
        verify_backups
        ;;
        
    "differential")
        check_disk_space
        run_backup "differential"
        verify_backups
        ;;
        
    "list")
        cd "$SCRIPT_DIR"
        if command -v php >/dev/null 2>&1; then
            php backup_database.php --list
        else
            echo "ERROR: PHP no está disponible"
            exit 1
        fi
        ;;
        
    "restore")
        if [ -z "$2" ]; then
            echo "ERROR: Debes especificar una fecha de backup"
            echo "Uso: $0 restore YYYYMMDD"
            exit 1
        fi
        
        cd "$SCRIPT_DIR"
        if command -v php >/dev/null 2>&1; then
            php backup_database.php --restore "$2"
        else
            echo "ERROR: PHP no está disponible"
            exit 1
        fi
        ;;
        
    "cleanup")
        cleanup_old_backups
        ;;
        
    "verify")
        verify_backups
        ;;
        
    "auto")
        # Modo automático: backup completo con verificación
        log_message "=== INICIANDO BACKUP AUTOMÁTICO ==="
        check_disk_space
        run_backup ""
        verify_backups
        cleanup_old_backups
        log_message "=== BACKUP AUTOMÁTICO COMPLETADO ==="
        ;;
        
    "help"|"-h"|"--help")
        show_usage
        ;;
        
    *)
        echo "ERROR: Opción desconocida '$1'"
        echo ""
        show_usage
        exit 1
        ;;
esac

# Salir con el código de estado apropiado
exit_code=${PIPESTATUS[0]}
exit $exit_code