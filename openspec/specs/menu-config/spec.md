# menu-config

## Purpose
Permitir la configuración personalizada de visibilidad de elementos del menú por usuario, con persistencia individual y una interfaz de administración con checkboxes.


### Requirement: Configuración de menú por usuario
El sistema SHALL permitir configurar visibilidad de elementos del menú por usuario mediante tabla menu_config con usuario_id, menu_path, visible.

#### Scenario: Ocultar elemento del menú
- **WHEN** se configura un elemento del menú como no visible para un usuario
- **THEN** el elemento no se renderiza en el sidebar de ese usuario

### Requirement: Persistencia por usuario
El sistema SHALL persistir la configuración de menú por usuario, no por rol.

#### Scenario: Configuración específica
- **WHEN** dos usuarios del mismo rol tienen configuraciones diferentes
- **THEN** cada uno ve su menú personalizado

### Requirement: Interfaz de administración
El sistema SHALL proveer interfaz para administrar menú por usuario con checkboxes.

#### Scenario: Administración de menú
- **WHEN** un admin accede a /menu-config
- **THEN** puede ver y modificar la configuración de menú de cada usuario
