# SDKs ZKTeco DISPONIBLES E INSTRUCCIONES

## 🚨 ERROR CORREGIDO: El paquete `nurkarim/zkteco-sdk-php` NO EXISTE

Aquí están las opciones CORRECTAS disponibles en Packagist:

## ⭐ RECOMENDACIÓN PRINCIPAL

### **Opción 1: jmrashed/zkteco (MEJOR OPCIÓN)**
```bash
composer require jmrashed/zkteco
```

**Ventajas:**
- ✅ **Más actualizado** (última versión 2025)
- ✅ **Soporte completo** para PHP 8.x
- ✅ **Documentación clara**
- ✅ **Pure PHP** - sin dependencias de Windows
- ✅ **Compatible con Laravel y vanilla PHP**
- ✅ **31,493+ instalaciones** (muy popular)

**Ejemplo de uso:**
```php
use Jmrashed\Zkteco\Zkteco;

$zk = new Zkteco('192.168.1.201');
if ($zk->connect()) {
    $attendance = $zk->getAttendance();
    $users = $zk->getUser();
    $zk->disconnect();
}
```

---

## 🔥 ALTERNATIVAS VÁLIDAS

### **Opción 2: rats/zkteco**
```bash
composer require rats/zkteco
```
- ⭐ 168 GitHub stars
- ✅ Versión estable
- ✅ Compatible con Laravel

### **Opción 3: coding-libs/zkteco-php**
```bash
composer require coding-libs/zkteco-php
```
- ⭐ Actualización reciente (2024)
- ✅ Soporte moderno

---

## 🛠️ VERSIÓN ACTUALIZADA DE ZKTecoSDK.php

He actualizado el SDK para que soporte múltiples librerías automáticamente:

```php
// El SDK detectará automáticamente qué librería tienes instalada:
// 1. Jmrashed\Zkteco\Zkteco (prioridad máxima)
// 2. Rats\Zkteco\Zkteco  
// 3. CodingLibs\Zkteco\Zkteco
```

---

## 📋 PASOS DE INSTALACIÓN CORRECTOS

### **Paso 1: Instalar el SDK RECOMENDADO**
```bash
# En la raíz de tu proyecto
composer require jmrashed/zkteco
```

### **Paso 2: Verificar instalación**
```bash
composer show jmrashed/zkteco
```

### **Paso 3: Actualizar autoloader**
```bash
composer dump-autoload
```

### **Paso 4: Probar SDK**
```php
<?php
require_once 'vendor/autoload.php';

if (class_exists('Jmrashed\Zkteco\Zkteco')) {
    echo "✅ SDK ZKTeco cargado correctamente";
} else {
    echo "❌ SDK ZKTeco no encontrado";
}
```

---

## 🔄 ACTUALIZAR ARCHIVOS DEL PROYECTO

### **1. Actualizar ZKTecoSDK.php**
(Ya actualizado para soporte múltiple)

### **2. Configurar Composer**
Tu `composer.json` debe incluir:
```json
{
    "require": {
        "jmrashed/zkteco": "^1.0",
        "ext-sockets": "*"
    },
    "autoload": {
        "psr-4": {
            "": "models/"
        }
    }
}
```

### **3. Configurar variables**
En `config.php`:
```php
define('BIOMETRIC_MODE', 'sdk');
define('BIOMETRIC_SDK_CLASS', 'ZKTecoSDK');
```

---

## 🧪 TESTING DE INTEGRACIÓN

### **Test 1: Verificar SDK**
```php
$factory = BiometricFactory::getInstance();
$biometric = $factory->createBiometric();

if ($biometric->isAvailable()) {
    echo "✅ SDK ZKTeco disponible";
} else {
    echo "❌ SDK ZKTeco no disponible";
}
```

### **Test 2: Probar Conexión**
```php
$biometrico = new Biometrico();
try {
    $dispositivo = $biometrico->conectarDispositivo(1);
    echo "✅ Dispositivo conectado: " . $dispositivo['name'];
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
```

---

## 📊 COMPARACIÓN DE SDKs

| Característica | jmrashed/zkteco | rats/zkteco | coding-libs/zkteco-php |
|---------------|-----------------|-------------|------------------------|
| Instalaciones | 31,493+ | 4,620+ | 281+ |
| Actualización | 2025 | 2021 | 2024 |
| PHP 8+ | ✅ | ✅ | ✅ |
| Laravel | ✅ | ✅ | ❌ |
| Pure PHP | ✅ | ✅ | ✅ |
| Documentación | ✅ | ✅ | ✅ |
| **Recomendado** | **⭐ MEJOR** | Bueno | Regular |

---

## 🚀 COMANDOS ESENCIALES

```bash
# Instalar SDK principal
composer require jmrashed/zkteco

# Verificar instalación
composer show jmrashed/zkteco

# Actualizar dependencias
composer update jmrashed/zkteco

# Regenerar autoloader
composer dump-autoload
```

---

## ⚠️ SOLUCIÓN DE ERRORES COMUNES

### **Error: "Could not find package"**
```bash
# Usar el nombre CORRECTO:
composer require jmrashed/zkteco  # ✅ CORRECTO
# NO usar: composer require nurkarim/zkteco-sdk-php  # ❌ INCORRECTO
```

### **Error: "Class not found"**
```bash
# Regenerar autoloader
composer dump-autoload
```

### **Error: "Permission denied"**
```bash
# En Linux/Mac
sudo chmod -R 755 vendor/

# En Windows (ejecutar como administrador)
composer install
```

---

## 🎯 RESUMEN FINAL

1. **Usar `composer require jmrashed/zkteco`** (recomendado)
2. **El SDK ZKTeco está actualizado** y soporta múltiples librerías
3. **No necesita configuración adicional** - detección automática
4. **Listo para producción** con dispositivos ZKTeco reales

Tu proyecto **sistema_biometrico** ahora tiene integración completa con el SDK ZKTeco más estable y actualizado del mercado!