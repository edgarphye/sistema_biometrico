
function testEmpleadoTemplate() {
    const empleado = {
        id: 121,
        nombre: "Ana García",
        apellido: "García",
        rfc: null,
        area: "Ventas",
        puesto: "Vendedora"
    };
    
    const template = `
        <div class="card">
            <h6>${escapeHtml((empleado.nombre || '') + ' ' + (empleado.apellido || ''))}</h6>
            <p>
                <strong>RFC:</strong> ${escapeHtml(empleado.rfc || '-')}<br>
                <strong>Área:</strong> ${escapeHtml(empleado.area || '-')}
            </p>
        </div>
    `;
    
    console.log("Template generado:", template);
    return template;
}

// Ejecutar prueba
testEmpleadoTemplate();
