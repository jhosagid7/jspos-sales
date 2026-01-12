@echo off
setlocal enabledelayedexpansion

:: Configuración
set "PROJECT_PATH=C:\laragon\www\jspos-sales"
set "SOURCE_PATH=%PROJECT_PATH%\storage\app\backups"
set "DEST_PATH=G:\My Drive\Backups_JSPOS"
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

:: 2. Ejecutar el respaldo de Laravel
echo [PASO 1] Generando respaldo (php artisan backup:run --only-db)...
echo [%DATE% %TIME%] Ejecutando php artisan backup:run --only-db... >> "%LOG_FILE%"
call php artisan backup:run --only-db >> "%LOG_FILE%" 2>&1

if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Fallo al crear el respaldo.
    echo Revisa el archivo de log: %LOG_FILE%
    echo [%DATE% %TIME%] Error al crear el respaldo. >> "%LOG_FILE%"
    pause
    exit /b %ERRORLEVEL%
) else (
    echo [OK] Respaldo generado correctamente.
)

:: 3. Copiar a Google Drive
echo.
echo [PASO 2] Copiando archivos a: "%DEST_PATH%"
echo [%DATE% %TIME%] Copiando archivos a Google Drive... >> "%LOG_FILE%"

:: Verificar si el origen existe
if not exist "%SOURCE_PATH%" (
    echo [ERROR] La carpeta de origen no existe: "%SOURCE_PATH%"
    pause
    exit /b 1
)

:: Verificar si el destino existe (o intentar crearlo)
if not exist "%DEST_PATH%" (
    echo [AVISO] La carpeta de destino no existe. Intentando crearla...
    mkdir "%DEST_PATH%"
    if !ERRORLEVEL! NEQ 0 (
        echo [ERROR] No se pudo crear la carpeta de destino. Verifica la ruta.
        pause
        exit /b 1
    )
)

:: Robocopy
:: /E :: Copia subdirectorios, incluyendo vacíos
:: /XO :: Excluye archivos antiguos
:: /R:1 :: 1 reintento
:: /W:2 :: Espera 2 segundos
robocopy "%SOURCE_PATH%" "%DEST_PATH%" /E /XO /R:1 /W:2 >> "%LOG_FILE%"

:: Robocopy exit codes:
:: 0 = No files copied (no changes)
:: 1 = Files copied successfully
:: 2 = Extra files in dest (not copied)
:: 4 = Mismatched files
:: 8 = Failed copies
set ROBO_EXIT=%ERRORLEVEL%

if %ROBO_EXIT% GEQ 8 (
    echo [ERROR] Hubo errores al copiar los archivos. Codigo: %ROBO_EXIT%
    echo [%DATE% %TIME%] Error en Robocopy (Codigo: %ROBO_EXIT%). >> "%LOG_FILE%"
) else (
    echo [OK] Copia finalizada. (Codigo: %ROBO_EXIT%)
    echo [%DATE% %TIME%] Respaldo copiado exitosamente. >> "%LOG_FILE%"
)

echo.
echo ==========================================
echo           PROCESO FINALIZADO
echo ==========================================
echo.
echo Puedes cerrar esta ventana.
pause
