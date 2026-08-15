# Tareas: Marca de entrega de oficios de notas malas

## 1. Migración de BD

- [x] 1.1 Agregar columnas a `documentos_generados`: `entregado TINYINT(1) NOT NULL DEFAULT 0`, `fecha_entrega DATETIME NULL`, `entregado_por INT NULL`
- [x] 1.2 Verificar que los oficios existentes queden con `entregado = 0` (default) y sin fecha/usuario de entrega

## 2. Modelo DocumentoGenerado

- [x] 2.1 Agregar método `marcarEntregado($id, $usuario_id)` en `models/PlantillaDocumento.php` (UPDATE de `entregado`, `fecha_entrega = NOW()`, `entregado_por`)
- [x] 2.2 Agregar método `desmarcarEntregado($id)` que limpie `entregado`, `fecha_entrega` y `entregado_por`
- [x] 2.3 Verificar que las consultas existentes (`getById`, `getByEmpleadoPeriodo`, SELECT del índice) retornen los nuevos campos sin cambios

## 3. Rutas

- [x] 3.1 Agregar en `routes.php`: `POST /notas-malas/oficio/{id:\d+}/marcar-entregado` → `NotasMalasController::marcarEntregado`
- [x] 3.2 Agregar en `routes.php`: `POST /notas-malas/oficio/{id:\d+}/desmarcar-entregado` → `NotasMalasController::desmarcarEntregado`

## 4. Controlador NotasMalasController

- [x] 4.1 Implementar `marcarEntregado($id)`: validar CSRF, roles (`superadmin`, `admin`, `rh`), que el documento exista y sea `oficio_notas_malas`; llamar `marcarEntregado` y responder JSON
- [x] 4.2 Implementar `desmarcarEntregado($id)`: validaciones equivalentes; llamar `desmarcarEntregado` y responder JSON
- [x] 4.3 Manejar errores (404 documento inexistente/otro tipo, 403 sin permisos, 500 interno)

## 5. Vista del módulo notas malas

- [x] 5.1 En `index()` de `NotasMalasController`, cuando el oficio existe y `entregado = 0`, renderizar botón "Marcar entregado" junto a las acciones del oficio
- [x] 5.2 Cuando `entregado = 1`, renderizar badge verde "Entregado" con fecha formateada + botón "Deshacer entrega"
- [x] 5.3 Agregar JS: handler de click para "Marcar entregado" y "Deshacer entrega" con `confirm()`, AJAX POST con header `X-CSRF-Token`, y `location.reload()` al éxito
- [x] 5.4 Mantener la tarjeta sin acciones de entrega cuando no hay oficio generado

## 6. Verificación

- [x] 6.1 Probar marcar/desmarcar entrega con un oficio generado (rol rh) y confirmar badge/fecha en la tarjeta (test funcional `OficiosEntregaEndpointsTest`)
- [x] 6.2 Probar 404 con id inexistente y 403 con usuario sin permisos
- [x] 6.3 Probar que regenerar un oficio entregado lo deja como no entregado (verificado por código: `generarOficio` elimina el registro previo y el nuevo nace con `entregado = 0`)
- [x] 6.4 Ejecutar validación de sintaxis PHP de los archivos modificados (`php -l`)
