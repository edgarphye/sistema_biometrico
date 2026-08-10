## 1. Base de datos

- [x] 1.1 Crear migración `migrations/YYYYMMDD_agregar_oficios_notas_malas.sql` con `CREATE TABLE claves_presupuestales` (id, empleado_id FK, clave), `CREATE TABLE config_oficios` y el `UPDATE` de normalización de `notas_malas.periodo` (join a `retardos` por `retardo_id`, setear `periodo = DATE_FORMAT(r.fecha, '%Y-%m-01')`)
- [x] 1.2 Insertar fila inicial en `config_oficios` (prefijo `DGIFA/CA/RH`, anio actual, ultimo_folio 0, firmante, iniciales, membrete_path)
- [x] 1.3 Ejecutar la migración en la BD `sistema_biometrico` y verificar con `DESCRIBE` y consulta de `notas_malas` normalizada
- [x] 1.4 Extraer `word/media/image1.png` del docx de referencia a `assets/images/membrete_sep.png`

## 2. Modelos y datos de empleado

- [x] 2.1 Crear modelo `models/ClavePresupuestal.php` con CRUD por `empleado_id` (`getByEmpleado`, `guardar` masivo, `eliminarPorEmpleado`) sobre `claves_presupuestales`
- [x] 2.2 Agregar en el formulario de alta/edición de empleados (`views/empleados/`) la captura dinámica de una o varias claves presupuestales (filas agregar/eliminar)
- [x] 2.3 Guardar/cargar las claves presupuestales en el guardado/edición del empleado (`EmpleadoController`)
- [x] 2.4 Crear modelo `models/ConfigOficio.php` con `getConfig()`, `obtenerSiguienteFolio($anio)`, `guardar($data)` sobre `config_oficios`

## 3. Servicio de generación de oficios

- [x] 3.1 Crear `services/OficioNotasMalasService.php` con `getIncidenciasPeriodo($mes, $anio, $quincena)` combinando `notas_malas` y retardos sin justificar mediante consulta única sobre `retardos` (join por `retardo_id`, filtro por `r.fecha` del rango, `justificado = 0 OR EXISTS(nota)` y `tipo_retraso IN ('retardo_menor','retardo_mayor','falta')`)
- [x] 3.2 Implementar cálculo de notas por empleado (fórmula `floor(menores/2) + mayores + faltas`) y clasificación por inciso (a/b), excluyendo notas de tipo `inasistencia`/`comision_rechazada`/`otro`
- [x] 3.3 Implementar `generarOficio($empleado_id, $mes, $anio, $quincena, $regenerar)` con PhpWord: membrete en header, folio, destinatario (nombre + ID, RFC, claves presupuestales unidas con ` / `, área), cuerpo legal, tablas MES/DÍA/HORA por inciso, advertencia y firma
- [x] 3.4 Implementar variante con suspensión (5+ notas): párrafo Art. 80 inciso d) y día programado (martes/miércoles/jueves)
- [x] 3.5 Implementar foliado atómico vía `ConfigOficio::obtenerSiguienteFolio` y formato `{prefijo}-{folio}/{anio}`
- [x] 3.6 Guardar .docx en `uploads/oficios/{YYYY}/` y registrar en `documentos_generados` (tipo `oficio_notas_malas`, archivo_path, periodo)
- [x] 3.7 Implementar prevención de duplicados (mismo empleado+periodo sin `$regenerar`) y `generarMasivo($mes, $anio, $quincena)` con ZIP

## 4. Controlador y rutas

- [x] 4.1 Agregar acciones en `controllers/NotasMalasController.php`: `generarOficio()`, `generarTodos()`, `descargarOficio($id)`
- [x] 4.2 Registrar rutas en `routes.php`: `POST /notas-malas/generar-oficio`, `POST /notas-malas/generar-todos`, `GET /notas-malas/oficio/{id:\d+}`
- [x] 4.3 Agregar endpoint de configuración de oficios (leer/actualizar `config_oficios`) con permisos admin/rh

## 5. Interfaz de usuario

- [x] 5.1 En `NotasMalasController::index` integrar datos unificados (notas_malas + retardos sin justificar) y filtro de quincena (Todas / 1ª / 2ª)
- [x] 5.2 Agregar botón "Generar oficio" por empleado y "Generar todos los oficios (ZIP)" con jQuery AJAX
- [x] 5.3 Mostrar folio/estado del oficio generado y enlace de descarga cuando ya exista
- [x] 5.4 Agregar sección/acciones de configuración de oficios (firma, prefijo, iniciales) para admin/rh
- [x] 5.5 Agregar modal `#modalVerOficio` en `NotasMalasController::index` con iframe de vista previa y botón "Descargar Word"; el botón "Ver" por empleado abre el modal
- [x] 5.6 Agregar endpoint `GET /notas-malas/oficio/{id:\d+}/preview` (`verOficioHtml`) que convierte el .docx a HTML autocontenido con LibreOffice (filtro `HTML:EmbedImages`), cacheado en `uploads/oficios/{YYYY}/.preview/` con invalidación por `filemtime` del .docx

## 6. Verificación

- [x] 6.1 Probar generación individual de oficio .docx con un empleado con retardos sin justificar y validar contenido contra el formato de referencia
- [x] 6.2 Probar generación masiva (ZIP) y descarga desde `/notas-malas/oficio/{id}`
- [x] 6.3 Probar foliado secuencial y prevención de duplicados
- [x] 6.4 Probar variante con suspensión usando un escenario de 5+ notas malas
- [x] 6.5 Probar el manejo de registros `notas_malas` inconsistentes (retardo_id que apunta a retardo fuera del rango o a comisión) verificando que no se filtran ni se cuentan en el oficio
- [x] 6.6 Probar un empleado con varias claves presupuestales para validar que el oficio las muestra unidas con ` / `
- [x] 6.7 Validar con `php -l` los archivos modificados y ejecutar la suite de pruebas existente
- [x] 6.8 Probar end-to-end vía HTTP: login real, generación de oficio de advertencia (folio 001) y suspensión (folio 002), vista previa `/preview` (HTML autocontenido con membrete en base64), descarga .docx y ejecutar los 20 tests de `OficioNotasMalasServiceTest`
