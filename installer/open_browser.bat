@echo off
setlocal enabledelayedexpansion
for %%I in ("%~dp0..") do set "FOLDER_NAME=%%~nxI"

echo Limpiando cache DNS de Windows...
ipconfig /flushdns >nul 2>&1

echo Abriendo asistente para !FOLDER_NAME!...
start "" "http://!FOLDER_NAME!.test/install" 2>nul || start "" "http://localhost/!FOLDER_NAME!/public/install"
exit /b 0
