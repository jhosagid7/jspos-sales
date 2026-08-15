@echo off
setlocal enabledelayedexpansion
set "PROYECTO_DIR=%~dp0"
if "%PROYECTO_DIR:~-1%"=="\" set "PROYECTO_DIR=%PROYECTO_DIR:~0,-1%"
cd /d "%PROYECTO_DIR%"

echo =========================================================
echo   FINALIZANDO INSTALACION Y CONFIGURACION DE JSPOS SALES
echo =========================================================
echo.

call "%PROYECTO_DIR%\installer\post_install.bat" "%PROYECTO_DIR%"
exit /b 0
