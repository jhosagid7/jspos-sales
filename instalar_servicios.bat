@echo off
:: Solicitar permisos de Administrador automaticamente si no los tiene
net session >nul 2>&1
if %errorlevel% neq 0 (
    powershell -Command "Start-Process '%~f0' -Verb RunAs"
    exit /b
)

echo ===================================================
echo   INSTALADOR DE SERVICIOS - JSPOS (NSSM)
echo ===================================================
echo.

:: 1. Definir las rutas dinamicamente
set "PROYECTO_DIR=%~dp0"
if "%PROYECTO_DIR:~-1%"=="\" set "PROYECTO_DIR=%PROYECTO_DIR:~0,-1%"
set "NSSM_EXE=%PROYECTO_DIR%\nssm\nssm.exe"

:: Modo silencioso (para llamadas automaticas desde el instalador)
set "SILENT_MODE=0"
if "%1"=="--silent" set "SILENT_MODE=1"
if "%1"=="/SILENT" set "SILENT_MODE=1"

:: Detectar automaticamente la ruta completa de php.exe
set "PHP_EXE="
for /f "delims=" %%i in ('where php 2^>nul') do (
    set "PHP_EXE=%%i"
    goto :php_found
)

:: Si no esta en el PATH global, buscar en Laragon C: y otras unidades
if exist "C:\laragon\bin\php" (
    for /f "delims=" %%i in ('dir /b /s /a:-d "C:\laragon\bin\php\php.exe" 2^>nul') do (
        set "PHP_EXE=%%i"
        goto :php_found
    )
)
for %%d in (D E F) do (
    if exist "%%d:\laragon\bin\php" (
        for /f "delims=" %%i in ('dir /b /s /a:-d "%%d:\laragon\bin\php\php.exe" 2^>nul') do (
            set "PHP_EXE=%%i"
            goto :php_found
        )
    )
)

if not defined PHP_EXE (
    echo.
    echo [ERROR] No se encontro php.exe ni en el PATH del sistema ni en C:\laragon\bin\php.
    echo Por favor, corre este script desde el Laragon Terminal o agrega PHP al PATH de Windows.
    if "%SILENT_MODE%"=="0" pause
    exit /b 1
)

:php_found
echo PHP encontrado en: %PHP_EXE%

:: Detectar automaticamente la ruta de node.exe
set "NODE_EXE="
for /f "delims=" %%i in ('where node 2^>nul') do (
    set "NODE_EXE=%%i"
    goto :node_found
)
if exist "C:\Program Files\nodejs\node.exe" (
    set "NODE_EXE=C:\Program Files\nodejs\node.exe"
    goto :node_found
)
if exist "C:\Program Files (x86)\nodejs\node.exe" (
    set "NODE_EXE=C:\Program Files (x86)\nodejs\node.exe"
    goto :node_found
)
if exist "C:\laragon\bin\nodejs" (
    for /f "delims=" %%i in ('dir /b /s /a:-d "C:\laragon\bin\nodejs\node.exe" 2^>nul') do (
        set "NODE_EXE=%%i"
        goto :node_found
    )
)
set "NODE_EXE=node"

:node_found
echo Node.js configurado como: %NODE_EXE%

echo Deteniendo servicios antiguos si existen...
"%NSSM_EXE%" stop JSPOS_WhatsApp_API >nul 2>&1
"%NSSM_EXE%" remove JSPOS_WhatsApp_API confirm >nul 2>&1
"%NSSM_EXE%" stop JSPOS_Queue_Worker >nul 2>&1
"%NSSM_EXE%" remove JSPOS_Queue_Worker confirm >nul 2>&1
"%NSSM_EXE%" stop JSPOS_Scheduler >nul 2>&1
"%NSSM_EXE%" remove JSPOS_Scheduler confirm >nul 2>&1
echo.

