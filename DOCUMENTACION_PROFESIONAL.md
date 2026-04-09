# Documentación Profesional: Sistema de Control Biométrico de Asistencia

## 1. Introducción

### 1.1. Propósito del Documento

Este documento describe de manera integral la arquitectura, el ciclo de vida y el proceso de desarrollo del **Sistema de Control Biométrico de Asistencia**. El objetivo es proporcionar una visión clara y profesional del proyecto, adecuada para presentaciones técnicas, revisiones de arquitectura y para la incorporación de nuevos miembros al equipo de desarrollo.

### 1.2. Alcance del Proyecto

El sistema está diseñado para gestionar el control de asistencia de empleados utilizando datos biométricos. Sus funcionalidades principales incluyen:

*   Registro de entradas y salidas de personal.
*   Cálculo y gestión de retardos según políticas internas.
*   Aplicación automática de sanciones (notas malas) por acumulación de retardos.
*   Gestión de justificaciones, comisiones y ausencias.
*   Generación de reportes detallados de asistencia.
*   Administración de empleados, horarios y dispositivos biométricos.

El sistema busca automatizar y estandarizar el seguimiento de la asistencia, reducir errores manuales y proporcionar datos fiables para la toma de decisiones en el área de Recursos Humanos.

---

## 2. Arquitectura del Software

El sistema está construido sobre una arquitectura robusta y modular que separa las responsabilidades, facilitando su mantenimiento y escalabilidad.

### 2.1. Patrón Arquitectónico: MVC con Capa de Servicios

La arquitectura del sistema sigue un patrón **Modelo-Vista-Controlador (MVC)**, enriquecido con una **Capa de Servicios** para encapsular la lógica de negocio compleja.

*   **Modelo (Model):** Representa la capa de acceso a datos. Es responsable de interactuar directamente con la base de datos para leer, escribir, actualizar y eliminar información. No contiene lógica de negocio.
    *   *Ubicación:* `models/`
    *   *Ejemplos:* `Empleado.php`, `Asistencia.php`, `Retardo.php`.

*   **Vista (View):** Es la capa de presentación, responsable de renderizar la interfaz de usuario en formato HTML. Las vistas son plantillas que reciben datos de los controladores para mostrarlos al usuario.
    *   *Ubicación:* `views/`
    *   *Ejemplos:* `views/empleados/index.php`, `views/asistencia/reporte.php`.

*   **Controlador (Controller):** Actúa como intermediario entre el Modelo y la Vista. Recibe las peticiones del usuario, invoca a la capa de servicios para procesar la lógica de negocio y finalmente selecciona una vista para mostrar los resultados.
    *   *Ubicación:* `controllers/`
    *   *Ejemplos:* `AsistenciaController.php`, `EmpleadoController.php`.

*   **Servicio (Service):** Esta capa adicional contiene la lógica de negocio del sistema. Orquesta las operaciones entre diferentes modelos y aplica las reglas definidas en los requerimientos, como el cálculo de retardos y la aplicación de sanciones. Desacopla la lógica de negocio de los controladores, haciendo el sistema más limpio y fácil de probar.
    *   *Ubicación:* `services/`
    *   *Ejemplos:* `AsistenciaService.php`.

