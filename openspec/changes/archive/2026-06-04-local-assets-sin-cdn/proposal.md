## Why

El sistema depende de 19 URLs CDN (jsDelivr, jQuery CDN, DataTables CDN, Cloudflare) para cargar Bootstrap, jQuery, DataTables, Font Awesome, Chart.js, ExcelJS, XLSX, Three.js y otros. Esto requiere internet para funcionar, creando riesgos de disponibilidad en entornos sin conectividad (intranet gubernamental, firewalls restrictivos). Migrar a archivos locales elimina la dependencia de red, mejora tiempos de carga y simplifica la CSP.

## What Changes

- Descargar 19 archivos CDN (CSS, JS, JSON) a `assets/css/`, `assets/js/`, `assets/webfonts/`
- Migrar todas las referencias CDN en 14 archivos PHP de vistas a rutas locales con `BASE_URL`
- Simplificar CSP: remover todos los orígenes CDN externos, dejar solo `'self'`
- Remover duplicados de Bootstrap bundle en layout.php (líneas 1603-1604)
- Estandarizar versiones: Font Awesome 6.5.1, Bootstrap 5.1.3, Chart.js 4.4.1

## Capabilities

### New Capabilities
- `assets-locales`: Gestión de assets locales sin dependencia CDN, con directorio estructurado `assets/{css,js,webfonts}/`

### Modified Capabilities
- Ninguna — no cambian requerimientos funcionales, solo infraestructura/dependencias

## Impact

- `helpers/SecurityHelper.php`: CSP simplificada a `'self'`
- `views/layout.php`: 8 CDN URLs reemplazadas, 2 bundles duplicados removidos
- `views/auth/login.php`, `views/auth/verify2fa.php`
- `views/asistencia.php`, `views/dashboard.php`
- `views/biometricos/{index,show,configurar}.php`
- `views/zktecologs/{index,procesar}.php`
- `views/huellas/index.php`
- `views/justificaciones/registrar.php`
- `views/reportes/retardos_incidencias.php`
- `views/ai/dashboard.php`
- `assets/css/` (4 archivos nuevos), `assets/js/` (12 archivos nuevos), `assets/webfonts/` (8 archivos nuevos)
- CSP actualizada: sin CDNs externas en script-src, style-src, font-src, img-src
