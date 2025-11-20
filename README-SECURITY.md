# Seguridad - Recomendaciones para despliegue

Este documento explica los pasos mínimos para asegurar el manejo de credenciales y plantillas biométricas antes de poner en producción `sistema_biometrico`.

1) Variables de entorno

- Configure las siguientes variables de entorno en el servidor web (no en el repositorio):
  - `DB_HOST` (ej: `localhost`)
  - `DB_USER` (ej: `sistema_user`)
  - `DB_PASS` (contraseña segura)
  - `DB_NAME` (ej: `sistema_biometrico`)
  - `ENCRYPTION_KEY` (clave secreta fuerte para cifrar plantillas biométricas)
  - `BIOMETRIC_API_KEY` (clave API del proveedor biométrico)
  - `BIOMETRIC_API_URL` (opcional)
  - `BASE_URL`, `APP_NAME`, `APP_VERSION` (opcional)

2) ENCRYPTION_KEY

- Debe ser una cadena aleatoria de al menos 32 caracteres. Puede generarla con OpenSSL:

```bat
:: En Windows (PowerShell):
php -r "echo bin2hex(random_bytes(32));"
```

- Establezca esa cadena como `ENCRYPTION_KEY` en las variables de entorno del servidor.

3) No registrar secretos

- No deje `ENCRYPTION_KEY`, `DB_PASS` o `BIOMETRIC_API_KEY` en el código fuente ni en `config.php`.
- Use mecanismos de configuración seguros del proveedor (Azure Key Vault, AWS Secrets Manager, HashiCorp Vault) si están disponibles.

4) Backups y retención

- Encripte backups de la base de datos si contienen plantillas biométricas.
- Defina políticas de retención y purgado para `huella_dactilar` si ya no son necesarias.

5) TLS y verificación del SDK

- Asegure las conexiones salientes al API biométrico con TLS y validación de certificados.
- En `models/biometric/BiometricSDK.php` puede considerar usar una librería HTTP (Guzzle) para controlar timeouts y retries.

6) Acceso y registros

- Limite el acceso a la base de datos y al directorio `uploads/fotos_empleados/` solo al proceso web.
- Configure rotación de logs y acceso restringido a archivos de log.

7) Pruebas

- Antes de producción, ejecute tests que verifiquen:
  - Creación/lectura/actualización de empleados con huella (asegurar cifrado y descifrado correctos).
  - Flujo de sincronización con dispositivos (usar `BiometricSimulation` en pruebas).

---

Si quieres, puedo:
- A) Actualizar `config.php` para leer todas las credenciales de `getenv()` (ya aplicable parcialmente).
- B) Añadir un script `env.example` y un `deploy` README con pasos en Windows y Linux.
- C) Integrar uso de `vlucas/phpdotenv` para desarrollo local con `.env`.

Elige A/B/C o dime otra preferencia y lo implemento.