![Arquitectura MVC con Capa de Servicios](https://i.imgur.com/3Vz1J7q.png)

### 2.2. Flujo de Datos

Un flujo de petición típico en el sistema sigue estos pasos:
1.  El usuario realiza una acción (ej. registrar su huella en un dispositivo).
2.  La petición llega al `index.php`, que actúa como punto de entrada y enrutador.
3.  El enrutador invoca al método correspondiente en un **Controlador** (ej. `AsistenciaController->registrarEntrada()`).
4.  El Controlador llama a un **Servicio** (ej. `AsistenciaService->registrarEntrada()`) para ejecutar la lógica de negocio.
5.  El Servicio utiliza uno o más **Modelos** (ej. `Asistencia.php`, `Retardo.php`, `Sancion.php`) para interactuar con la base de datos.
6.  El Servicio procesa los datos y devuelve el resultado al Controlador.
7.  El Controlador selecciona una **Vista** y le pasa los datos necesarios para renderizar la respuesta (generalmente en formato JSON para operaciones de API o HTML para vistas de usuario).

### 2.3. Diseño de la Base de Datos

El esquema de la base de datos es relacional (probablemente MySQL o MariaDB) y está diseñado para soportar las funcionalidades del sistema. Las tablas principales incluyen:

*   `empleados`: Almacena la información de los empleados.
*   `asistencia`: Registra cada evento de entrada y salida.
*   `horarios`: Define los horarios laborales, incluyendo tolerancias.
*   `retardos`: Guarda un registro por cada retardo, su tipo y si fue justificado.
*   `sanciones`: Almacena las sanciones aplicadas, como las "notas malas".
*   `justificaciones`, `comisiones`, `ausencias`: Gestionan las excepciones a la asistencia regular.
*   `dispositivos_biometricos`: Administra los dispositivos de registro.

El archivo `database.sql` contiene el script para la creación inicial de la estructura de la base de datos.

### 2.4. Descripción de Componentes Principales

*   `controllers/`: Contiene los controladores que gestionan el flujo de la aplicación.
*   `models/`: Contiene las clases que interactúan con la base de datos.
*   `views/`: Contiene las plantillas HTML para la interfaz de usuario.
*   `services/`: Alberga la lógica de negocio central del sistema.
*   `helpers/`: Incluye clases de utilidad para tareas comunes como cifrado (`Encryption.php`) o validaciones (`RfcCurpHelper.php`).
*   `vendor/`: Gestionado por Composer, contiene las dependencias de terceros (ej. PHPUnit, DomPDF).
*   `tests/`: Contiene las pruebas unitarias y funcionales del proyecto.
*   `migrations/`: Almacena scripts para realizar cambios incrementales en la base de datos.
*   `public/`: Directorio accesible desde la web para recursos estáticos como CSS y JavaScript.

---

## 3. Ciclo de Vida del Software

El desarrollo del proyecto se alinea con un **modelo de ciclo de vida iterativo e incremental**, similar a las metodologías ágiles. Este enfoque permite entregar valor de forma temprana y continua, adaptándose a los cambios y refinando el producto en cada ciclo.

Las fases del ciclo de vida se manifiestan de la siguiente manera:

### 3.1. Fase 1: Planificación y Análisis de Requisitos
En esta fase se definen los objetivos y el alcance del proyecto.
*   **Artefactos en el proyecto:**
    *   `00_LECTURA_PRIMERO.md` y `LEEME_PRIMERO.txt`: Documentos que establecen las directrices iniciales.
    *   `DOCUMENTACION_PROYECTO_FINAL.docx` y `diagrama de flujo de justificacion.pptx`: Evidencia del análisis y diseño de funcionalidades específicas.
    *   El pseudocódigo proporcionado para la gestión de retardos es un claro ejemplo de un requisito funcional detallado.

### 3.2. Fase 2: Diseño de la Arquitectura y el Sistema
Se define la estructura técnica del sistema, el diseño de la base de datos y los componentes principales.
*   **Artefactos en el proyecto:**
    *   La estructura de directorios (MVC + Servicios) es el resultado principal del diseño arquitectónico.
    *   `database.sql`: Define el diseño inicial de la base de datos.
    *   `config.php`: Centraliza la configuración de la aplicación, como las credenciales de la base de datos.

### 3.3. Fase 3: Implementación (Codificación)
Se traduce el diseño a código funcional, siguiendo las convenciones y estándares establecidos.
*   **Artefactos en el proyecto:**
    *   Todo el código fuente en PHP dentro de `controllers/`, `models/`, `services/`, etc.

### 3.4. Fase 4: Pruebas y Verificación
Se asegura que el código cumpla con los requisitos y esté libre de errores.
*   **Artefactos en el proyecto:**
    *   `tests/`: Directorio que contiene los casos de prueba.
    *   `phpunit.xml`: Archivo de configuración para el framework de pruebas PHPUnit.
    *   `tests/test_retardos_flow.php`: Ejemplo de una prueba funcional que valida un flujo de negocio completo.
    *   `.github/workflows/phpunit.yml`: Define un flujo de trabajo de Integración Continua que ejecuta las pruebas automáticamente en cada cambio.

### 3.5. Fase 5: Despliegue
Se pone el sistema en producción para que los usuarios finales puedan utilizarlo.
*   **Artefactos en el proyecto:**
    *   `DEPLOYMENT.md`: Documento que probablemente contiene las instrucciones para el despliegue.
    *   `setup_production.bat`: Script que automatiza parte del proceso de configuración en un entorno de producción.

### 3.6. Fase 6: Mantenimiento y Evolución
Se corrigen errores, se realizan actualizaciones y se añaden nuevas funcionalidades de forma continua.
*   **Artefactos en el proyecto:**
    *   `migrations/`: Los scripts SQL en este directorio (`20251119_add_sanciones_modificados.sql`, etc.) son evidencia clara de la evolución y mantenimiento de la base deatos.
    *   `logs/`: Los archivos de log (`error.log`, `debug_route.log`) son cruciales para monitorizar el sistema y diagnosticar problemas en producción.
    *   El control de versiones con Git permite gestionar los cambios a lo largo del tiempo.

---

## 4. Proceso de Desarrollo y Herramientas

El proyecto utiliza un conjunto de herramientas y prácticas modernas que garantizan la calidad y eficiencia del desarrollo.

*   **Control de Versiones (Git y GitHub):** El código fuente está gestionado con Git, y el repositorio centralizado probablemente reside en GitHub. Esto permite el trabajo colaborativo, el seguimiento de cambios y la gestión de ramas para nuevas funcionalidades.

*   **Gestión de Dependencias (Composer):** El archivo `composer.json` indica el uso de Composer para gestionar las librerías de terceros, como `PHPUnit` para las pruebas o `DomPDF` para la generación de PDFs.

*   **Integración Continua (CI):** El archivo `.github/workflows/phpunit.yml` demuestra la implementación de un flujo de trabajo de CI con GitHub Actions. Este proceso ejecuta automáticamente las pruebas en cada `push` o `pull request`, asegurando que los nuevos cambios no rompan la funcionalidad existente.

*   **Estrategia de Pruebas (PHPUnit):** El proyecto cuenta con un conjunto de pruebas unitarias y funcionales en el directorio `tests/`. Esto es fundamental para:
    *   Verificar que cada componente funciona como se espera de forma aislada.
    *   Validar flujos de negocio completos (ej. el proceso de retardo y sanción).
    *   Facilitar la refactorización segura del código.

---

## 5. Funcionalidades Clave Implementadas

Basado en el análisis, se ha mejorado y completado la funcionalidad del `EmpleadoController` y la lógica de negocio asociada, cumpliendo con los requisitos del pseudocódigo.

### 5.1. Gestión de Retardos y Sanciones
Se implementó la lógica completa para el manejo de retardos:
1.  **Cálculo de Retardo:** Al registrar una entrada, `AsistenciaService` calcula los minutos de retardo.
2.  **Clasificación:** El retardo se clasifica como "menor" (11-20 min) or "mayor" (21-30 min).
3.  **Registro:** Se guarda un registro en la tabla `retardos`.
4.  **Validación Quincenal:** El sistema verifica cuántos retardos ha acumulado el empleado en la quincena actual.
5.  **Aplicación de Sanción:** Si el empleado tiene 1 o más retardos, se le aplica automáticamente una "nota mala" (una entrada en la tabla `sanciones`).
6.  **Notificación:** Se registra un evento para notificar al empleado (actualmente a través de logs, pero escalable para enviar correos electrónicos).

### 5.2. Refactorización del `EmpleadoController`
*   Se reestructuró el `EmpleadoController` para seguir una correcta estructura de clases en PHP.
*   Se mejoró el método `resumenCompleto($id)`, que ahora devuelve un JSON con un perfil completo del empleado, incluyendo:
    *   Datos personales.
    *   Estadísticas de asistencia.
    *   Detalle y clasificación de retardos.
    *   Historial de sanciones del año en curso.
    *   Alertas automáticas sobre acumulaciones de retardos o justificaciones pendientes.

---

## 6. Conclusión

El Sistema de Control Biométrico de Asistencia es un proyecto de software robusto, desarrollado siguiendo prácticas profesionales y una arquitectura modular. El uso de un ciclo de vida iterativo, junto con herramientas como Git, Composer y PHPUnit, garantiza un producto de alta calidad, mantenible y escalable.

Las recientes mejoras han completado funcionalidades críticas en la lógica de negocio y han fortalecido la estructura del código, alineándolo con los requisitos definidos y preparando el sistema para futuras expansiones.
