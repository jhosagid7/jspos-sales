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
OutputBaseFilename=Setup_JSPOS_Sales_v1.10
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
; Herramientas opcionales de instalacion (copiadas a directorio temporal)
Source: "tools\laragon-wamp.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: laragon
Source: "tools\ZeroTierOne.msi"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: zerotier
Source: "tools\tailscale-setup.exe"; DestDir: "{tmp}"; Flags: deleteafterinstall; Tasks: tailscale

; Copiar todos los archivos del proyecto para una INSTALACION LIMPIA, excluyendo respaldos DB, PDFs generados, imagenes de storage, sesiones y logs
Source: "..\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "*\.git\*,*\.idea\*,*\.vscode\*,*build\*,*.dart_tool\*,*.gradle\*,*storage\logs\*,*storage\framework\cache\*,*storage\framework\sessions\*,*storage\framework\views\*,*storage\debugbar\*,*storage\installed*,*storage\app\client_id.txt*,*storage\app\backups\*,*storage\app\backup-temp\*,*storage\app\livewire-tmp\*,*storage\app\temp\*,*storage\app\public\*,*public\storage\*,*whatsapp-api\sessions\*,*whatsapp-api\.puppeteer_cache*,*whatsapp-api\.wwebjs_cache*,*whatsapp-api\storage*,*whatsapp-api\uploads*,*whatsapp-api\*.log,*installer\output\*,*.sql,*.zip,*.tmp,*.log,*.env"

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\public\favicon.ico"; Comment: "Abrir JSPOS Sales"; Parameters: "{#MyAppURL}"
Name: "{group}\Finalizar Configuración JSPOS Sales"; Filename: "{app}\finalizar_instalacion.bat"; Comment: "Completar instalación de JSPOS Sales"
Name: "{autodesktop}\{#MyAppName}"; Filename: "chrome.exe"; Parameters: "--app={#MyAppURL}"; IconFilename: "{app}\public\favicon.ico"; Tasks: desktopicon
Name: "{autodesktop}\Finalizar Configuración JSPOS Sales"; Filename: "{app}\finalizar_instalacion.bat"; Comment: "Completar instalación de JSPOS Sales"; Flags: runminimized

[Run]
; Instalacion silenciosa sin reinicio obligado de herramientas adicionales
Filename: "{tmp}\laragon-wamp.exe"; Parameters: "/SILENT /NORESTART /SUPPRESSMSGBOXES"; Tasks: laragon; Flags: runhidden waituntilterminated; StatusMsg: "Instalando entorno Laragon WAMP..."
Filename: "msiexec.exe"; Parameters: "/i ""{tmp}\ZeroTierOne.msi"" /qn REBOOT=ReallySuppress"; Tasks: zerotier; Flags: runhidden waituntilterminated; StatusMsg: "Instalando cliente ZeroTier One..."
Filename: "{tmp}\tailscale-setup.exe"; Parameters: "/silent /norestart /suppressmsgboxes"; Tasks: tailscale; Flags: runhidden waituntilterminated; StatusMsg: "Instalando cliente Tailscale..."

; Ejecutar el script post_install.bat al finalizar la descompresion de archivos (configura Apache, MySQL, BD, migraciones y abre el navegador)
Filename: "{app}\installer\post_install.bat"; Parameters: """{app}"""; Flags: runhidden waituntilterminated; StatusMsg: "Configurando base de datos, servidor Apache y servicios en segundo plano..."
