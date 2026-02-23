@echo off
echo ===================================================
echo   INSTALADOR DE SERVICIOS - JSPOS (NSSM)
echo ===================================================
echo.

:: 1. Definir las rutas (¡Cambia estas rutas si en tu cliente Laragon esta en otro lado!)
set "PROYECTO_DIR=C:\laragon\www\jspos-sales"
set "NSSM_EXE=%PROYECTO_DIR%\nssm\nssm.exe"

set "NODE_EXE=node"

:: Detectar automaticamente la ruta completa de php.exe (importante cuando Laragon no esta en el PATH global)
for /f "delims=" %%i in ('where php 2^>nul') do set "PHP_EXE=%%i" & goto :php_found
echo.
echo [ERROR] No se encontro php.exe en el PATH del sistema.
echo Por favor, corre este script desde el Laragon Terminal o agrega PHP al PATH de Windows.
echo Ruta tipica de Laragon: C:\laragon\bin\php\phpX.X.X\php.exe
pause
exit /b 1
:php_found
echo PHP encontrado en: %PHP_EXE%

echo Deteniendo servicios antiguos si existen...
"%NSSM_EXE%" stop JSPOS_WhatsApp_API >nul 2>&1
"%NSSM_EXE%" remove JSPOS_WhatsApp_API confirm >nul 2>&1
"%NSSM_EXE%" stop JSPOS_Queue_Worker >nul 2>&1
"%NSSM_EXE%" remove JSPOS_Queue_Worker confirm >nul 2>&1
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
echo Iniciando los servicios...
"%NSSM_EXE%" start JSPOS_WhatsApp_API
"%NSSM_EXE%" start JSPOS_Queue_Worker

echo.
echo ===================================================
echo ¡INSTALACION COMPLETADA!
echo Los servicios ya estan corriendo invisiblemente.
echo ===================================================
pause
