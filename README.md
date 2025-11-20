# Sistema Biométrico de Control de Asistencia

Sistema completo para el control de asistencia de empleados utilizando biometría (huella dactilar y reconocimiento facial) con integración de SDK CKTeco.

## 🚀 Características

- **Biometría Avanzada**: Soporte para huella dactilar y reconocimiento facial
- **Control de Asistencia**: Registro automático de entrada/salida
- **Cálculos Automáticos**: Retardos, comisiones y ausencias
- **Interfaz en Español**: UI completa y amigable
- **Base de Datos MySQL**: Almacenamiento seguro y eficiente
- **Arquitectura MVC**: Código organizado y mantenible

## 📋 Requisitos

- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB 10.0+
- Servidor web (Apache/Nginx)
- SDK CKTeco (para producción)

## 🛠️ Instalación

### 1. Clonar/Configurar el proyecto
```bash
# El proyecto ya está en c:/tools/nginx/html/sistema_biometrico
cd c:/tools/nginx/html/sistema_biometrico
```

### 2. Configurar Base de Datos

**Opción A: Configuración Automática (Recomendada)**
```bash
php setup.php
```

**Opción B: Configuración Manual**
```sql
-- Ejecutar en MySQL
CREATE DATABASE sistema_biometrico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SOURCE database.sql;
```

### 3. Configurar Credenciales
Edita `config.php` si es necesario:
```php
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 4. Iniciar Servidor
```bash
php -S localhost:8000
```

### 5. Acceder al Sistema
Abre en tu navegador: http://localhost:8000

## 📊 Funcionalidades

### 👥 Gestión de Empleados
- Crear, editar, eliminar empleados
- Información completa: RFC, CURP, área, jerarquía
- Almacenamiento de datos biométricos

### 🔐 Sistema Biométrico
- Integración con 10 dispositivos CKTeco
- Verificación automática de identidad
- Soporte para huella y cara

### ⏰ Control de Asistencia
- Registro automático de entrada/salida
- Cálculo de retardos con tolerancia de 9 minutos
- Clasificación: menor (<30 min) / mayor (>30 min)

### 📈 Reportes y Cálculos
- **Retardos**: Seguimiento mensual con justificación
- **Comisiones**: Control de montos y vencimientos
- **Ausencias**: Registro por tipo con justificación

## 🏗️ Arquitectura

```
sistema_biometrico/
├── config.php              # Configuraciones globales
├── database.sql            # Esquema de BD
├── index.php               # Router principal
├── setup.php               # Instalador automático
├── models/                 # Modelos de datos
│   ├── Database.php        # Conexión BD
│   ├── Empleado.php        # Gestión empleados
│   ├── Asistencia.php      # Control asistencia
│   ├── Retardo.php         # Cálculos retardos
│   ├── Comision.php        # Gestión comisiones
│   ├── Ausencia.php        # Control ausencias
│   └── Biometrico.php      # Integración biométrica
├── controllers/            # Lógica de negocio
│   ├── EmpleadoController.php
│   └── AsistenciaController.php
└── views/                  # Interfaz de usuario
    ├── layout.php          # Plantilla base
    └── empleados/          # Vistas empleados
        ├── index.php
        ├── create.php
        └── show.php
```

## 🔧 Configuración Avanzada

### Problemas de Conexión MySQL 8.0+
Si encuentras errores de autenticación, ejecuta en MySQL:
```sql
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'tu_password';
FLUSH PRIVILEGES;
```

### Configuración de Dispositivos Biométricos
Para producción, configura la API de CKTeco en `config.php`:
```php
define('BIOMETRIC_API_URL', 'https://tu-api-ckteco.com');
define('BIOMETRIC_API_KEY', 'tu_api_key');
```

## 📝 API Endpoints

- `GET /empleados` - Listar empleados
- `POST /empleados/create` - Crear empleado
- `GET /empleados/{id}` - Ver empleado
- `POST /asistencia/entrada` - Registrar entrada
- `POST /asistencia/salida` - Registrar salida
- `GET /biometricos/estado` - Estado dispositivos

## 🔒 Seguridad

- Validación de inputs
- Encriptación de datos sensibles
- Conexiones seguras a BD
- Autenticación de usuarios (próximamente)

## 📈 Próximas Funcionalidades

- [ ] Autenticación de usuarios
- [ ] Reportes avanzados en PDF
- [ ] Notificaciones por email
- [ ] API REST completa
- [ ] Dashboard administrativo
- [ ] Backup automático

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 🆘 Soporte

Para soporte técnico o preguntas:
- Revisa la documentación
- Verifica los logs de error
- Contacta al equipo de desarrollo

---

**Desarrollado con ❤️ para optimizar el control de asistencia laboral**
