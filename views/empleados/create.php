<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Nuevo Empleado</h2>
    <a href="/sistema_biometrico/empleados" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>
<div class="container-fluid">

    <div class="card">
        <div class="card-body">
            <form method="POST" action="/sistema_biometrico/empleados/create" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nombre_completo" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                            <div class="form-text">
                                Nombre completo del empleado (este campo es para identificación general)
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="sexo" class="form-label">Sexo</label>
                            <select class="form-control" id="sexo" name="sexo" required>
                                <option value="">Seleccionar sexo</option>
                                <option value="H">Hombre</option>
                                <option value="M">Mujer</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="entidad_federativa" class="form-label">Entidad Federativa de Nacimiento</label>
                            <select class="form-control" id="entidad_federativa" name="entidad_federativa" required>
                                <option value="">Seleccionar entidad</option>
                                <option value="AGUASCALIENTES">Aguascalientes</option>
                                <option value="BAJA CALIFORNIA">Baja California</option>
                                <option value="BAJA CALIFORNIA SUR">Baja California Sur</option>
                                <option value="CAMPECHE">Campeche</option>
                                <option value="COAHUILA">Coahuila</option>
                                <option value="COLIMA">Colima</option>
                                <option value="CHIAPAS">Chiapas</option>
                                <option value="CHIHUAHUA">Chihuahua</option>
                                <option value="DISTRITO FEDERAL">Distrito Federal</option>
                                <option value="DURANGO">Durango</option>
                                <option value="GUANAJUATO">Guanajuato</option>
                                <option value="GUERRERO">Guerrero</option>
                                <option value="HIDALGO">Hidalgo</option>
                                <option value="JALISCO">Jalisco</option>
                                <option value="MEXICO">México</option>
                                <option value="MICHOACAN">Michoacán</option>
                                <option value="MORELOS">Morelos</option>
                                <option value="NAYARIT">Nayarit</option>
                                <option value="NUEVO LEON">Nuevo León</option>
                                <option value="OAXACA">Oaxaca</option>
                                <option value="PUEBLA">Puebla</option>
                                <option value="QUERETARO">Querétaro</option>
                                <option value="QUINTANA ROO">Quintana Roo</option>
                                <option value="SAN LUIS POTOSI">San Luis Potosí</option>
                                <option value="SINALOA">Sinaloa</option>
                                <option value="SONORA">Sonora</option>
                                <option value="TABASCO">Tabasco</option>
                                <option value="TAMAULIPAS">Tamaulipas</option>
                                <option value="TLAXCALA">Tlaxcala</option>
                                <option value="VERACRUZ">Veracruz</option>
                                <option value="YUCATAN">Yucatán</option>
                                <option value="ZACATECAS">Zacatecas</option>
                                <option value="NACIDO EXTRANJERO">Nacido en el Extranjero</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="primer_apellido" class="form-label">Primer Apellido</label>
                            <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="segundo_apellido" class="form-label">Segundo Apellido</label>
                            <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="nombres" class="form-label">Nombre(s)</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="rfc" class="form-label">RFC</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="rfc" name="rfc" required maxlength="13" readonly>
                                <button class="btn btn-outline-primary" type="button" id="generarCodigosBtn">
                                    <i class="fas fa-magic"></i> Generar RFC/CURP
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="curp" class="form-label">CURP</label>
                            <input type="text" class="form-control" id="curp" name="curp" required maxlength="18" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="area" class="form-label">Área</label>
                            <input type="text" class="form-control" id="area" name="area" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jerarquia" class="form-label">Jerarquía</label>
                            <select class="form-control" id="jerarquia" name="jerarquia" required>
                                <option value="">Seleccionar jerarquía</option>
                                <option value="Empleado">Empleado</option>
                                <option value="Supervisor">Supervisor</option>
                                <option value="Gerente">Gerente</option>
                                <option value="Director">Director</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <label for="foto_cara" class="form-label">Foto del Empleado</label>
                            <input type="file" class="form-control" id="foto_cara" name="foto_cara" accept="image/*">
                            <div class="form-text">
                                Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                            </div>
                            <div id="image-preview" class="mt-2" style="display: none;">
                                <img id="preview-img" src="" alt="Vista previa" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección de registro biométrico -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card border-info mb-3">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-fingerprint"></i> Registro Biométrico</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="registrar_huella" name="registrar_huella" value="1">
                                        <label class="form-check-label" for="registrar_huella">
                                            <strong>Registrar huella dactilar durante la creación del empleado</strong>
                                        </label>
                                        <div class="form-text">
                                            Al marcar esta opción, se activará el detector de huella para capturar la huella del empleado.
                                        </div>
                                    </div>
                                </div>

                                <div id="dispositivo-section" style="display: none;">
                                    <div class="mb-3">
                                        <label for="dispositivo_huella" class="form-label">Dispositivo Biométrico</label>
                                        <select class="form-select" id="dispositivo_huella" name="dispositivo_huella">
                                            <?php if (!empty($dispositivos)): ?>
                                                <?php foreach ($dispositivos as $dispositivo): ?>
                                                    <option value="<?php echo htmlspecialchars($dispositivo['dispositivo_id']); ?>">
                                                        Dispositivo <?php echo htmlspecialchars($dispositivo['dispositivo_id']); ?> - <?php echo htmlspecialchars($dispositivo['nombre']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="1">Dispositivo 1 (Predeterminado)</option>
                                            <?php endif; ?>
                                        </select>
                                        <div class="form-text">
                                            Seleccione el dispositivo biométrico para capturar la huella.
                                        </div>
                                    </div>

                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Instrucciones:</strong> Una vez que marque la opción de registrar huella,
                                        al guardar el empleado se activará automáticamente el proceso de captura de huella
                                        desde el dispositivo seleccionado.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Empleado
                </button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../layout.php'; ?>

<script>
document.getElementById('foto_cara').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});

