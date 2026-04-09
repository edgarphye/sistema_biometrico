# 📚 API Documentation - Sistema Biométrico

**Versión**: 1.0.0  
**Base URL**: `https://api.sistema-biometrico.com`  
**Formato**: RESTful JSON  

---

## 🔐 **Autenticación**

### **POST /api/v1/auth/login**
Autenticar usuario y obtener token.

**Request Body:**
```json
{
    "username": "admin",
    "password": "password123"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "user": {
            "id": 1,
            "username": "admin",
            "rol": "admin",
            "empleado_id": null
        },
        "expires_in": 3600
    }
}
```

**Response (401 Unauthorized):**
```json
{
    "success": false,
    "error": "Credenciales inválidas"
}
```

### **POST /api/v1/auth/2fa/verify**
Verificar código de autenticación de dos factores.

**Request Body:**
```json
{
    "user_id": 1,
    "code": "123456"
}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "verified": true
    }
}
```

### **POST /api/v1/auth/logout**
Cerrar sesión del usuario.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Sesión cerrada exitosamente"
}
```

---

## 👥 **Empleados**

### **GET /api/v1/empleados**
Obtener lista de empleados con paginación y filtros.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (integer, optional): Número de página (default: 1)
- `limit` (integer, optional): Registros por página (default: 10, max: 100)
- `search` (string, optional): Término de búsqueda
- `area` (string, optional): Filtro por área
- `activo` (boolean, optional): Filtro por estado

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "empleados": [
            {
                "id": 1,
                "nombre": "Juan",
                "apellido": "Pérez",
                "rfc": "PEJU800101HDFXXX01",
                "email": "juan.perez@empresa.com",
                "telefono": "5551234567",
                "area": "TI",
                "puesto": "Desarrollador",
                "activo": 1,
                "fecha_registro": "2025-01-01T00:00:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 10,
            "total": 25,
            "total_pages": 3
        }
    }
}
```

### **GET /api/v1/empleados/{id}**
Obtener detalles de un empleado específico.

**Headers:**
```
Authorization: Bearer {token}
```

**Path Parameters:**
- `id` (integer): ID del empleado

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "nombre": "Juan",
        "apellido": "Pérez",
        "rfc": "PEJU800101HDFXXX01",
        "email": "juan.perez@empresa.com",
        "telefono": "5551234567",
        "area": "TI",
        "puesto": "Desarrollador",
        "activo": 1,
        "fecha_registro": "2025-01-01T00:00:00Z",
        "asistencia_resumen": {
            "total_dias": 22,
            "dias_puntuales": 18,
            "dias_retardo": 4
        }
    }
}
```

### **POST /api/v1/empleados**
Crear un nuevo empleado.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "nombre": "María",
    "apellido": "González",
    "rfc": "GOMA750123MDFXXX02",
    "email": "maria.gonzalez@empresa.com",
    "telefono": "5559876543",
    "area": "RH",
    "puesto": "Gerente"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "data": {
        "id": 2,
        "message": "Empleado creado exitosamente"
    }
}
```

### **PUT /api/v1/empleados/{id}**
Actualizar información de un empleado.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "nombre": "María",
    "apellido": "González López",
    "email": "maria.gonzalez.lopez@empresa.com",
    "telefono": "5559876543"
}
```

### **DELETE /api/v1/empleados/{id}**
Eliminar un empleado.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "message": "Empleado eliminado exitosamente"
}
```

---

## ⏰ **Asistencia**

### **GET /api/v1/asistencia**
Obtener registros de asistencia.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `empleado_id` (integer, optional): ID del empleado
- `fecha_inicio` (date, optional): Fecha inicial (YYYY-MM-DD)
- `fecha_fin` (date, optional): Fecha final (YYYY-MM-DD)
- `page` (integer, optional): Número de página
- `limit` (integer, optional): Registros por página

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "asistencia": [
            {
                "id": 1,
                "empleado_id": 1,
                "empleado_nombre": "Juan Pérez",
                "fecha": "2025-12-01",
                "hora_entrada": "09:15:00",
                "hora_salida": "17:30:00",
                "minutos_trabajados": 495,
                "tipo_asistencia": "retardo_menor",
                "dispositivo_entrada": "BIO001",
                "dispositivo_salida": "BIO001"
            }
        ],
        "estadisticas": {
            "total_registros": 22,
            "dias_puntuales": 18,
            "dias_retardo_menor": 3,
            "dias_retardo_mayor": 1
        }
    }
}
```

### **POST /api/v1/asistencia/entrada**
Registrar entrada de empleado.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "empleado_id": 1,
    "dispositivo_id": "BIO001",
    "metodo_verificacion": "huella",
    "timestamp": "2025-12-01T09:15:00Z"
}
```

**Response (201 Created):**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "empleado_id": 1,
        "fecha": "2025-12-01",
        "hora_entrada": "09:15:00",
        "tipo_asistencia": "retardo_menor"
    }
}
```

### **POST /api/v1/asistencia/salida**
Registrar salida de empleado.

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "empleado_id": 1,
    "dispositivo_id": "BIO001",
    "timestamp": "2025-12-01T17:30:00Z"
}
```

