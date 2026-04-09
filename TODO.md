# TODO: Alinear Proyecto con Pseudocódigo de Retardos - COMPLETADO

## Información Recopilada
- Pseudocódigo requiere clasificación de retardos: MENOR (>10 <=20 min), MAYOR (>20 <=30 min). Ignorar retardos >30 min.
- Acumulación quincenal (bi-semanal) de retardos.
- Después de registrar retardo: verificar acumulados quincenales, mostrar mensaje si <2, aplicar nota mala y notificar si >=1.
- Implementar AplicarNotaMala (crear sanción) y NotificarEmpleado (usar email).

## Plan de Cambios - IMPLEMENTADO
- [x] Modificar `calcularRetardo` en `AsistenciaService.php` para usar rangos del pseudocódigo.
- [x] Agregar `getRetardosAcumuladosQuincena` en `Retardo.php`.
- [x] Modificar `registrarEntrada` en `AsistenciaService.php` para incluir lógica post-registro.
- [x] Agregar métodos `AplicarNotaMala` y `NotificarEmpleado` en modelos/servicios relevantes.

## Archivos Dependientes
- `services/AsistenciaService.php`
- `models/Retardo.php`
- `models/Sancion.php` (para nota mala)
- `controllers/EmailController.php` (para notificación)

## Pasos de Seguimiento - COMPLETADOS
- [x] Probar registro de entrada con retardos en rangos especificados.
- [x] Verificar acumulación quincenal.
- [x] Confirmar mensajes, sanciones y notificaciones.

## Nuevos TODOs: Expansión de Tests y Preparación para Producción

### Testing Expansion (Prioridad Alta)
- [ ] Ejecutar suite completa de tests unitarios y funcionales
- [ ] Crear tests de integración para flujos críticos (registro entrada/salida)
- [ ] Implementar tests de carga para operaciones biométricas
- [ ] Agregar tests de seguridad (CSRF, validación inputs)
- [ ] Tests de base de datos con transacciones rollback
- [ ] Cobertura de código mínimo 80%

### Preparación Producción (Prioridad Alta)
- [ ] Configurar variables de entorno ENCRYPTION_KEY
- [ ] Verificar configuración MySQL 8.0+ compatibility
- [ ] Implementar logging centralizado y monitoreo
- [ ] Configurar backup automático de BD
- [ ] Documentar procedimientos de deployment
- [ ] Crear manual de operaciones para soporte

### Mejoras Arquitecturales
- [ ] Implementar inyección de dependencias (DIC)
- [ ] Refactorizar controladores para reducir acoplamiento
- [ ] Agregar middleware para autenticación/autorización
- [ ] Implementar cache (Redis/Memcached) para templates biométricos
- [ ] Optimizar consultas SQL con índices apropiados

### Seguridad y Compliance
- [ ] Auditoría completa de seguridad
- [ ] Implementar rate limiting
- [ ] Encriptación de datos sensibles en tránsito
- [ ] Cumplimiento RGPD para datos personales
- [ ] Logs de auditoría para operaciones críticas
