@echo off
setlocal enabledelayedexpansion
echo ===================================================
echo   CONFIGURACION Y POST-INSTALACION DE JSPOS SALES
echo ===================================================
echo.

set "PROYECTO_DIR=C:\laragon\www\jspos-sales"
if not "%~1"=="" set "PROYECTO_DIR=%~1"

cd /d "%PROYECTO_DIR%"

powershell -NoProfile -ExecutionPolicy Bypass -File "%PROYECTO_DIR%\installer\setup.ps1" -ProjectDir "%PROYECTO_DIR%"

if exist "%PROYECTO_DIR%\installer\open_browser.bat" (
    call "%PROYECTO_DIR%\installer\open_browser.bat"
)

echo.
echo ===================================================
echo ¡POST-INSTALACION COMPLETADA CON EXITO!
echo ===================================================
exit /b 0
