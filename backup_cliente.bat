@echo off
setlocal enabledelayedexpansion

:: Configuración del Proyecto
set "PROJECT_PATH=%~dp0"
if "%PROJECT_PATH:~-1%"=="\" set "PROJECT_PATH=%PROJECT_PATH:~0,-1%"
if not exist "%PROJECT_PATH%\artisan" set "PROJECT_PATH=C:\laragon\www\jspos-sales"

set "LOG_FILE=%PROJECT_PATH%\storage\logs\backup_bat.log"

:: Modo silencioso/programado
set "IS_SCHEDULED=0"
if /i "%~1"=="--scheduled" set "IS_SCHEDULED=1"
if /i "%~1"=="/scheduled" set "IS_SCHEDULED=1"
if /i "%~1"=="--silent" set "IS_SCHEDULED=1"

echo ==========================================
echo      INICIANDO RESPALDO AUTOMATICO
echo ==========================================
echo.

echo [%DATE% %TIME%] Iniciando proceso de respaldo... > "%LOG_FILE%"

:: 1. Ir al directorio del proyecto
cd /d "%PROJECT_PATH%"
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] No se pudo entrar al directorio del proyecto: %PROJECT_PATH%
    echo [%DATE% %TIME%] Error entrando al directorio del proyecto. >> "%LOG_FILE%"
    if "!IS_SCHEDULED!"=="0" pause
    exit /b 1
)

:: 2. Detectar PHP de Laragon dinamicamente o desde PATH
set "PHP_BIN=php"
if exist "C:\laragon\bin\php" (
    for /f "delims=" %%I in ('dir /b /ad /o-n "C:\laragon\bin\php\php-*" 2^>nul') do (
        if exist "C:\laragon\bin\php\%%I\php.exe" (
            set "PHP_BIN=C:\laragon\bin\php\%%I\php.exe"
            goto :php_detected
        )
    )
)
:php_detected

echo [INFO] Utilizando ejecutable PHP: "%PHP_BIN%"
echo [%DATE% %TIME%] PHP detectado: "%PHP_BIN%" >> "%LOG_FILE%"

:: 3. Limpiar respaldos viejos según reglas de config/backup.php
echo [PASO 1] Ejecutando limpieza de respaldos antiguos (php artisan backup:clean)...
echo [%DATE% %TIME%] Ejecutando backup:clean... >> "%LOG_FILE%"
call "%PHP_BIN%" artisan backup:clean --disable-notifications >> "%LOG_FILE%" 2>&1

:: 4. Ejecutar el respaldo de base de datos
echo [PASO 2] Generando respaldo de base de datos (php artisan backup:run --only-db)...
echo [%DATE% %TIME%] Ejecutando backup:run --only-db... >> "%LOG_FILE%"
call "%PHP_BIN%" artisan backup:run --only-db --disable-notifications >> "%LOG_FILE%" 2>&1

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Fallo al crear el respaldo local.
    echo Revisa el archivo de log: %LOG_FILE%
    echo [%DATE% %TIME%] Error al crear el respaldo local. >> "%LOG_FILE%"
    if "!IS_SCHEDULED!"=="0" pause
    exit /b %ERRORLEVEL%
)

:: 5. Sincronizar respaldo con la nube (Opción A - Servidor Central de Licencias)
echo [PASO 3] Sincronizando respaldo con el Servidor Central de Licencias en la Nube...
echo [%DATE% %TIME%] Ejecutando backup:cloud-sync... >> "%LOG_FILE%"
call "%PHP_BIN%" artisan backup:cloud-sync >> "%LOG_FILE%" 2>&1

echo [OK] Proceso completado exitosamente. Respaldo generado y sincronizado con la nube.
echo [%DATE% %TIME%] Proceso completado exitosamente. >> "%LOG_FILE%"

echo.
echo ==========================================
echo           PROCESO FINALIZADO
echo ==========================================
echo.
if "!IS_SCHEDULED!"=="0" (
    echo Puedes cerrar esta ventana.
    pause
)
exit /b 0
