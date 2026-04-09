@echo off
echo ========================================================
echo Ejecutando pruebas unitarias para ZKTecoMappingManager
echo ========================================================
echo.

call vendor\bin\phpunit tests\Unit\ZKTecoMappingManagerTest.php --colors=always

pause