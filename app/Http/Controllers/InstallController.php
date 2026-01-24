<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Services\LicenseService;
use Spatie\Permission\Models\Role;

class InstallController extends Controller
{
    protected $licenseService;

    public function __construct(LicenseService $licenseService)
    {
        $this->licenseService = $licenseService;
    }

    public function index()
    {
        // Redirect to the first incomplete step
        return redirect()->route('install.step1');
    }

    // Step 1: Requirements
    public function step1()
    {
        $requirements = [
            'PHP Version >= 8.1' => version_compare(phpversion(), '8.1.0', '>='),
            'BCMath Extension' => extension_loaded('bcmath'),
            'Ctype Extension' => extension_loaded('ctype'),
            'JSON Extension' => extension_loaded('json'),
            'Mbstring Extension' => extension_loaded('mbstring'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'PDO Extension' => extension_loaded('pdo'),
            'Tokenizer Extension' => extension_loaded('tokenizer'),
            'XML Extension' => extension_loaded('xml'),
            'Storage Writable' => is_writable(storage_path()),
            'Bootstrap Cache Writable' => is_writable(base_path('bootstrap/cache')),
        ];

        $allMet = !in_array(false, $requirements);

        return view('install.requirements', compact('requirements', 'allMet'));
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

    // Step 3: Migrations
    public function step3()
    {
        return view('install.migrations');
    }

    public function runMigrations()
    {
        try {
            Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al migrar: ' . $e->getMessage());
        }

        return redirect()->route('install.step4');
    }

    // Step 4: License
    public function step4()
    {
        $clientId = $this->licenseService->getClientId();
        return view('install.license', compact('clientId'));
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
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        // Create Client Admin
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'profile' => 'Admin', // Assuming 'Admin' is the role name for business owner
            'status' => 'Active',
        ]);
        
        // Assign Role (assuming Spatie Permission)
        // Note: The Seeder already created roles.
        // We need to ensure 'Admin' role exists or use a specific 'Business Owner' role if you prefer.
        // For now, using 'Admin' as per UserSeeder.
        $user->assignRole('Admin');

        // Create the installed lock file
        file_put_contents(storage_path('installed'), 'JSPOS INSTALLED ON ' . date('Y-m-d H:i:s'));

        // Mark as installed
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
        if (file_exists($path)) {
            $content = file_get_contents($path);
            foreach ($data as $key => $value) {
                // If key exists, replace it
                if (preg_match("/^{$key}=.*/m", $content)) {
                    $content = preg_replace("/^{$key}=.*/m", "{$key}=" . ($value ?? ''), $content);
                } else {
                    // If key doesn't exist, append it
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
        
        // Batch script content
        $content = <<<EOT
@echo off
set "URL={$appUrl}"
set "NAME={$appName}"

echo Creando acceso directo para %NAME%...

:: Create VBS script to create shortcut
echo Set oWS = WScript.CreateObject("WScript.Shell") > "%temp%\CreateShortcut.vbs"
echo sLinkFile = oWS.ExpandEnvironmentStrings("%USERPROFILE%\Desktop\" & "%NAME%.lnk") >> "%temp%\CreateShortcut.vbs"
echo Set oLink = oWS.CreateShortcut(sLinkFile) >> "%temp%\CreateShortcut.vbs"
echo oLink.TargetPath = "chrome.exe" >> "%temp%\CreateShortcut.vbs"
echo oLink.Arguments = "--app=" & "%URL%" >> "%temp%\CreateShortcut.vbs"
echo oLink.IconLocation = "chrome.exe" >> "%temp%\CreateShortcut.vbs"
echo oLink.Save >> "%temp%\CreateShortcut.vbs"

:: Run VBS
cscript //nologo "%temp%\CreateShortcut.vbs"
del "%temp%\CreateShortcut.vbs"

echo.
echo Acceso directo creado exitosamente en el Escritorio.
echo Abriendo sistema...
echo.

:: Launch the app immediately
start "" "chrome.exe" --app="%URL%"

pause
EOT;

        return response($content)
            ->header('Content-Type', 'application/x-bat')
            ->header('Content-Disposition', 'attachment; filename="Instalar_Acceso_Directo.bat"');
    }
}
