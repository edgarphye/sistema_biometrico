const fs = require('fs');
const path = require('path');
const { zkMapping, processRecord } = require('./zk_processor.js');

// Simulación de inserción en base de datos
class DatabaseSimulator {
    constructor() {
        this.registros = [];
    }
    
    insert(asistencia) {
        this.registros.push(asistencia);
        console.log(`✓ Insertado: Empleado ${asistencia.empleado_id} - ${asistencia.fecha} ${asistencia.hora}`);
    }
    
    getEstadisticas() {
        const total = this.registros.length;
        const porEmpleado = {};
        
        this.registros.forEach(reg => {
            porEmpleado[reg.empleado_id] = (porEmpleado[reg.empleado_id] || 0) + 1;
        });
        
        return {
            total,
            porEmpleado,
            empleados: Object.keys(porEmpleado).length
        };
    }
}

// Procesador principal
class ZKTecoFileProcessor {
    constructor() {
        this.db = new DatabaseSimulator();
    }
    
    parseLine(line) {
        const parts = line.trim().split('\t');
        if (parts.length >= 3) {
            return {
                empleado_id: parseInt(parts[0]),
                fecha: parts[1],
                hora: parts[2],
                accion: parseInt(parts[3] || '0')
            };
        }
        return null;
    }
    
    async processFile(filePath) {
        console.log('🔄 Iniciando procesamiento del archivo...');
        
        try {
            const content = fs.readFileSync(filePath, 'utf-8');
            const lines = content.split('\n').filter(line => line.trim());
            
            let procesados = 0;
            let omitidos = 0;
            let sinMapeo = 0;
            
            for (let i = 0; i < lines.length; i++) {
                const record = this.parseLine(lines[i]);
                if (!record) continue;
                
                procesados++;
                
                const processed = processRecord(record);
                
                if (!processed) {
                    omitidos++;
                    continue;
                }
                
                if (!processed.empleado_id) {
                    sinMapeo++;
                    console.log(`⚠️  ZKTeco-ID ${record.empleado_id} sin mapeo a empleado`);
                    continue;
                }
                
                this.db.insert(processed);
            }
            
            console.log('\n📊 Estadísticas del procesamiento:');
            console.log(`Total líneas procesadas: ${procesados}`);
            console.log(`Registros omitidos (reglas): ${omitidos}`);
            console.log(`Sin mapeo ZKTeco-ID: ${sinMapeo}`);
            console.log(`Registros insertados: ${this.db.registros.length}`);
            
            const stats = this.db.getEstadisticas();
            console.log(`Empleados únicos: ${stats.empleados}`);
            
            console.log('\n📋 Registros por empleado:');
            Object.entries(stats.porEmpleado)
                .sort((a, b) => b[1] - a[1])
                .forEach(([empId, count]) => {
                    console.log(`  Empleado ${empId}: ${count} registros`);
                });
            
        } catch (error) {
            console.error('❌ Error al procesar el archivo:', error.message);
        }
    }
}

// Ejecución principal
if (require.main === module) {
    const processor = new ZKTecoFileProcessor();
    const filePath = path.join(__dirname, '20241106.TXT');
    
    if (!fs.existsSync(filePath)) {
        console.error('❌ Archivo no encontrado:', filePath);
        process.exit(1);
    }
    
    processor.processFile(filePath).then(() => {
        console.log('\n✅ Proceso completado exitosamente.');
    }).catch(error => {
        console.error('❌ Error en el proceso:', error);
        process.exit(1);
    });
}

module.exports = ZKTecoFileProcessor;