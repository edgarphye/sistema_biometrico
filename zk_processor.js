// Mapeo ZKTeco-ID a Empleado-ID
const zkMapping = {
    1: 1234,  // Juan Pérez
    2: 5678,  // María García
    3: 9012,  // Carlos López
    4: 3456,  // Ana Martínez
    5: 7890,  // Luis Rodríguez
    15: 1111, // Carmen Sánchez
    21: 2222, // Pedro Díaz
    26: 3333, // Laura Fernández
    28: 4444, // Miguel Torres
    31: 5555, // Isabel Ruiz
    32: 6666, // Javier Herrera
    36: 7777, // Elena Morales
    37: 8888, // Roberto Vargas
    39: 9999, // Patricia Castro
    46: 1010, // Diego Ortega
    54: 1212, // Silvia Navarro
    55: 1313, // Andrés Guerrero
    57: 1414, // Martha Ríos
    59: 1515, // Eduardo Mendoza
    62: 1616  // Claudia Flores
};

// Reglas de procesamiento
const processRecord = (record) => {
    const { empleado_id, fecha, hora, accion } = record;
    const diaSemana = new Date(fecha).getDay(); // 0=Domingo, 6=Sábado
    
    // Regla 1: Sábados y domingos omitir todos los registros
    if (diaSemana === 0 || diaSemana === 6) {
        return null;
    }
    
    // Regla 2: Solo insertar registros de acción "0" (Check-in)
    if (accion !== 0) {
        return null;
    }
    
    // Regla 3: Si el tiempo es entre 00:00:00 y 04:00:00, marcar como día anterior
    let fechaFinal = fecha;
    if (hora >= '00:00:00' && hora <= '04:00:00') {
        const fechaAnterior = new Date(fecha);
        fechaAnterior.setDate(fechaAnterior.getDate() - 1);
        fechaFinal = fechaAnterior.toISOString().split('T')[0];
    }
    
    return {
        empleado_id: zkMapping[empleado_id] || null,
        fecha: fechaFinal,
        hora: hora,
        accion: accion
    };
};

module.exports = {
    zkMapping,
    processRecord
};