echo Instalando Servicio: JSPOS_WhatsApp_API...
if not exist "%PROYECTO_DIR%\whatsapp-api\storage\logs" mkdir "%PROYECTO_DIR%\whatsapp-api\storage\logs"

:: Descargar el navegador de WhatsApp localmente primero
echo Descargando dependencias del navegador (Chrome) localmente...
set "PUPPETEER_CACHE_DIR=%PROYECTO_DIR%\whatsapp-api\.puppeteer_cache"
cd /d "%PROYECTO_DIR%\whatsapp-api"
cmd /c "npm install && npx puppeteer browsers install chrome"

:: Crear el servicio de Node (WhatsApp)
"%NSSM_EXE%" install JSPOS_WhatsApp_API "%NODE_EXE%" "index.js"
"%NSSM_EXE%" set JSPOS_WhatsApp_API AppDirectory "%PROYECTO_DIR%\whatsapp-api"
"%NSSM_EXE%" set JSPOS_WhatsApp_API AppEnvironmentExtra PUPPETEER_CACHE_DIR="%PUPPETEER_CACHE_DIR%"
"%NSSM_EXE%" set JSPOS_WhatsApp_API Description "Motor de WhatsApp Web JS para JSPOS"
"%NSSM_EXE%" set JSPOS_WhatsApp_API AppStdout "%PROYECTO_DIR%\whatsapp-api\storage\logs\whatsapp-service.log"
"%NSSM_EXE%" set JSPOS_WhatsApp_API AppStderr "%PROYECTO_DIR%\whatsapp-api\storage\logs\whatsapp-error.log"
"%NSSM_EXE%" set JSPOS_WhatsApp_API AppRestartDelay 2000

echo.
echo Instalando Servicio: JSPOS_Queue_Worker...
:: Crear el servicio de Laravel (Cola de Tareas)
"%NSSM_EXE%" install JSPOS_Queue_Worker "%PHP_EXE%" "artisan queue:work --tries=3 --timeout=90"
"%NSSM_EXE%" set JSPOS_Queue_Worker AppDirectory "%PROYECTO_DIR%"
"%NSSM_EXE%" set JSPOS_Queue_Worker Description "Procesador de trabajos en segundo plano de JSPOS"
"%NSSM_EXE%" set JSPOS_Queue_Worker AppStdout "%PROYECTO_DIR%\storage\logs\queue-worker.log"
"%NSSM_EXE%" set JSPOS_Queue_Worker AppStderr "%PROYECTO_DIR%\storage\logs\queue-error.log"
"%NSSM_EXE%" set JSPOS_Queue_Worker AppRestartDelay 2000

echo.
echo Instalando Servicio: JSPOS_Scheduler...
:: Crear el servicio de Laravel (Planificador de Tareas)
"%NSSM_EXE%" install JSPOS_Scheduler "%PHP_EXE%" "artisan schedule:work"
"%NSSM_EXE%" set JSPOS_Scheduler AppDirectory "%PROYECTO_DIR%"
"%NSSM_EXE%" set JSPOS_Scheduler Description "Planificador de tareas y respaldos de JSPOS"
"%NSSM_EXE%" set JSPOS_Scheduler AppStdout "%PROYECTO_DIR%\storage\logs\scheduler-worker.log"
"%NSSM_EXE%" set JSPOS_Scheduler AppStderr "%PROYECTO_DIR%\storage\logs\scheduler-error.log"
"%NSSM_EXE%" set JSPOS_Scheduler AppRestartDelay 2000

echo.
echo Iniciando los servicios...
"%NSSM_EXE%" start JSPOS_WhatsApp_API
"%NSSM_EXE%" start JSPOS_Queue_Worker
"%NSSM_EXE%" start JSPOS_Scheduler

echo.
echo ===================================================
echo ??INSTALACION COMPLETADA!
echo Los servicios ya estan corriendo invisiblemente.
echo ===================================================
if "%SILENT_MODE%"=="0" pause
