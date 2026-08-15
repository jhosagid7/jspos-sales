@echo off
set "PROYECTO_DIR=%~dp0"
if "%PROYECTO_DIR:~-1%"=="\" set "PROYECTO_DIR=%PROYECTO_DIR:~0,-1%"
cd /d "%PROYECTO_DIR%"

echo =========================================================
echo   RECREANDO ENLACE SIMBOLICO PUBLIC/STORAGE (JUNCTION)
echo =========================================================
echo.

if not exist "%PROYECTO_DIR%\storage\app\public" mkdir "%PROYECTO_DIR%\storage\app\public"
if exist "%PROYECTO_DIR%\public\storage" rmdir /s /q "%PROYECTO_DIR%\public\storage"

mklink /J "%PROYECTO_DIR%\public\storage" "%PROYECTO_DIR%\storage\app\public"

echo.
echo ¡Enlace simbolico recreado exitosamente!
pause
