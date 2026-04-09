// Función de prueba simplificada para cargar empleados
async function testCargarEmpleados() {
    console.log('=== INICIANDO PRUEBA DE EMPLEADOS ===');
    
    try {
        console.log('Haciendo llamada a:', `${window.BASE_URL}/validaciones/buscar-empleados`);
        
        const response = await fetch(`${window.BASE_URL}/validaciones/buscar-empleados`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            console.log('✅ Empleados cargados:', data.empleados);
            
            // Renderizar empleados
            const container = document.getElementById('empleadosContainer');
            if (container) {
                if (data.empleados && data.empleados.length > 0) {
                    container.innerHTML = data.empleados.map(emp => 
                        `<div class="col-md-4 mb-2">
                            <div class="card">
                                <div class="card-body">
                                    <h6>${emp.nombre_completo}</h6>
                                    <small>${emp.area}</small>
                                </div>
                            </div>
                        </div>`
                    ).join('');
                    console.log('✅ Empleados renderizados');
                } else {
                    container.innerHTML = '<div class="alert alert-warning">No hay empleados</div>';
                }
            }
        } else {
            console.error('❌ Error en respuesta:', data.error);
        }
        
    } catch (error) {
        console.error('❌ Error en la llamada:', error);
    }
}

// Función de prueba para cargar validaciones
async function testCargarValidaciones() {
    console.log('=== INICIANDO PRUEBA DE VALIDACIONES ===');
    
    try {
        console.log('Haciendo llamada a:', `${window.BASE_URL}/validaciones/buscar-pendientes`);
        
        const response = await fetch(`${window.BASE_URL}/validaciones/buscar-pendientes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                pagina: 1,
                limite: 25
            })
        });
        
        console.log('Response status:', response.status);
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            console.log('✅ Validaciones cargadas:', data.validaciones);
            
            // Renderizar validaciones
            const tbody = document.getElementById('tablaValidacionesBody');
            if (tbody) {
                if (data.validaciones && data.validaciones.length > 0) {
                    tbody.innerHTML = data.validaciones.map(val => 
                        `<tr>
                            <td>${val.empleado_nombre} ${val.empleado_apellido}</td>
                            <td>${val.tipo_incidencia}</td>
                            <td>${val.descripcion_incidencia || 'N/A'}</td>
                        </tr>`
                    ).join('');
                    console.log('✅ Validaciones renderizadas');
                } else {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center">No hay validaciones pendientes</td></tr>';
                }
            }
        } else {
            console.error('❌ Error en respuesta:', data.error);
        }
        
    } catch (error) {
        console.error('❌ Error en la llamada:', error);
    }
}

// Ejecutar pruebas cuando la página esté lista
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página lista, esperando 1 segundo...');
    
    setTimeout(async function() {
        await testCargarEmpleados();
        await testCargarValidaciones();
    }, 1000);
});