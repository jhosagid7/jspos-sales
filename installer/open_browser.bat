@echo off
setlocal enabledelayedexpansion

set "PROY_PATH=C:\laragon\www\jspos-sales"
if not "%~1"=="" set "PROY_PATH=%~1"
for %%I in ("%PROY_PATH%") do set "FOLDER_NAME=%%~nxI"

echo Limpiando cache DNS de Windows...
ipconfig /flushdns >nul 2>&1

echo Abriendo asistente para !FOLDER_NAME!...
start "" "http://!FOLDER_NAME!.test/install" 2>nul || start "" "http://localhost/!FOLDER_NAME!/public/install"
exit /b 0
