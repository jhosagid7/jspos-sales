<?php

use App\Livewire\Roles;
use App\Livewire\Sales;
use App\Livewire\Users;


use App\Livewire\Tester;
use App\Livewire\Welcome;
use App\Events\PrintEvent;
use App\Livewire\Products;
use App\Livewire\Settings;
use App\Livewire\CashCount;
use App\Livewire\Customers;
use App\Livewire\Inventory;
use App\Livewire\Purchases;
use App\Livewire\Suppliers;
use App\Livewire\Categories;
use App\Livewire\SalesReport;
use App\Livewire\AsignarPermisos;
use App\Livewire\PurchasesReport;
use Illuminate\Support\Facades\Route;
use App\Livewire\AccountsPayableReport;
use App\Http\Controllers\DataController;
use App\Livewire\AccountsReceivableReport;
use App\Http\Controllers\ProfileController;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('auth.login');
});

// License Routes
Route::get('/license/expired', [\App\Http\Controllers\LicenseController::class, 'expired'])->name('license.expired');
Route::post('/license/activate', [\App\Http\Controllers\LicenseController::class, 'activate'])->name('license.activate');

Route::post('/license/activate', [\App\Http\Controllers\LicenseController::class, 'activate'])->name('license.activate');

// Installation Routes
Route::prefix('install')->name('install.')->group(function () {
    Route::get('/', [\App\Http\Controllers\InstallController::class, 'index'])->name('index');
    Route::get('/step1', [\App\Http\Controllers\InstallController::class, 'step1'])->name('step1');
    Route::get('/step2', [\App\Http\Controllers\InstallController::class, 'step2'])->name('step2');
    Route::post('/step2', [\App\Http\Controllers\InstallController::class, 'saveDatabase'])->name('saveDatabase');
    Route::get('/step3', [\App\Http\Controllers\InstallController::class, 'step3'])->name('step3');
    Route::post('/step3', [\App\Http\Controllers\InstallController::class, 'runMigrations'])->name('runMigrations');
    Route::get('/step4', [\App\Http\Controllers\InstallController::class, 'step4'])->name('step4');
    Route::post('/step4', [\App\Http\Controllers\InstallController::class, 'activateLicense'])->name('activateLicense');
    Route::get('/step5', [\App\Http\Controllers\InstallController::class, 'step5'])->name('step5');
    Route::post('/step5', [\App\Http\Controllers\InstallController::class, 'createAdmin'])->name('createAdmin');
    Route::get('/finish', [\App\Http\Controllers\InstallController::class, 'finish'])->name('finish');
    Route::get('/download-shortcut', [\App\Http\Controllers\InstallController::class, 'downloadShortcut'])->name('downloadShortcut');
});

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('Driver')) {
        return redirect()->route('driver.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware('auth')->group(function () {
    Route::get('welcome', Welcome::class)->name('welcome');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});