// Controlar visibilidad de la sección de dispositivo biométrico
document.getElementById('registrar_huella').addEventListener('change', function(e) {
    const dispositivoSection = document.getElementById('dispositivo-section');
    if (e.target.checked) {
        dispositivoSection.style.display = 'block';
    } else {
        dispositivoSection.style.display = 'none';
    }
});

// Función para generar códigos desde el formulario principal
document.getElementById('generarCodigosBtn').addEventListener('click', function() {
    const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
    const primerApellido = document.getElementById('primer_apellido').value;
    const segundoApellido = document.getElementById('segundo_apellido').value;
    const nombres = document.getElementById('nombres').value;
    const sexo = document.getElementById('sexo').value;
    const entidadFederativa = document.getElementById('entidad_federativa').value;

    if (!fechaNacimiento || !primerApellido || !segundoApellido || !nombres || !sexo || !entidadFederativa) {
        alert('Por favor complete todos los campos antes de generar los códigos.');
        return;
    }

    // Mostrar loading en el botón
    const btn = document.getElementById('generarCodigosBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando...';
    btn.disabled = true;

    // Enviar datos al servidor para generar ambos códigos
    Promise.all([
        fetch('/sistema_biometrico/empleados/generate_rfc', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fecha_nacimiento: fechaNacimiento,
                primer_apellido: primerApellido,
                segundo_apellido: segundoApellido,
                nombres: nombres,
                sexo: sexo,
                entidad_federativa: entidadFederativa
            })
        }),
        fetch('/sistema_biometrico/empleados/generate_curp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                fecha_nacimiento: fechaNacimiento,
                primer_apellido: primerApellido,
                segundo_apellido: segundoApellido,
                nombres: nombres,
                sexo: sexo,
                entidad_federativa: entidadFederativa
            })
        })
    ])
    .then(responses => Promise.all(responses.map(r => r.json())))
    .then(([rfcData, curpData]) => {
        // Restaurar botón
        btn.innerHTML = originalText;
        btn.disabled = false;

        if (rfcData.success && curpData.success) {
            document.getElementById('rfc').value = rfcData.rfc;
            document.getElementById('curp').value = curpData.curp;

            // Mostrar mensaje de éxito
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show';
            successAlert.innerHTML = `
                <i class="fas fa-check"></i> RFC y CURP generados exitosamente
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            const form = document.querySelector('form');
            form.insertBefore(successAlert, form.firstChild);

            // Remover alerta después de 5 segundos
            setTimeout(() => {
                if (successAlert.parentNode) {
                    successAlert.remove();
                }
            }, 5000);
        } else {
            const errorMsg = rfcData.error || curpData.error;
            alert('Error al generar códigos: ' + errorMsg);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.innerHTML = originalText;
        btn.disabled = false;
        alert('Error al generar códigos. Verifique la consola para más detalles.');
    });
});




</script>
