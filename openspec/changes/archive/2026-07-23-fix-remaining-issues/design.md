## Context

El sistema biométrico de control de asistencia tiene 280+ empleados activos pero con datos incompletos:
- 366 empleados (80%) sin jefe_directo_id asignado
- 262 empleados (57%) sin zkteo_id (no vinculados a dispositivos)
- 604 retardos (100%) sin horario_id
- 12,563 registros de asistencia (88%) sin validación RH
- 0 huellas dactilares registradas
- 0 fotos de empleados
- Controllers con require paths rotos y problemas de autenticación

## Goals / Non-Goals

**Goals:**
- Asignar jefes directos a empleados usando catálogo de mandos existente
- Vincular empleados con dispositivos biométricos ZKTeco
- Migrar retardos existentes con horario_id correcto
- Validar asistencia pendiente por RH
- Corregir controllers restantes (MenuConfigController, paths)
- Crear flujo para captura de huellas y fotos

**Non-Goals:**
- Modificar estructura de tablas existentes
- Cambiar lógica de cálculo de retardos
- Implementar nuevos dispositivos biométricos
- Modificar reglas de negocio AEFCM

## Decisions

### 1. Asignación de jefes por área
**Decisión**: Usar `catalogos_mandos` para vincular empleados sin jefe_directo_id
**Alternativa**: Asignación manual por RH
**Razón**: Ya existe el catálogo, es automático y consistente

### 2. Vinculación biométrica
**Decisión**: Usar zkteo_id como identificador único del dispositivo
**Alternativa**: Crear nueva tabla de relación empleado-dispositivo
**Razón**: zkteo_id ya existe en schema, es simpler

### 3. Migración de retardos
**Decisión**: Asignar horario_id basado en fecha del retardo y horario del empleado
**Alternativa**: Dejar horario_id como NULL
**Razón**: Permite cálculos correctos de retardos futuros

### 4. Validación masiva
**Decisión**: Script SQL para marcar asistencia como validada
**Alternativa**: Interfaz web para validación uno por uno
**Razón**: 12,563 registros es inviable manualmente

## Risks / Trade-offs

- **[Pérdida de datos]** → Mitigación: Backup antes de cada migración
- **[Rendimiento]** → Mitigación: Ejecutar migraciones en horario no laboral
- **[Integridad]** → Mitigación: Transacciones SQL con rollback
- **[Dependencia ZKTeco]** → Mitigación: Verificar compatibilidad de firmware

## Migration Plan

1. Backup completo de BD
2. Ejecutar scripts de migración en orden:
   a. Asignación de jefes directos
   b. Vinculación biométrica
   c. Migración de retardos
   d. Validación de asistencia
3. Verificar integridad post-migración
4. Deploy de controllers corregidos
5. Pruebas de regresión

## Open Questions

- ¿Cuántos dispositivos ZKTeco están configurados?
- ¿Hay empleados que cambiaron de área y necesitan reasignación?
- ¿Es necesario mantener historial de cambios de jefe?
