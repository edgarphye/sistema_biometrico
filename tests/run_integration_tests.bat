@echo off
echo ========================================================
echo Ejecutando pruebas de integracion (Flujo ZKTeco)
echo ========================================================
echo.

call vendor\bin\phpunit tests\Integration\ZKTecoFlowTest.php --colors=always

pause