Route::middleware('auth')->group(function () {


    Route::get('categories', Categories::class)->name('categories')->middleware('can:categorias');
    Route::get('products', Products::class)->name('products')->middleware('can:productos');
    Route::get('suppliers', Suppliers::class)->name('suppliers')->middleware('can:proveedores');
    Route::get('customers', Customers::class)->name('customers')->middleware('can:clientes');
    Route::get('sales', Sales::class)->name('sales')->middleware(['can:sales.create', \App\Http\Middleware\EnsureCashRegisterIsOpen::class]);

    Route::get('purchases', Purchases::class)->name('purchases')->middleware('can:compras');
    Route::get('inventories', Inventory::class)->name('inventories')->middleware('can:inventarios');
    Route::get('warehouses', \App\Livewire\Warehouses::class)->name('warehouses');
    Route::get('transfers', \App\Livewire\Transfers::class)->name('transfers');
    Route::get('requisition', \App\Livewire\Requisition::class)->name('requisition');
    Route::get('cargos', \App\Livewire\Cargos\CargosList::class)->name('cargos');
    Route::get('cargos/create', \App\Livewire\Cargos\CreateCargo::class)->name('cargos.create');
    Route::get('cargos/{cargo}/pdf', [\App\Http\Controllers\CargoController::class, 'pdf'])->name('cargos.pdf');

    // Production Routes
    Route::get('production', \App\Livewire\Production\ProductionList::class)->name('production.index')->middleware('can:manage_production');
    Route::get('production/create/{production?}', \App\Livewire\Production\CreateProduction::class)->name('production.create')->middleware('can:manage_production');
    Route::get('production/{id}/pdf', [\App\Http\Controllers\ProductionController::class, 'pdf'])->name('production.pdf')->middleware('can:manage_production');

    Route::get('descargos', \App\Livewire\Descargos\DescargosList::class)->name('descargos');
    Route::get('descargos/create', \App\Livewire\Descargos\CreateDescargo::class)->name('descargos.create');
    Route::get('descargos/{descargo}/pdf', [\App\Http\Controllers\DescargoController::class, 'pdf'])->name('descargos.pdf');


    //personas / roles y permisos
    Route::get('users', Users::class)->name('users')->middleware('can:usuarios');
    Route::get('roles', Roles::class)->name('roles')->middleware('can:roles');
    Route::get('asignar', AsignarPermisos::class)->name('asignar')->middleware('can:asignacion');
    Route::get('asignar', AsignarPermisos::class)->name('asignar')->middleware('can:asignacion');
    Route::get('commissions', \App\Livewire\Commissions::class)->name('commissions');
    
    // Label Generator
    Route::get('labels', \App\Livewire\LabelGenerator::class)->name('labels.index');
    Route::get('labels/pdf', [\App\Http\Controllers\LabelController::class, 'generate'])->name('labels.pdf');



    //data
    Route::get('data/customers', [DataController::class, 'autocomplete_customers'])->name('data.customers');
    Route::get('data/suppliers', [DataController::class, 'autocomplete_suppliers'])->name('data.suppliers');
    Route::get('data/products', [DataController::class, 'autocomplete_products'])->name('data.products');


    //reports
    Route::prefix('reports')->group(function () {
        Route::get('sales', SalesReport::class)->name('reports.sales')->middleware('can:reportes');
        Route::get('purchases', PurchasesReport::class)->name('reports.purchases')->middleware('can:reportes');
        Route::get('accounts-receivable', AccountsReceivableReport::class)->name('reports.accounts.receivable')->middleware('can:reportes');
        Route::get('accounts-payables', AccountsPayableReport::class)->name('reports.accounts.payables')->middleware('can:reportes');
        Route::get('payment-relationship', \App\Livewire\Reports\PaymentRelationshipReport::class)->name('reports.payment.relationship')->middleware('can:reportes');
        Route::get('daily-sales', \App\Livewire\Reports\DailySalesReport::class)->name('reports.daily.sales')->middleware('can:reportes');
        Route::get('commissions', \App\Livewire\CommissionReport::class)->name('reports.commissions')->middleware('can:reportes');
        Route::get('best-sellers', \App\Livewire\Reports\BestSellers::class)->name('reports.best.sellers')->middleware('can:reportes');
        Route::get('rotation', \App\Livewire\Reports\RotationReport::class)->name('reports.rotation')->middleware('can:reportes');
    });

    //corte de caja
    Route::get('cash-count', CashCount::class)->name('cash.count');

    //settings
    Route::get('settings', Settings::class)->name('settings');
    Route::get('updates', \App\Livewire\Settings\UpdateSystem::class)->name('updates');
    Route::get('backups', \App\Livewire\Settings\Backups::class)->name('backups');
    Route::get('backups/download/{fileName}', [\App\Http\Controllers\BackupController::class, 'download'])->name('backups.download');
    Route::get('devices', \App\Livewire\Settings\DeviceManager::class)->name('devices');

    // Delivery Routes
    Route::get('driver/dashboard', \App\Livewire\DriverDashboard::class)->name('driver.dashboard');
    Route::get('delivery/tracking/{sale}', \App\Livewire\DeliveryTracking::class)->name('delivery.tracking');
    Route::get('delivery/map', \App\Livewire\LiveDriverMap::class)->name('delivery.map');

    //generate pdf invoices
    Route::get('sales/{sale}', [Sales::class, 'generatePdfInvoice'])->name('pos.sales.generatePdfInvoice');
    //generate pdf orders invoices
    //generate pdf orders invoices
    Route::get('orders/{order}', [Sales::class, 'generatePdfOrderInvoice'])->name('pos.orders.generatePdfOrderInvoice');

    // Cash Register Routes
    Route::get('cash-register/open', \App\Livewire\CashRegisterOpen::class)->name('cash-register.open')->middleware('can:cash_register.open');
    Route::get('cash-register/close', \App\Livewire\CashRegister::class)->name('cash-register.close');
});




//provar event
// Route::get('evento', function () {
//     $users = \App\Models\User::all();
//     event(new PrintEvent(json_encode($users)));
//     return 'event ok';
// });



// Route::get('tester', Tester::class);


Route::get('/access-denied', function () {
    return view('errors.access-denied');
})->name('access.denied');

// System Update Routes
Route::prefix('system')->name('system.')->group(function () {
    Route::get('/upgrade-db', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            return redirect('/dashboard')->with('success', 'Base de datos actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    })->name('upgrade-db');
});

require __DIR__ . '/auth.php';



Route::get('/fix-driver-role', function () {
    try {
        // Create Role if not exists
        if (!\Spatie\Permission\Models\Role::where('name', 'Driver')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'Driver', 'guard_name' => 'web', 'level' => 10]);
        }
        
        // Clear Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        return "Rol 'Driver' creado y caché limpiada correctamente. <a href='/dashboard'>Volver al Dashboard</a>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/fix-super-admin', function () {
    try {
        $user = \App\Models\User::where('email', 'jhosagid77@gmail.com')->first();
        if (!$user) return "Usuario no encontrado";
        
        // Ensure Admin role exists
        if (!\Spatie\Permission\Models\Role::where('name', 'Admin')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'Admin', 'guard_name' => 'web', 'level' => 100]);
        }
        
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'Admin')->first();
        $user->assignRole($adminRole);
        
        // Clear Cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        return "Rol Admin asignado a {$user->name} y caché limpiada. <a href='/dashboard'>Volver</a>";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});
