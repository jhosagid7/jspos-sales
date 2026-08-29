@echo off
setlocal enabledelayedexpansion

:: Configuración del Proyecto
set "PROJECT_PATH=C:\laragon\www\jspos-sales"
set "LOG_FILE=%PROJECT_PATH%\storage\logs\backup_bat.log"

echo ==========================================
echo      INICIANDO RESPALDO AUTOMATICO
echo ==========================================
echo.

echo [%DATE% %TIME%] Iniciando proceso... > "%LOG_FILE%"

:: 1. Ir al directorio del proyecto
cd /d "%PROJECT_PATH%"
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] No se pudo entrar al directorio del proyecto.
    pause
    exit /b 1
)

:: 2. Limpiar respaldos viejos según reglas de config/backup.php
echo [PASO 1] Ejecutando limpieza de respaldos antiguos (php artisan backup:clean)...
echo [%DATE% %TIME%] Ejecutando php artisan backup:clean --disable-notifications... >> "%LOG_FILE%"
call "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan backup:clean --disable-notifications >> "%LOG_FILE%" 2>&1

:: 3. Ejecutar el respaldo de Laravel
echo [PASO 2] Generando respaldo (php artisan backup:run --only-db --disable-notifications)...
echo [%DATE% %TIME%] Ejecutando php artisan backup:run --only-db --disable-notifications... >> "%LOG_FILE%"
call "C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe" artisan backup:run --only-db --disable-notifications >> "%LOG_FILE%" 2>&1

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Fallo al crear el respaldo.
    echo Revisa el archivo de log: %LOG_FILE%
    echo [%DATE% %TIME%] Error al crear el respaldo. >> "%LOG_FILE%"
    pause
    exit /b %ERRORLEVEL%
) else (
    echo [OK] Proceso completado. Respaldo generado y sincronizado automáticamente por Google Drive.
)

echo.
echo ==========================================
echo           PROCESO FINALIZADO
echo ==========================================
echo.
echo Puedes cerrar esta ventana.
pause
