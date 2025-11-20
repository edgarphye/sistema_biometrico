# Políticas de Justificaciones, Licencias y Asistencia

Este documento recoge las reglas operativas que debe respetar el sistema y el área de Recursos Humanos.

## Justificaciones
- Temporalidad: La entrega de justificaciones de inasistencia es de máximo dos días hábiles desde la fecha de la falta.
- Formato: El formato de incidencia deberá ser requisitado y firmado y deberá anexarse el soporte documental original que justifique la falta (PDF/JPG/PNG).
- Revisión: El área de Recursos Humanos revisará los formatos y la documentación para validar cumplimiento normativo.
- Consecuencia: Transcurrido el periodo sin que la inasistencia esté justificada, se procederá a efectuar el descuento correspondiente.

## Licencias
- Se otorgan a trabajadores para ausentarse justificadamente a solicitud del trabajador, por necesidades del servicio o por dictamen médico del ISSSTE; pueden ser con goce total, medio sueldo, o sin goce de sueldo.
- Las licencias sin goce de sueldo solicitadas por el trabajador deben tramitarse con un mínimo de 15 días de anticipación ante la Unidad Administrativa.
- Permisos especiales (lactancia, cuidados maternos/paternos, permiso de maternidad, licencias por cuidados médicos para atender a hijos con cáncer) podrán disfrutarse siempre y cuando se cumplan los requisitos para su expedición.

### Licencias médicas (resumen)
1. Menos de 1 año de servicio:
   - Hasta 15 días con sueldo íntegro
   - Hasta 15 días adicionales con medio sueldo
2. De 1 a 5 años de antigüedad:
   - Hasta 30 días con sueldo íntegro
   - Hasta 30 días adicionales con medio sueldo
3. De 5 a 10 años de antigüedad:
   - Hasta 45 días con sueldo íntegro
   - Hasta 45 días adicionales con medio sueldo

Si al vencer la licencia con medio sueldo continúa la enfermedad, se concederá licencia sin goce de sueldo hasta por 52 semanas desde el inicio de la incapacidad o desde la primera licencia.

## Retardos y faltas
- Retardo menor: El empleado que se presente transcurridos 10 minutos de tolerancia, pero sin exceder 20 minutos, generará una nota negativa cada 2 retardos al mes.
- Retardo mayor: El empleado que se presente después de los 20 minutos de tolerancia (pero sin exceder 30) generará una nota negativa por cada retardo.
- Falta: Transcurridos 30 minutos de la hora fijada para la iniciación de labores, se considerará falta injustificada y no se concederá salario por la jornada.
- Sanciones:
  - 5 notas negativas por retardos → 1 día de suspensión.
  - 7 suspensiones en el término de un año por impuntualidad → posible terminación de nombramiento tras procedimiento.

Reglas para faltas no consecutivas:
- Hasta 4 faltas en 2 meses → amonestación escrita.
- Hasta 6 faltas en 2 meses → hasta 3 días de suspensión.
- Sanciones mayores (13 a 18 días en seis meses) → 7 días de suspensión.

Si la entrada o salida no están registradas o están en blanco se toma como falta injustificada salvo que exista una licencia que lo justifique.

## Días económicos
- Disponer hasta 3 días económicos de manera continua; deberá transcurrir un mes para volver a disfrutar días económicos.
- Disponer hasta 2 días económicos continuos; deberán transcurrir 15 días calendario para volver a disfrutarlos.
- Disponer 1 día económico; deberá transcurrir una semana calendario para volver a disfrutarlo.
- Esta licencia será autorizada por el jefe inmediato con nivel mínimo de jefe de Departamento.
- El trabajador podrá solicitar la licencia por conducto del Sindicato, con la documentación sindical correspondiente.
- Máximo de días para entregar la licencia: 5 días hábiles.

## Plazos para notificación de licencias médicas
- Justificaciones del día 01 al 15 de cada mes: notificar a más tardar el día 20 del mes.
- Justificaciones del día 16 al 31: notificar a más tardar el día 5 del mes siguiente.

## Requerimientos funcionales del sistema (UI y generación de identificadores)
- El formulario de `Empleados` debe mostrarse en ventana modal.
- Estilos y colores solicitados (hex):
  - Fondo formulario: `#691C32` o `#9F2241`.
  - Sidebar: fondo `#9F2241` con texto blanco.
  - `body.dark-theme` sidebar background: `#235B4E`.
  - Hover `.sidebar .nav-link` → background `#9F2241`.
  - `.sidebar-toggle` background `#9F2241`.
  - `body.dark-theme .sidebar-toggle` background `#235B4C`.
  - `sidebar-expand-fixed` background `#9F2241`.

- Generación del RFC: Debe usar fecha de nacimiento, sexo, nombre(s), apellido paterno, apellido materno y lugar de nacimiento según normas oficiales (SEGOB / RENAPO). El sistema ya incluye funciones RFC/CURP; validar su conformidad.

## Referencias legales
- Consultar Reglamento Interior de la SEP, Manual de Recursos Humanos 2022 y la Ley del ISSSTE.

---

Si deseas, puedo:
- (1) Añadir validaciones front+back para forzar plazos y adjunto de soporte (ya implementé validación básica en backend).
- (2) Actualizar estilos del layout para aplicar la paleta de colores solicitada.
- (3) Generar scripts de ayuda para la gestión de licencias y reportes.

Indica 1/2/3 o dime otra prioridad y la implemento.
