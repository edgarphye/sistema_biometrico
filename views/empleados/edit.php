 EN foto<?php include __DIR__ . '/../layout.php'; ?>

<div class="container mt-4">
    <h2>Editar Empleado</h2>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5>Información del Empleado</h5>
                </div>
                <div class="card-body">
                    <form action="/sistema_biometrico/empleados/<?php echo $empleado['id']; ?>/edit" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre:</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                           value="<?php echo htmlspecialchars($empleado['nombre']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="apellido">Apellido:</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido"
                                           value="<?php echo htmlspecialchars($empleado['apellido']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="rfc">RFC:</label>
                                    <input type="text" class="form-control" id="rfc" name="rfc"
                                           value="<?php echo htmlspecialchars($empleado['rfc']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="curp">CURP:</label>
                                    <input type="text" class="form-control" id="curp" name="curp"
                                           value="<?php echo htmlspecialchars($empleado['curp']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="area">Área:</label>
                                    <input type="text" class="form-control" id="area" name="area"
                                           value="<?php echo htmlspecialchars($empleado['area']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jerarquia">Jerarquía:</label>
                                    <select class="form-control" id="jerarquia" name="jerarquia" required>
                                        <option value="">Seleccionar jerarquía</option>
                                        <option value="Empleado" <?php echo $empleado['jerarquia'] == 'Empleado' ? 'selected' : ''; ?>>Empleado</option>
                                        <option value="Supervisor" <?php echo $empleado['jerarquia'] == 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                        <option value="Gerente" <?php echo $empleado['jerarquia'] == 'Gerente' ? 'selected' : ''; ?>>Gerente</option>
                                        <option value="Director" <?php echo $empleado['jerarquia'] == 'Director' ? 'selected' : ''; ?>>Director</option>
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
                                    <?php if ($empleado['foto_cara']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Foto actual:</small><br>
                                            <img src="/sistema_biometrico/<?php echo htmlspecialchars($empleado['foto_cara']); ?>"
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Empleado
                            </button>
                            <a href="/sistema_biometrico/empleados" class="btn btn-secondary ml-2">
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
