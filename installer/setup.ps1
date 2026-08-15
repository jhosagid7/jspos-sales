# PowerShell Post-Installation & Configuration Script for JSPOS Sales
param (
    [string]$ProjectDir = ""
)

if (-not $ProjectDir) {
    $ProjectDir = (Resolve-Path ($PSScriptRoot + "\..")).Path
} else {
    $ProjectDir = (Resolve-Path $ProjectDir).Path
}

Set-Location $ProjectDir

$folderName = Split-Path $ProjectDir -Leaf
$cleanFolder = $folderName -replace '^jspos[-_]?', ''
$cleanDb = 'jspos_' + ($cleanFolder -replace '[^a-zA-Z0-9_]', '_')
$appUrl = 'http://' + $folderName + '.test'

# Detectar directorio de Laragon e inyectar ejecutables embebidos (PHP, MySQL, Composer, Apache) en PATH
$laragonDir = "C:\laragon"
if (-not (Test-Path $laragonDir)) {
    $drive = $ProjectDir.Substring(0,2)
    $laragonDir = "$drive\laragon"
}

if (Test-Path "$laragonDir\bin") {
    $laragonBinPaths = Get-ChildItem -Path "$laragonDir\bin" -Recurse -Directory -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName
    $env:PATH = ($laragonBinPaths -join ';') + ';' + $env:PATH
}

Write-Host "Configurando proyecto: $folderName"
Write-Host "App URL: $appUrl"
Write-Host "Base de datos: $cleanDb"

# 1. Copiar .env si no existe
$envFile = "$ProjectDir\.env"
$envExample = "$ProjectDir\.env.example"
if (-not (Test-Path $envFile) -and (Test-Path $envExample)) {
    Copy-Item $envExample $envFile
}

# 2. Eliminar storage/installed y storage/app/client_id.txt si existen para que genere un ID unico de cliente por instalacion
$installedFile = "$ProjectDir\storage\installed"
if (Test-Path $installedFile) {
    Remove-Item -Force $installedFile
}

$clientIdFile = "$ProjectDir\storage\app\client_id.txt"
if (Test-Path $clientIdFile) {
    Remove-Item -Force $clientIdFile
}

# 3. Crear directorios de almacenamiento limpios
$storageDirs = @(
    "$ProjectDir\storage\app\public",
    "$ProjectDir\storage\app\backups",
    "$ProjectDir\storage\framework\cache",
    "$ProjectDir\storage\framework\sessions",
    "$ProjectDir\storage\framework\views",
    "$ProjectDir\storage\logs",
    "$ProjectDir\whatsapp-api\sessions"
)
foreach ($dir in $storageDirs) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
    }
}

# 4. Ajustar .env dinamicamente y generar APP_KEY
if (Test-Path $envFile) {
    $content = Get-Content $envFile -Raw
    $content = $content -replace '(?m)^APP_ENV=.*', "APP_ENV=production"
    $content = $content -replace '(?m)^APP_DEBUG=.*', "APP_DEBUG=false"
    $content = $content -replace '(?m)^APP_URL=.*', "APP_URL=$appUrl"
    $content = $content -replace '(?m)^DB_DATABASE=.*', "DB_DATABASE=$cleanDb"
    if ($content -match '(?m)^DEBUGBAR_ENABLED=.*') {
        $content = $content -replace '(?m)^DEBUGBAR_ENABLED=.*', "DEBUGBAR_ENABLED=false"
    } else {
        $content += "`nDEBUGBAR_ENABLED=false`n"
    }
    
    if ($content -match '(?m)^APP_KEY=\s*$' -or $content -notmatch '(?m)^APP_KEY=') {
        $bytes = New-Object byte[] 32
        (New-Object Security.Cryptography.RNGCryptoServiceProvider).GetBytes($bytes)
        $genKey = 'base64:' + [Convert]::ToBase64String($bytes)
        $content = $content -replace '(?m)^APP_KEY=.*', "APP_KEY=$genKey"
    }
    Set-Content -Path $envFile -Value $content -NoNewline
}

# 5. Registrar dominio .test en archivo hosts de Windows
$hostsFile = "C:\Windows\System32\drivers\etc\hosts"
if (Test-Path $hostsFile) {
    try {
        $hostsContent = Get-Content $hostsFile -Raw
        if ($hostsContent -notmatch [regex]::Escape("$folderName.test")) {
            Add-Content -Path $hostsFile -Value "`n127.0.0.1      $folderName.test #laragon magic!" -ErrorAction SilentlyContinue
        }
    } catch {}
}

# 6. Detección de IPs y Generación de VirtualHost completo en Laragon (SITE, SITE3, SITE4, SITE5)
$site3 = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias 'Wi-Fi*','Ethernet*','Conexión*' -ErrorAction SilentlyContinue | Where-Object { $_.IPAddress -notlike '169.254*' -and $_.IPAddress -notlike '127.*' } | Select-Object -ExpandProperty IPAddress -First 1)
if (-not $site3) { $site3 = '127.0.0.1' }

$site4 = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias '*ZeroTier*' -ErrorAction SilentlyContinue | Select-Object -ExpandProperty IPAddress -First 1)
if (-not $site4) { $site4 = '127.0.0.1' }

$site5 = (Get-NetIPAddress -AddressFamily IPv4 -InterfaceAlias '*Tailscale*' -ErrorAction SilentlyContinue | Select-Object -ExpandProperty IPAddress -First 1)
if (-not $site5) { $site5 = '127.0.0.1' }

