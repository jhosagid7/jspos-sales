; =====================================================================
; SCRIPT DE COMPILACION DE INNO SETUP PARA JSPOS SALES
; Genera el ejecutable 1-Click: Setup_JSPOS_Sales_v1.10.exe
; =====================================================================

#define MyAppName "JSPOS Sales"
#define MyAppVersion "1.10.383"
#define MyAppPublisher "JSPOS Software"
#define MyAppURL "http://jspos-sales.test"
#define MyAppExeName "jspos_launcher.bat"

[Setup]
; Identificador unico de la aplicacion
AppId={{D37E84B2-7A12-4B6E-9C8F-4D2A9B1C3E5F}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
AppPublisherURL={#MyAppURL}
AppSupportURL={#MyAppURL}
AppUpdatesURL={#MyAppURL}

; Ruta de instalacion predeterminada en el cliente
DefaultDirName=C:\laragon\www\jspos-sales
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
DisableDirPage=no
UsePreviousAppDir=no

; Suprimir reinicios solicitados por instaladores secundarios para no interrumpir post-install
RestartIfNeededByRun=no

; Salida del ejecutable compilado
OutputDir=.\output
OutputBaseFilename=Setup_JSPOS_Sales_v1.10.388
SetupIconFile=..\public\favicon.ico
UninstallDisplayIcon={app}\public\favicon.ico
Compression=lzma2/ultra64
SolidCompression=yes
WizardStyle=modern
PrivilegesRequired=admin

[Languages]
Name: "spanish"; MessagesFile: "compiler:Languages\Spanish.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked
Name: "laragon"; Description: "Instalar entorno de servidor web Laragon WAMP"; GroupDescription: "Componentes Adicionales de Servidor y Red:"
Name: "zerotier"; Description: "Instalar cliente de red VPN ZeroTier One"; GroupDescription: "Componentes Adicionales de Servidor y Red:"
Name: "tailscale"; Description: "Instalar cliente de red VPN Tailscale"; GroupDescription: "Componentes Adicionales de Servidor y Red:"

[Files]
; Herramientas opcionales y scripts de instalacion (copiados a directorio temporal y eliminados tras instalar)
Source: "tools\laragon-wamp.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: laragon
Source: "tools\ZeroTierOne.msi"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: zerotier
Source: "tools\tailscale-setup.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: tailscale
Source: "post_install.bat"; DestDir: "{tmp}"; Flags: deleteafterinstall
Source: "setup.ps1"; DestDir: "{tmp}"; Flags: deleteafterinstall
Source: "open_browser.bat"; DestDir: "{tmp}"; Flags: deleteafterinstall

; Copiar UNICAMENTE los archivos de produccion esenciales para el CLIENTE FINAL
; Se excluyen: Clave privada, scripts de desarrollo, carpetas de instalador, apps moviles .apk, logs y archivos temporales
Source: "..\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "*\.git\*,*\.idea\*,*\.vscode\*,*build\*,*.dart_tool\*,*.gradle\*,*storage\logs\*,*storage\framework\cache\*,*storage\framework\sessions\*,*storage\framework\views\*,*storage\debugbar\*,*storage\installed*,*storage\app\client_id.txt*,*storage\app\backups\*,*storage\app\backup-temp\*,*storage\app\livewire-tmp\*,*storage\app\temp\*,*storage\app\public\*,*public\storage\*,*whatsapp-api\sessions\*,*whatsapp-api\.puppeteer_cache*,*whatsapp-api\.wwebjs_cache*,*whatsapp-api\storage*,*whatsapp-api\uploads*,*whatsapp-api\*.log,*installer\*,*mobile_app\*,*mobile_vip_app\*,*private_key.pem*,*generate_keys.php*,*google-drive-key.json*,*DATOS_SERVIDOR_Y_ECOSISTEMA*,*CONTEXTO_IA.md*,*directives*,*fix_all*,*fix_score*,*GUIA_*.md*,*INSTALLATION_GUIDE.md*,*plan_modulos_saas.md*,*scratch_*,*.apk,*.sql,*.zip,*.tmp,*.log,*.env,*.pem.backup,*reset_system*,*finalizar_instalacion.bat*"

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\public\favicon.ico"; Comment: "Abrir JSPOS Sales"; Parameters: "{#MyAppURL}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "chrome.exe"; Parameters: "--app={#MyAppURL}"; IconFilename: "{app}\public\favicon.ico"; Tasks: desktopicon

[Run]
; Instalacion silenciosa sin reinicio obligado de herramientas adicionales
Filename: "{tmp}\laragon-wamp.exe"; Parameters: "/SILENT /NORESTART /SUPPRESSMSGBOXES"; Tasks: laragon; Flags: runhidden waituntilterminated; StatusMsg: "Instalando entorno Laragon WAMP..."
Filename: "msiexec.exe"; Parameters: "/i ""{tmp}\ZeroTierOne.msi"" /qn REBOOT=ReallySuppress"; Tasks: zerotier; Flags: runhidden waituntilterminated; StatusMsg: "Instalando cliente ZeroTier One..."
Filename: "{tmp}\tailscale-setup.exe"; Parameters: "/silent /norestart /suppressmsgboxes"; Tasks: tailscale; Flags: runhidden waituntilterminated; StatusMsg: "Instalando cliente Tailscale..."

; Ejecutar el script post_install.bat desde la carpeta temporal (configura Apache, MySQL, BD, migraciones y abre el navegador)
Filename: "{tmp}\post_install.bat"; Parameters: """{app}"""; Flags: runhidden waituntilterminated; StatusMsg: "Configurando base de datos, servidor Apache y servicios en segundo plano..."
