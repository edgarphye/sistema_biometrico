# Sistema Biométrico - Control de Asistencia

## Stack
- **PHP 8.4** - Backend (MVC custom, sin framework)
- **MariaDB/MySQL** - Base de datos
- **Bootstrap 5.3** - Frontend
- **jQuery 3.7** - DOM/API calls
- **Font Awesome 4.7** - Iconos

## Database (sistema_biometrico)
- Host: localhost / User: root / Pass: root
- MCP server activo: `mariadb` para consultas SQL read-only

## Key tables
- `empleados` - Catálogo de empleados
- `usuarios` - Usuarios del sistema (vinculados a empleados via empleado_id)
- `asistencia` - Registro diario de asistencia
- `retardos` - Retardos registrados
- `comisiones` - Comisiones
- `justificaciones` - Justificaciones de faltas/retardos
- `vacaciones` - Solicitudes de vacaciones
- `validaciones_jefe` - Validaciones de jefe inmediato
- `catalogos_mandos` - Relación jefe-subordinado por área
- `notas_malas` - Sanciones por retardos acumulados
- `sanciones` - Registro de sanciones