$sitesDir = "$laragonDir\etc\apache2\sites-enabled"

if (Test-Path $sitesDir) {
    # Calcular puerto unico si hay múltiples sistemas instalados en el mismo servidor (ej. 80, 8081, 8082...)
    $existingConfs = Get-ChildItem -Path $sitesDir -Filter "auto.*.conf" -ErrorAction SilentlyContinue | Where-Object { $_.Name -ne "auto.$folderName.test.conf" }
    $usedPorts = @(80)
    Get-ChildItem -Path $sitesDir -Filter "auto.*.conf" -ErrorAction SilentlyContinue | ForEach-Object {
        $content = Get-Content $_.FullName -Raw
        if ($content -match '(?i)Listen\s+(\d+)') {
            $usedPorts += [int]$matches[1]
        }
    }

    $assignedPort = 80
    if ($existingConfs -and $existingConfs.Count -gt 0) {
        $candidatePort = 8081
        while ($usedPorts -contains $candidatePort) {
            $candidatePort++
        }
        $assignedPort = $candidatePort
    }

    $listenDirective = ""
    $portDirective = "*:80"
    if ($assignedPort -ne 80) {
        if (-not ($usedPorts -contains $assignedPort)) {
            $listenDirective = "Listen $assignedPort`n"
        }
        $portDirective = "*:80 *:$assignedPort"
        Write-Host "Puerto dedicado asignado para acceso por IP: $assignedPort"
    }

    # Guardar ASSIGNED_PORT en el archivo .env para que Laravel lo reconozca en el Paso 4
    if (Test-Path $envFile) {
        $envContent = Get-Content $envFile -Raw
        if ($envContent -match '(?m)^ASSIGNED_PORT=.*') {
            $envContent = $envContent -replace '(?m)^ASSIGNED_PORT=.*', "ASSIGNED_PORT=$assignedPort"
        } else {
            $envContent += "`nASSIGNED_PORT=$assignedPort`n"
        }
        Set-Content -Path $envFile -Value $envContent -NoNewline
    }

    $rootPath = ($ProjectDir.Replace('\', '/')) + "/public"
    $vhostContent = @"
$listenDirective`define ROOT "$rootPath"
define SITE "$folderName.test"
define SITE3 "$site3"
define SITE4 "$site4"
define SITE5 "$site5"

<VirtualHost $portDirective>
    DocumentRoot "`${ROOT}"
    ServerName `${SITE}
    ServerAlias *.`${SITE}
    <Directory "`${ROOT}">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost $portDirective>
    DocumentRoot "`${ROOT}"
    ServerName `${SITE3}
    ServerAlias *.`${SITE3}
    <Directory "`${ROOT}">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost $portDirective>
    DocumentRoot "`${ROOT}"
    ServerName `${SITE4}
    ServerAlias *.`${SITE4}
    <Directory "`${ROOT}">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost $portDirective>
    DocumentRoot "`${ROOT}"
    ServerName `${SITE5}
    ServerAlias *.`${SITE5}
    <Directory "`${ROOT}">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
"@
    Set-Content -Path "$sitesDir\auto.$folderName.test.conf" -Value $vhostContent
}

# 7. Crear base de datos en MySQL si no existe
$phpExe = "$laragonDir\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"
if (-not (Test-Path $phpExe)) {
    $foundPhp = Get-ChildItem -Path "$laragonDir\bin\php" -Filter "php.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($foundPhp) { $phpExe = $foundPhp.FullName }
}

if (Test-Path $phpExe) {
    Start-Process -FilePath $phpExe -ArgumentList "-r ""try { `$pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', ''); `$pdo->exec('CREATE DATABASE IF NOT EXISTS \`$cleanDb\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'); } catch (Exception `$e) {}""" -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan key:generate --force" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan storage:link --force" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan config:clear" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
    Start-Process -FilePath $phpExe -ArgumentList "artisan cache:clear" -WorkingDirectory $ProjectDir -WindowStyle Hidden -Wait
}

# 8. Reiniciar proceso de Apache (httpd.exe) y limpiar DNS
Get-Process -Name "httpd" -ErrorAction SilentlyContinue | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 1

$confPath = "$laragonDir\bin\apache\httpd-2.4.54-win64-VS16\conf\httpd.conf"
if (-not (Test-Path $confPath)) {
    $foundConf = Get-ChildItem -Path "$laragonDir\bin\apache" -Filter "httpd.conf" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($foundConf) { $confPath = $foundConf.FullName }
}

$httpdPath = "$laragonDir\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe"
if (-not (Test-Path $httpdPath)) {
    $foundHttpd = Get-ChildItem -Path "$laragonDir\bin\apache" -Filter "httpd.exe" -Recurse -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($foundHttpd) { $httpdPath = $foundHttpd.FullName }
}

if (Test-Path $httpdPath) {
    $apacheHome = (Resolve-Path "$httpdPath\..\..").Path
    Start-Process -FilePath $httpdPath -ArgumentList "-f `"$confPath`"" -WorkingDirectory $apacheHome -WindowStyle Hidden
}

Start-Process -FilePath "ipconfig" -ArgumentList "/flushdns" -WindowStyle Hidden -Wait
Write-Host "Post-instalacion completada exitosamente para $folderName."
