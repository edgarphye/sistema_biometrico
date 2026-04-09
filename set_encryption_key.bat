@echo off
REM Script para generar y persistir ENCRYPTION_KEY (Base64, 32 bytes) en Windows

for /f "usebackq delims=" %%i in (`powershell -NoProfile -Command "$b = New-Object byte[] 32; [System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($b); [Convert]::ToBase64String($b)"`) do set ENCRYPTION_KEY=%%i

echo Clave generada para ENCRYPTION_KEY:
echo %ENCRYPTION_KEY%

REM Persistir para el usuario actual
setx ENCRYPTION_KEY "%ENCRYPTION_KEY%" >nul

echo.
echo ENCRYPTION_KEY guardada. Cierra y reabre la terminal para que se aplique.
echo Para uso inmediato en PowerShell puedes ejecutar:
echo   $env:ENCRYPTION_KEY = '%ENCRYPTION_KEY%'
echo.
pause
