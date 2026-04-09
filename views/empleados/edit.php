<?php
require_once __DIR__ . '/../../helpers/Csrf.php';
ob_start();
?>

<div class="container mt-4">
    <h2>Editar Empleado</h2>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5>Información del Empleado</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['form_errors'])): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Errores de validación:</h6>
                            <ul class="mb-0">
                                <?php foreach ($_SESSION['form_errors'] as $field => $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php unset($_SESSION['form_errors']); ?>
                    <?php endif; ?>
                    
                    <form action="<?php echo rtrim(BASE_URL, '/') . '/empleados/' . ($empleado['id'] ?? '') . '/edit'; ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="<?php echo htmlspecialchars(Csrf::token()); ?>">
                        
                        <?php
                        // Divide de forma segura el campo 'apellido' para evitar avisos de PHP.
                        $apellidos = explode(' ', $empleado['apellido'] ?? '', 2);
                        $primer_apellido = $apellidos[0] ?? '';
                        $segundo_apellido = $apellidos[1] ?? '';
                        ?>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="primer_apellido">Primer Apellido:</label>
                                    <input type="text" class="form-control" id="primer_apellido" name="primer_apellido"
                                           value="<?php echo htmlspecialchars($primer_apellido); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="segundo_apellido">Segundo Apellido:</label>
                                    <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido"
                                           value="<?php echo htmlspecialchars($segundo_apellido); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nombres">Nombre(s):</label>
                                    <input type="text" class="form-control" id="nombres" name="nombres"
                                           value="<?php echo htmlspecialchars($empleado['nombre'] ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sexo">Sexo:</label>
                                    <select class="form-control" id="sexo" name="sexo">
                                        <option value="">Seleccionar sexo</option>
                                        <option value="H" <?php echo ($empleado['sexo'] ?? '') == 'H' ? 'selected' : ''; ?>>Hombre</option>
                                        <option value="M" <?php echo ($empleado['sexo'] ?? '') == 'M' ? 'selected' : ''; ?>>Mujer</option>
                                        <option value="O" <?php echo ($empleado['sexo'] ?? '') == 'O' ? 'selected' : ''; ?>>Otro</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_nacimiento">Fecha Nacimiento:</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                           value="<?php echo htmlspecialchars($empleado['fecha_nacimiento'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="rfc">RFC:</label>
                                    <input type="text" class="form-control" id="rfc" name="rfc"
                                           value="<?php echo htmlspecialchars($empleado['rfc'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="curp">CURP:</label>
                                    <input type="text" class="form-control" id="curp" name="curp"
                                           value="<?php echo htmlspecialchars($empleado['curp'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area">Área (Departamento):</label>
                                    <input type="text" class="form-control" id="area" name="area"
                                           value="<?php echo htmlspecialchars($empleado['area'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="area_fisica">Área Física (Ubicación):</label>
                                    <input type="text" class="form-control" id="area_fisica" name="area_fisica"
                                           value="<?php echo htmlspecialchars($empleado['area_fisica'] ?? ''); ?>"
                                           placeholder="Ej: Edificio A, Planta Baja">
                                    <div class="form-text">Ubicación física donde trabaja el empleado</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="clave_depto">Clave Depto:</label>
                                    <input type="text" class="form-control" id="clave_depto" name="clave_depto"
                                           value="<?php echo htmlspecialchars($empleado['clave_depto'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="jerarquia">Jerarquía:</label>
                                    <select class="form-control" id="jerarquia" name="jerarquia" required>
                                        <option value="">Seleccionar jerarquía</option>
                                        <option value="Empleado" <?php echo ($empleado['jerarquia'] ?? '') == 'Empleado' ? 'selected' : ''; ?>>Empleado</option>
                                        <option value="Supervisor" <?php echo ($empleado['jerarquia'] ?? '') == 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                        <option value="Gerente" <?php echo ($empleado['jerarquia'] ?? '') == 'Gerente' ? 'selected' : ''; ?>>Gerente</option>
                                        <option value="Director" <?php echo ($empleado['jerarquia'] ?? '') == 'Director' ? 'selected' : ''; ?>>Director</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="entidad_federativa">Entidad Federativa:</label>
                                    <select class="form-control" id="entidad_federativa" name="entidad_federativa">
                                        <option value="">Seleccionar</option>
                                        <option value="AS" <?php echo ($empleado['entidad_federativa'] ?? '') == 'AS' ? 'selected' : ''; ?>>Aguascalientes</option>
                                        <option value="BC" <?php echo ($empleado['entidad_federativa'] ?? '') == 'BC' ? 'selected' : ''; ?>>Baja California</option>
                                        <option value="BS" <?php echo ($empleado['entidad_federativa'] ?? '') == 'BS' ? 'selected' : ''; ?>>Baja California Sur</option>
                                        <option value="CC" <?php echo ($empleado['entidad_federativa'] ?? '') == 'CC' ? 'selected' : ''; ?>>Campeche</option>
                                        <option value="CL" <?php echo ($empleado['entidad_federativa'] ?? '') == 'CL' ? 'selected' : ''; ?>>Coahuila</option>
                                        <option value="CM" <?php echo ($empleado['entidad_federativa'] ?? '') == 'CM' ? 'selected' : ''; ?>>Colima</option>
                                        <option value="CS" <?php echo ($empleado['entidad_federativa'] ?? '') == 'CS' ? 'selected' : ''; ?>>Chiapas</option>
                                        <option value="CH" <?php echo ($empleado['entidad_federativa'] ?? '') == 'CH' ? 'selected' : ''; ?>>Chihuahua</option>
                                        <option value="DF" <?php echo ($empleado['entidad_federativa'] ?? '') == 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                                        <option value="DG" <?php echo ($empleado['entidad_federativa'] ?? '') == 'DG' ? 'selected' : ''; ?>>Durango</option>
                                        <option value="GT" <?php echo ($empleado['entidad_federativa'] ?? '') == 'GT' ? 'selected' : ''; ?>>Guanajuato</option>
                                        <option value="GR" <?php echo ($empleado['entidad_federativa'] ?? '') == 'GR' ? 'selected' : ''; ?>>Guerrero</option>
                                        <option value="HG" <?php echo ($empleado['entidad_federativa'] ?? '') == 'HG' ? 'selected' : ''; ?>>Hidalgo</option>
                                        <option value="JC" <?php echo ($empleado['entidad_federativa'] ?? '') == 'JC' ? 'selected' : ''; ?>>Jalisco</option>
                                        <option value="MC" <?php echo ($empleado['entidad_federativa'] ?? '') == 'MC' ? 'selected' : ''; ?>>México</option>
                                        <option value="MN" <?php echo ($empleado['entidad_federativa'] ?? '') == 'MN' ? 'selected' : ''; ?>>Michoacán</option>
                                        <option value="MS" <?php echo ($empleado['entidad_federativa'] ?? '') == 'MS' ? 'selected' : ''; ?>>Morelos</option>
                                        <option value="NT" <?php echo ($empleado['entidad_federativa'] ?? '') == 'NT' ? 'selected' : ''; ?>>Nayarit</option>
                                        <option value="NL" <?php echo ($empleado['entidad_federativa'] ?? '') == 'NL' ? 'selected' : ''; ?>>Nuevo León</option>
                                        <option value="OC" <?php echo ($empleado['entidad_federativa'] ?? '') == 'OC' ? 'selected' : ''; ?>>Oaxaca</option>
                                        <option value="PL" <?php echo ($empleado['entidad_federativa'] ?? '') == 'PL' ? 'selected' : ''; ?>>Puebla</option>
                                        <option value="QT" <?php echo ($empleado['entidad_federativa'] ?? '') == 'QT' ? 'selected' : ''; ?>>Querétaro</option>
                                        <option value="QR" <?php echo ($empleado['entidad_federativa'] ?? '') == 'QR' ? 'selected' : ''; ?>>Quintana Roo</option>
                                        <option value="SP" <?php echo ($empleado['entidad_federativa'] ?? '') == 'SP' ? 'selected' : ''; ?>>San Luis Potosí</option>
                                        <option value="SL" <?php echo ($empleado['entidad_federativa'] ?? '') == 'SL' ? 'selected' : ''; ?>>Sinaloa</option>
                                        <option value="SR" <?php echo ($empleado['entidad_federativa'] ?? '') == 'SR' ? 'selected' : ''; ?>>Sonora</option>
                                        <option value="TC" <?php echo ($empleado['entidad_federativa'] ?? '') == 'TC' ? 'selected' : ''; ?>>Tabasco</option>
                                        <option value="TS" <?php echo ($empleado['entidad_federativa'] ?? '') == 'TS' ? 'selected' : ''; ?>>Tamaulipas</option>
                                        <option value="TL" <?php echo ($empleado['entidad_federativa'] ?? '') == 'TL' ? 'selected' : ''; ?>>Tlaxcala</option>
                                        <option value="VZ" <?php echo ($empleado['entidad_federativa'] ?? '') == 'VZ' ? 'selected' : ''; ?>>Veracruz</option>
                                        <option value="YN" <?php echo ($empleado['entidad_federativa'] ?? '') == 'YN' ? 'selected' : ''; ?>>Yucatán</option>
                                        <option value="ZS" <?php echo ($empleado['entidad_federativa'] ?? '') == 'ZS' ? 'selected' : ''; ?>>Zacatecas</option>
                                        <option value="NE" <?php echo ($empleado['entidad_federativa'] ?? '') == 'NE' ? 'selected' : ''; ?>>Nacido en el Extranjero</option>
                                    </select>
                                </div>
                            </div>
                           <div class="col-md-3">
                               <div class="form-group">
                                   <label for="activo">Estatus:</label>
                                   <select class="form-control" id="activo" name="activo">
                                       <option value="1" <?php echo ($empleado['activo'] ?? 1) == 1 ? 'selected' : ''; ?>>Activo</option>
                                       <option value="0" <?php echo ($empleado['activo'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactivo</option>
                                   </select>
                               </div>
                           </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="foto_cara">Foto del Empleado:</label>
                                    <input type="file" class="form-control" id="foto_cara" name="foto_cara" accept="image/*">
                                    <div class="form-text">
                                        Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                                    </div>
                                    <?php if (!empty($empleado['foto_cara'])): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Foto actual:</small><br>
                                            <img src="<?php echo htmlspecialchars(rtrim(BASE_URL, '/') . '/' . ($empleado['foto_cara'] ?? '')); ?>"
                                                 alt="Foto actual"
                                                 class="img-thumbnail mt-1"
                                                 style="max-width: 150px; max-height: 150px;">
                                        </div>
                                    <?php endif; ?>
                                    <div id="image-preview" class="mt-2" style="display: none;">
                                        <small class="text-muted">Vista previa de nueva foto:</small><br>
                                        <img id="preview-img" src="" alt="Vista previa" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary" style="background-color: #9F2241; border-color: #9F2241;">
                                <i class="fas fa-save"></i> Actualizar Empleado
                            </button>
                            <a href="<?php echo rtrim(BASE_URL, '/') . '/empleados'; ?>" class="btn btn-secondary ml-2" style="border-color: #6F7271; color: #6F7271;">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
