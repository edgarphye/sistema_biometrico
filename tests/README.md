# Pruebas del Sistema Biométrico

Este directorio contiene las pruebas unitarias y de integración del sistema.

## Requisitos

- PHP 7.4 o superior
- Composer
- PHPUnit (instalado vía `composer install`)

## Ejecución de Pruebas

Para ejecutar todas las pruebas:
`vendor/bin/phpunit`

Para ejecutar las pruebas de ZKTeco:
`tests\run_zk_tests.bat`

Para ejecutar pruebas de integración (Flujo completo):
`tests\run_integration_tests.bat`
