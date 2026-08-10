## Context

El sistema cargaba 19 recursos desde 4 CDNs distintas. La CSP tenía 6 orígenes externos. Esto impedía operar sin internet y complicaba la postura de seguridad.

## Goals / Non-Goals

**Goals:**
- Operación 100% offline sin dependencia de CDNs externos
- CSP simplificada a solo `'self'` y `'unsafe-inline'`/`'unsafe-eval'`
- Misma funcionalidad sin cambios en el comportamiento
- Tiempo de carga reducido (sin DNS + SSL a CDNs)

**Non-Goals:**
- No cambiar versión de librerías (mantener las versiones actuales)
- No minificar/empaquetar assets (dejar archivos individuales)

## Decisions

1. **Estructura `assets/{css,js,webfonts}/`** sobre `vendor/` o `public/`: Ya existía el directorio `assets/` con CSS/JS propios del proyecto; añadir los vendor allí evita crear nueva estructura y mantiene consistencia.
2. **Font Awesome 6.5.1 como versión única** sobre 6.0.0/6.4.0/6.5.1: layout.php ya usaba 6.5.1; las vistas con 6.0.0 o 6.4.0 funcionan igual con 6.5.1 por compatibilidad backward.
3. **Bootstrap 5.1.3 como versión única**: Una vista usaba 5.3.0; es compatible con 5.1.3 para CSS/JS usados (no hay breaking changes en los componentes utilizados).
4. **URLs con `BASE_URL`** sobre rutas absolutas: Consistente con el resto del proyecto que usa `BASE_URL` para todos los assets.
5. **CSP sin CDNs** sobre mantenerlas por precaución: Ya no se necesita ninguna URL externa; se eliminaron `fonts.googleapis.com`, `fonts.gstatic.com`, `chart.googleapis.com` que no se usaban.

## Risks / Trade-offs

- [Actualización de versiones] → Si se requiere una versión futura de Bootstrap/jQuery/etc, se debe descargar manualmente el nuevo archivo a `assets/`. No hay CDN que sirva la última versión automáticamente.
- [Espacio en disco] → ~4.5 MB de assets adicionales. No es un riesgo significativo.
- [Compatibilidad] → Bootstrap 5.3.0 → 5.1.3 podría romper algún componente específico de la vista `justificaciones/registrar.php`. Se verificó que los componentes usados (modal, forms, buttons) son compatibles entre ambas versiones.
