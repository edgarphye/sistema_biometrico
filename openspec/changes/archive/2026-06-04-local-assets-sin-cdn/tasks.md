## 1. Descarga de Assets

- [x] 1.1 Descargar Bootstrap 5.1.3 CSS + JS bundle a `assets/css/bootstrap.min.css` y `assets/js/bootstrap.bundle.min.js`
- [x] 1.2 Descargar jQuery 3.6.0 a `assets/js/jquery-3.6.0.min.js`
- [x] 1.3 Descargar DataTables 1.13.6 (CSS, jquery.dataTables, dataTables.bootstrap5) a `assets/`
- [x] 1.4 Descargar Font Awesome 6.5.1 CSS + webfonts a `assets/css/fontawesome-all.min.css` y `assets/webfonts/`
- [x] 1.5 Descargar Chart.js 4.4.1, ExcelJS 4.4.0, XLSX 0.18.5 a `assets/js/`
- [x] 1.6 Descargar Three.js 0.128.0 + OrbitControls + Spanish.json a `assets/js/`

## 2. Actualización de Vistas

- [x] 2.1 Actualizar `views/layout.php`: reemplazar 8 CDN URLs, remover bundles duplicados de Bootstrap
- [x] 2.2 Actualizar `views/auth/login.php`
- [x] 2.3 Actualizar `views/auth/verify2fa.php`
- [x] 2.4 Actualizar `views/asistencia.php`
- [x] 2.5 Actualizar `views/dashboard.php`
- [x] 2.6 Actualizar `views/biometricos/{index,show,configurar}.php`
- [x] 2.7 Actualizar `views/zktecologs/{index,procesar}.php`
- [x] 2.8 Actualizar `views/huellas/index.php`
- [x] 2.9 Actualizar `views/justificaciones/registrar.php`
- [x] 2.10 Actualizar `views/reportes/retardos_incidencias.php`
- [x] 2.11 Actualizar `views/ai/dashboard.php`

## 3. Seguridad

- [x] 3.1 Simplificar CSP en `helpers/SecurityHelper.php`: remover CDNs de script-src, style-src, font-src, img-src
- [x] 3.2 Verificar que CSP header solo contenga `'self'` como origen

## 4. Verificación

- [x] 4.1 Verificar que curl devuelve 200 para todos los assets locales
- [x] 4.2 Ejecutar suite completa de tests (75 tests, 212 assertions)
- [x] 4.3 Confirmar que no quedan URLs CDN en ninguna vista
