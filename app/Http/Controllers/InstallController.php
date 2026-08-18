<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\LicenseService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class InstallController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function index()
    {
        // Check if already installed
        if (File::exists(storage_path('installed'))) {
            return redirect('/');
        }
        return redirect()->route('install.step1');
    }

    /**
     * Identifies if the installation is via Git or Manual Copy
     */
    protected function getInstallationMethod()
    {
        if (File::isDirectory(base_path('.git'))) {
            return 'Git Clone (Profesional)';
        }
        return 'Copia Manual (Standard)';
    }

    /**
     * Checks if composer dependencies are present
     */
    protected function checkDependencies()
    {
        return File::isDirectory(base_path('vendor'));
    }

    // Step 1: Requirements & Method Detection
    public function step1()
    {
        $requirements = [
            'Versión de PHP >= 8.1' => version_compare(phpversion(), '8.1.0', '>='),
            'Extensión BCMath' => extension_loaded('bcmath'),
            'Extensión Ctype' => extension_loaded('ctype'),
            'Extensión JSON' => extension_loaded('json'),
            'Extensión Mbstring' => extension_loaded('mbstring'),
            'Extensión OpenSSL' => extension_loaded('openssl'),
            'Extensión PDO' => extension_loaded('pdo'),
            'Extensión Tokenizer' => extension_loaded('tokenizer'),
            'Extensión XML' => extension_loaded('xml'),
            'Storage con Permisos de Escritura' => is_writable(storage_path()),
            'Bootstrap Cache con Permisos de Escritura' => is_writable(base_path('bootstrap/cache')),
        ];

        $installationMethod = $this->getInstallationMethod();
        $hasVendor = $this->checkDependencies();
        $allMet = !in_array(false, $requirements) && $hasVendor;

        return view('install.requirements', compact('requirements', 'allMet', 'installationMethod', 'hasVendor'));
    }

    // Step 2: Database
    public function step2()
    {
        return view('install.database');
    }

    public function saveDatabase(Request $request)
    {
        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required',
            'db_database' => 'required',
            'db_username' => 'required',
        ]);

        try {
            // First, connect without database to create it if needed
            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port}",
                $request->db_username,
                $request->db_password
            );
            
            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$request->db_database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            
            // Now connect to the specific database
            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port};dbname={$request->db_database}",
                $request->db_username,
                $request->db_password
            );
        } catch (\PDOException $e) {
            return back()->with('error', 'Error de conexión o permisos: ' . $e->getMessage())->withInput();
        }

        // Write to .env
        $this->writeEnv([
            'APP_URL' => $request->root(),
            'DB_HOST' => $request->db_host,
            'DB_PORT' => $request->db_port,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password,
        ]);

        return redirect()->route('install.step3');
    }

    // Step 3: Migrations & Master Data
    public function step3()
    {
        return view('install.migrations');
    }

    public function runMigrations()
    {
        try {
            // Generate App Key if not exists
            if (empty(config('app.key'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }

            // Run Migrations
            Artisan::call('migrate', ['--force' => true]);

            // Run Master Data Seeder (Professional/Production Mode)
            Artisan::call('db:seed', [
                '--class' => 'MasterDataSeeder',
                '--force' => true
            ]);

        } catch (\Exception $e) {
            return back()->with('error', 'Error en migración/seed: ' . $e->getMessage());
        }

        return redirect()->route('install.step4');
    }

    // Step 4: License
    public function step4()
    {
        $clientId = $this->licenseService->getClientId();
        
        $host = request()->getHost();
        $port = env('ASSIGNED_PORT') ?: request()->getPort();
        
        $vpnIps = $this->detectAllLocalIps($port);
        
        $defaultVpnIp = '';
        if ($host && $host !== 'localhost' && $host !== '127.0.0.1' && !str_contains($host, '.test')) {
            $defaultVpnIp = $host;
        } elseif (!empty($vpnIps)) {
            $defaultVpnIp = reset($vpnIps);
        } else {
            $detectedIp = gethostbyname(gethostname());
            if ($detectedIp && $detectedIp !== '127.0.0.1') {
                $defaultVpnIp = $detectedIp;
            }
        }

        if ($defaultVpnIp && $port && $port != 80 && $port != 443 && !str_contains($defaultVpnIp, ':')) {
            $defaultVpnIp .= ':' . $port;
        }

        $defaultServerIp = $this->licenseService->getLicenseServerIp();

        return view('install.license', compact('clientId', 'defaultVpnIp', 'defaultServerIp', 'vpnIps'));
    }

    private function detectAllLocalIps($port = null)
    {
        $ips = [];
        $portSuffix = ($port && $port != 80 && $port != 443) ? ":{$port}" : '';

        if (str_starts_with(PHP_OS, 'WIN')) {
            @exec('powershell -Command "Get-NetIPAddress -AddressFamily IPv4 | Select-Object InterfaceAlias, IPAddress | ConvertTo-Json"', $output);
            $json = implode("\n", (array) $output);
            $data = json_decode($json, true);
            if (is_array($data)) {
                if (isset($data['InterfaceAlias'])) {
                    $data = [$data];
                }
                foreach ($data as $item) {
                    $alias = strtolower($item['InterfaceAlias'] ?? '');
                    $ip = $item['IPAddress'] ?? '';
                    if ($ip && $ip !== '127.0.0.1' && !str_starts_with($ip, '169.254.')) {
                        $fullIp = $ip . $portSuffix;
                        if (str_contains($alias, 'tailscale') || str_starts_with($ip, '100.')) {
                            $ips['Tailscale'] = $fullIp;
                        } elseif (str_contains($alias, 'zerotier')) {
                            $ips['ZeroTier'] = $fullIp;
                        } elseif (str_contains($alias, 'wi-fi') || str_contains($alias, 'ethernet')) {
                            $ips['Red Local (LAN)'] = $fullIp;
                        }
                    }
                }
            }
        }
        return $ips;
    }

    public function connectLicenseServer(Request $request)
    {
        $request->validate([
            'server_ip' => 'required|string',
            'client_name' => 'required|string',
        ]);

        $clientId = $this->licenseService->getClientId();
        $serverIp = trim($request->server_ip);
        $serverIp = preg_replace('#^https?://#i', '', $serverIp);
        $serverIp = rtrim($serverIp, '/');

        try {
            $url = "http://{$serverIp}/api/clients/register";
            $response = Http::timeout(5)->post($url, [
                'client_system_id' => $clientId,
                'name' => $request->client_name,
                'vpn_ip' => $request->client_vpn_ip ?: null,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Save server IP to session
                session(['license_server_ip' => $serverIp]);

                if (!empty($data['has_license']) && !empty($data['license_key'])) {
                    $activated = $this->licenseService->activateLicense($data['license_key']);
                    if ($activated) {
                        return response()->json([
                            'status' => 'activated',
                            'message' => $data['message'] ?? '¡Licencia detectada y activada automáticamente!',
                            'redirect' => route('install.step5')
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'registered',
                    'message' => $data['message'] ?? '¡Equipo registrado en línea exitosamente! El administrador ya lo ve en su panel.',
                    'client_id' => $clientId
                ]);
            }

            $json = $response->json();
            $errMsg = is_array($json) ? ($json['message'] ?? $json['error'] ?? $json['msg'] ?? null) : null;
            if (!$errMsg) {
                $body = $response->body();
                $errMsg = !empty($body) ? trim(substr(strip_tags($body), 0, 150)) : ('HTTP Status Code ' . $response->status());
            }

            return response()->json([
                'status' => 'error',
                'message' => 'El servidor de licencias devolvió un error: ' . $errMsg
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo establecer conexión con http://' . $serverIp . ': ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkLicenseStatus(Request $request)
    {
        $serverIp = $request->input('server_ip') ?: session('license_server_ip');
        if (!$serverIp) {
            return response()->json(['status' => 'error', 'message' => 'IP del servidor no configurada.'], 400);
        }
        $serverIp = preg_replace('#^https?://#i', '', $serverIp);
        $serverIp = rtrim($serverIp, '/');

        $clientId = $this->licenseService->getClientId();

        try {
            $url = "http://{$serverIp}/api/clients/check-status";
            $response = Http::timeout(5)->post($url, [
                'client_system_id' => $clientId,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['has_license']) && !empty($data['license_key'])) {
                    $activated = $this->licenseService->activateLicense($data['license_key']);
                    if ($activated) {
                        return response()->json([
                            'status' => 'activated',
                            'message' => '¡Licencia aprobada y activada exitosamente!',
                            'redirect' => route('install.step5')
                        ]);
                    }
                }

                return response()->json([
                    'status' => 'pending',
                    'message' => $data['message'] ?? 'Aún en espera de que el administrador apruebe y asigne la licencia en el panel.'
                ]);
            }

            $json = $response->json();
            $errMsg = is_array($json) ? ($json['message'] ?? $json['error'] ?? $json['msg'] ?? null) : null;

            return response()->json(['status' => 'error', 'message' => 'Servidor: ' . ($errMsg ?? 'Código ' . $response->status())], 400);


        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error de conexión con el servidor de licencias.'], 500);
        }
    }

    public function activateLicense(Request $request)
    {
        $request->validate(['license_key' => 'required']);

        if ($this->licenseService->activateLicense($request->license_key)) {
            return redirect()->route('install.step5');
        }

        return back()->with('error', 'Licencia inválida.');
    }

    // Step 5: Admin
    public function step5()
    {
        return view('install.admin');
    }

    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ], [
            'name.required' => 'El nombre del administrador es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.unique' => 'Este correo electrónico ya está registrado en el sistema.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        // Determine Role
        $superAdminEmails = ['jhosagid7@gmail.com', 'jhosagid77@gmail.com'];
        $role = in_array($request->email, $superAdminEmails) ? 'Super Admin' : 'Admin';

        // Create Client Admin
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'profile' => $role, 
            'status' => 'Active',
        ]);
        
        // Assign Role
        $user->assignRole($role);

        // Create the installed lock file
        file_put_contents(storage_path('installed'), 'JSPOS INSTALLED ON ' . date('Y-m-d H:i:s'));

        // Mark as installed in ENV
        $this->writeEnv(['APP_INSTALLED' => 'true']);

        return view('install.finish');
    }

    public function finish()
    {
        return view('install.finish');
    }

    protected function writeEnv(array $data)
    {
        $path = base_path('.env');
        
        // If .env doesn't exist, try to copy from .env.example
        if (!file_exists($path) && file_exists(base_path('.env.example'))) {
            File::copy(base_path('.env.example'), $path);
        }

        if (file_exists($path)) {
            $content = file_get_contents($path);
            foreach ($data as $key => $value) {
                if (preg_match("/^{$key}=.*/m", $content)) {
                    $content = preg_replace("/^{$key}=.*/m", "{$key}=" . ($value ?? ''), $content);
                } else {
                    $content .= "\n{$key}=" . ($value ?? '');
                }
            }
            file_put_contents($path, $content);
        }
    }

    public function downloadShortcut()
    {
        $appUrl = request()->root();
        $appName = config('app.name', 'JSPOS Sales');
        
        $content = <<<EOT
@echo off
set "URL={$appUrl}"
set "NAME={$appName}"

echo Creando acceso directo para %NAME%...

echo Set oWS = WScript.CreateObject("WScript.Shell") > "%temp%\CreateShortcut.vbs"
echo sLinkFile = oWS.ExpandEnvironmentStrings("%USERPROFILE%\Desktop\" & "%NAME%.lnk") >> "%temp%\CreateShortcut.vbs"
echo Set oLink = oWS.CreateShortcut(sLinkFile) >> "%temp%\CreateShortcut.vbs"
echo oLink.TargetPath = "chrome.exe" >> "%temp%\CreateShortcut.vbs"
echo oLink.Arguments = "--app=" & "%URL%" >> "%temp%\CreateShortcut.vbs"
echo oLink.IconLocation = "chrome.exe" >> "%temp%\CreateShortcut.vbs"
echo oLink.Save >> "%temp%\CreateShortcut.vbs"

cscript //nologo "%temp%\CreateShortcut.vbs"
del "%temp%\CreateShortcut.vbs"

echo.
echo Acceso directo creado exitosamente en el Escritorio.
echo Abriendo sistema...
echo.

start "" "chrome.exe" --app="%URL%"

pause
EOT;

        return response($content)
            ->header('Content-Type', 'application/x-bat')
            ->header('Content-Disposition', 'attachment; filename="Instalar_Acceso_Directo.bat"');
    }
}