---

## 📊 **Reportes**

### **GET /api/v1/reportes/asistencia**
Generar reporte de asistencia.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `empleado_id` (integer, optional): ID del empleado
- `fecha_inicio` (date, required): Fecha inicial
- `fecha_fin` (date, required): Fecha final
- `formato` (string, optional): Formato de exportación (json, excel, pdf)

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "periodo": {
            "inicio": "2025-12-01",
            "fin": "2025-12-31"
        },
        "resumen": {
            "total_dias_laborales": 22,
            "dias_asistidos": 21,
            "dias_ausentes": 1,
            "horas_trabajadas": 168.5,
            "promedio_horas_diarias": 8.02
        },
        "detalles": [
            {
                "empleado": {
                    "id": 1,
                    "nombre": "Juan Pérez"
                },
                "estadisticas": {
                    "dias_puntuales": 18,
                    "dias_retardo_menor": 2,
                    "dias_retardo_mayor": 1,
                    "total_horas": 168.5
                }
            }
        ]
    }
}
```

### **GET /api/v1/reportes/retardos**
Generar reporte de retardos.

### **GET /api/v1/reportes/empleados**
Generar reporte general de empleados.

---

## 📅 **Días Económicos**

### **GET /api/v1/dias-economicos**
Obtener solicitudes de días económicos.

**Headers:**
```
Authorization: Bearer {token}
```

**Response (200 OK):**
```json
{
    "success": true,
    "data": {
        "dias_economicos": [
            {
                "id": 1,
                "empleado_id": 1,
                "empleado_nombre": "Juan Pérez",
                "fecha": "2025-12-15",
                "motivo": "Asuntos personales",
                "estatus": "aprobado",
                "fecha_solicitud": "2025-12-10T10:00:00Z",
                "fecha_aprobacion": "2025-12-11T09:00:00Z"
            }
        ],
        "resumen": {
            "total_solicitados": 5,
            "aprobados": 3,
            "pendientes": 1,
            "rechazados": 1
        }
    }
}
```

### **POST /api/v1/dias-economicos**
Solicitar día económico.

**Request Body:**
```json
{
    "empleado_id": 1,
    "fecha": "2025-12-20",
    "motivo": "Cita médica"
}
```

---

## 📱 **Dispositivos Biométricos**

### **GET /api/v1/dispositivos**
Obtener lista de dispositivos biométricos.

**Response (200 OK):**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "nombre": "Dispositivo Principal",
            "ip": "192.168.1.100",
            "puerto": 4370,
            "modelo": "TA100C",
            "ubicacion": "Entrada Principal",
            "estatus": "activo",
            "ultimo_heartbeat": "2025-12-01T17:30:00Z"
        }
    ]
}
```

### **POST /api/v1/dispositivos/{id}/sync**
Sincronizar dispositivo con empleados.

**Request Body:**
```json
{
    "accion": "sincronizar_empleados"
}
```

---

## 🔒 **Seguridad**

### **Headers de Autenticación**
Todas las solicitudes (excepto login y register) deben incluir:
```
Authorization: Bearer {token}
Content-Type: application/json
```

### **Rate Limiting**
- **Límite**: 100 solicitudes por minuto por IP
- **Headers**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

### **Códigos de Error HTTP**

| Código | Descripción | Ejemplo |
|--------|-------------|---------|
| 200 | OK | Solicitud exitosa |
| 201 | Created | Recurso creado |
| 400 | Bad Request | Parámetros inválidos |
| 401 | Unauthorized | No autenticado |
| 403 | Forbidden | Sin permisos |
| 404 | Not Found | Recurso no encontrado |
| 422 | Unprocessable Entity | Error de validación |
| 429 | Too Many Requests | Excedido rate limit |
| 500 | Internal Server Error | Error del servidor |

### **Formato de Error**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "El RFC es inválido",
        "details": {
            "field": "rfc",
            "reason": "formato_invalido"
        }
    }
}
```

---

## 📝 **Ejemplos de Uso**

### **JavaScript/Fetch**
```javascript
// Login
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        username: 'admin',
        password: 'password123'
    })
});

const data = await response.json();
const token = data.data.token;

// Obtener empleados
const empleadosResponse = await fetch('/api/v1/empleados', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
});
```

### **cURL**
```bash
# Login
curl -X POST https://api.sistema-biometrico.com/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password123"}'

# Obtener empleados
curl -X GET https://api.sistema-biometrico.com/api/v1/empleados \
  -H "Authorization: Bearer {token}"
```

---

## 🔄 **Versionamiento**

La API utiliza versionado semántico:
- `v1.0.0` - Versión actual estable
- Cambios no compatibles incrementan versión mayor (v2.0.0)
- Nuevas funcionalidades incrementan versión menor (v1.1.0)
- Correcciones incrementan versión patch (v1.0.1)

---

## 📞 **Soporte**

Para consultas técnicas sobre la API:
- **Email**: api-support@sistema-biometrico.com
- **Documentation**: https://docs.sistema-biometrico.com/api
- **Status Page**: https://status.sistema-biometrico.com

---

*Última actualización: 2025-12-06*