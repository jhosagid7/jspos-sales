<?php

use App\Livewire\Roles;
use App\Livewire\Sales;
use App\Livewire\Users;
use App\Livewire\PriceGroups;


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
use App\Livewire\Reports\DispatchReport;
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
    return redirect()->route('welcome');
})->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware('auth')->group(function () {
    Route::get('welcome', Welcome::class)->name('welcome');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/user/theme', [App\Http\Controllers\ThemeController::class, 'update'])->name('user.theme.update');
});



Route::middleware('auth')->group(function () {


    Route::get('categories', Categories::class)->name('categories')->middleware('can:categories.index');
    Route::get('products/import', \App\Livewire\ProductImport::class)->name('products.import')->middleware('can:products.import');
    Route::get('products', Products::class)->name('products')->middleware('can:products.index');
    Route::get('price-groups', PriceGroups::class)->name('price-groups')->middleware('can:products.index');
    Route::get('suppliers', Suppliers::class)->name('suppliers')->middleware('can:suppliers.index');
    Route::get('customers/import', \App\Livewire\CustomerImport::class)->name('customers.import')->middleware('can:customers.import');
    Route::get('customers', Customers::class)->name('customers')->middleware('can:customers.index');
    Route::get('sales', Sales::class)->name('sales')->middleware(['can:sales.index', \App\Http\Middleware\EnsureCashRegisterIsOpen::class]);

    Route::get('purchases', Purchases::class)->name('purchases')->middleware(['can:purchases.create', 'module:module_purchases']); // Usually create
    Route::get('purchase-list', \App\Livewire\PurchaseList::class)->name('purchase.list')->middleware(['can:purchases.index', 'module:module_purchases']);
    Route::get('inventories', Inventory::class)->name('inventories')->middleware('can:inventory.index');
    Route::get('warehouses', \App\Livewire\Warehouses::class)->name('warehouses')->middleware('can:warehouses.index');
    Route::get('transfers', \App\Livewire\Transfers::class)->name('transfers')->middleware(['can:transfers.create', 'module:module_multi_warehouse']);
    Route::get('requisition', \App\Livewire\Requisition::class)->name('requisition')->middleware(['can:transfers.create', 'module:module_multi_warehouse']); // Or inventory.index?
    Route::get('cargos', \App\Livewire\Cargos\CargosList::class)->name('cargos')->middleware('can:adjustments.create');
    Route::get('cargos/create', \App\Livewire\Cargos\CreateCargo::class)->name('cargos.create')->middleware('can:adjustments.create');
    Route::get('cargos/{cargo}/pdf', [\App\Http\Controllers\CargoController::class, 'pdf'])->name('cargos.pdf')->middleware('can:adjustments.create');

    // Production Routes
    Route::get('production', \App\Livewire\Production\ProductionList::class)->name('production.index')->middleware(['can:production.index', 'module:module_production']);
    Route::get('production/create/{production?}', \App\Livewire\Production\CreateProduction::class)->name('production.create')->middleware(['can:production.create', 'module:module_production']);
    Route::get('production/{id}/pdf', [\App\Http\Controllers\ProductionController::class, 'pdf'])->name('production.pdf')->middleware(['can:production.index', 'module:module_production']);

    Route::get('descargos', \App\Livewire\Descargos\DescargosList::class)->name('descargos')->middleware('can:adjustments.create');
    Route::get('descargos/create', \App\Livewire\Descargos\CreateDescargo::class)->name('descargos.create')->middleware('can:adjustments.create');
    Route::get('descargos/{descargo}/pdf', [\App\Http\Controllers\DescargoController::class, 'pdf'])->name('descargos.pdf')->middleware('can:adjustments.create');


    //personas / roles y permisos
    Route::get('users', Users::class)->name('users')->middleware('can:users.index');
    Route::get('roles', Roles::class)->name('roles')->middleware(['can:roles.index', 'module:module_roles']);
    Route::get('asignar', AsignarPermisos::class)->name('asignar')->middleware(['can:permissions.assign', 'module:module_roles']);
    Route::get('commissions', \App\Livewire\Commissions::class)->name('commissions')->middleware(['can:reports.commissions', 'module:module_commissions']); // Or a specific manage permission?
    
    // Label Generator
    Route::get('labels', \App\Livewire\LabelGenerator::class)->name('labels.index')->middleware(['can:products.labels', 'module:module_labels']);
    Route::get('labels/pdf', [\App\Http\Controllers\LabelController::class, 'generate'])->name('labels.pdf')->middleware(['can:products.labels', 'module:module_labels']);



    //data
    Route::get('data/customers', [DataController::class, 'autocomplete_customers'])->name('data.customers');
    Route::get('data/suppliers', [DataController::class, 'autocomplete_suppliers'])->name('data.suppliers');
    Route::get('data/products', [DataController::class, 'autocomplete_products'])->name('data.products');
    Route::get('customer/{id}/debt-pdf', [DataController::class, 'customerDebtPdf'])->name('customer.debt.pdf');


    //reports
    Route::prefix('reports')->group(function () {
        Route::get('sales', SalesReport::class)->name('reports.sales')->middleware('can:reports.sales');
        Route::get('purchases', PurchasesReport::class)->name('reports.purchases')->middleware(['can:reports.purchases', 'module:module_purchases']);
        Route::get('accounts-receivable', AccountsReceivableReport::class)->name('reports.accounts.receivable')->middleware(['can:reports.financial', 'module:module_credits']);
        Route::get('accounts-payables', AccountsPayableReport::class)->name('reports.accounts.payables')->middleware(['can:reports.financial', 'module:module_purchases']);
        Route::get('payment-relationship', \App\Livewire\Reports\PaymentRelationshipReport::class)->name('reports.payment.relationship')->middleware(['can:reports.sales', 'module:module_credits']);
        Route::get('collection-relationship/{sheet}/pdf', [\App\Http\Controllers\ReportController::class, 'collectionRelationshipPdf'])->name('reports.collection.relationship.pdf');
        Route::get('daily-sales', \App\Livewire\Reports\DailySalesReport::class)->name('reports.daily.sales')->middleware('can:reports.sales');
        Route::get('daily-sales/pdf', [\App\Http\Controllers\ReportController::class, 'dailySalesPdf'])->name('reports.daily.sales.pdf');
        Route::get('commissions', \App\Livewire\CommissionReport::class)->name('reports.commissions')->middleware(['can:reports.sales', 'module:module_commissions']); // reports.commissions?
        Route::get('best-sellers', \App\Livewire\Reports\BestSellers::class)->name('reports.best.sellers')->middleware('can:reports.sales');
        Route::get('rotation', \App\Livewire\Reports\RotationReport::class)->name('reports.rotation')->middleware(['can:reports.stock', 'module:module_advanced_reports']);
        Route::get('dispatch', DispatchReport::class)->name('reports.dispatch')->middleware(['can:reports.sales', 'module:module_delivery']);
        Route::get('dispatch/pdf', [\App\Http\Controllers\ReportController::class, 'dispatchPdf'])->name('reports.dispatch.pdf')->middleware(['can:reports.sales', 'module:module_delivery']);
        Route::get('settlement/pdf', [\App\Http\Controllers\ReportController::class, 'settlementPdf'])->name('reports.settlement.pdf')->middleware(['can:reports.sales', 'module:module_delivery']);
        Route::get('purchases/{purchase}/pdf', [\App\Http\Controllers\PurchaseController::class, 'pdf'])->name('purchases.pdf')->middleware(['can:purchases.index', 'module:module_purchases']);
        Route::get('reports/inventory', \App\Livewire\Reports\InventoryReport::class)->name('reports.inventory')->middleware(['can:reports.sales']);
        Route::get('reports/inventory/pdf', [\App\Http\Controllers\ReportController::class, 'inventoryPdf'])->name('reports.inventory.pdf')->middleware(['can:reports.sales']);
    });

    // Consultas
    Route::get('consultation/zelle', \App\Livewire\Consultation\ZelleConsultation::class)->name('consultation.zelle')->middleware(['can:zelle_index', 'module:module_advanced_payments']);
    Route::get('consultation/zelle/{id}/pdf', [\App\Http\Controllers\PaymentConsultationController::class, 'generateZellePdf'])->name('zelle.pdf')->middleware(['can:zelle_print_pdf', 'module:module_advanced_payments']);
    
    Route::get('consultation/bank', \App\Livewire\Consultation\BankConsultation::class)->name('consultation.bank')->middleware(['can:bank_index', 'module:module_advanced_payments']);
    Route::get('consultation/bank/{id}/pdf', [\App\Http\Controllers\PaymentConsultationController::class, 'generateBankPdf'])->name('bank.pdf')->middleware(['can:bank_print_pdf', 'module:module_advanced_payments']);

    //corte de caja
    Route::get('cash-count', CashCount::class)->name('cash.count')->middleware('can:cash_register.close');
    Route::get('cash-count/pdf', [\App\Http\Controllers\ReportController::class, 'cashCountPdf'])->name('reports.cash.count.pdf')->middleware('can:cash_register.close');

    //settings
    Route::get('settings', Settings::class)->name('settings')->middleware('can:settings.index');
    Route::get('updates', \App\Livewire\Settings\UpdateSystem::class)->name('updates')->middleware(['can:settings.update', 'module:module_updates']);
    Route::get('backups', \App\Livewire\Settings\Backups::class)->name('backups')->middleware(['can:settings.backups', 'module:module_backups']);
    Route::get('backups/download/{fileName}', [\App\Http\Controllers\BackupController::class, 'download'])->name('backups.download')->middleware(['can:settings.backups', 'module:module_backups']);
    Route::get('devices', \App\Livewire\Settings\DeviceManager::class)->name('devices')->middleware('can:settings.index');
    
    // WhatsApp
    Route::get('settings/whatsapp', \App\Livewire\Settings\WhatsappSettings::class)->name('settings.whatsapp')->middleware(['can:settings.index', 'module:module_whatsapp']);
    Route::get('settings/whatsapp-outbox', \App\Livewire\Settings\WhatsappOutbox::class)->name('settings.whatsapp_outbox')->middleware(['can:settings.index', 'module:module_whatsapp']);
    Route::get('settings/license-generator', \App\Livewire\Settings\LicenseGenerator::class)->name('settings.license_generator')->middleware('role:Super Admin');
    
    Route::get('whatsapp/download-pdf/{msgId}', function($msgId) {
        $msg = \App\Models\WhatsappMessage::findOrFail($msgId);
        
        if ($msg->attachment_path && file_exists($msg->attachment_path)) {
            return response()->file($msg->attachment_path);
        }
        
        abort(404, 'PDF no encontrado o ya fue eliminado del servidor.');
    })->name('whatsapp.download-pdf');

    // Delivery Routes
    Route::get('driver/dashboard/{driverId?}', \App\Livewire\DriverDashboard::class)->name('driver.dashboard'); // Maybe add permission?
    Route::get('delivery/tracking/{sale}', \App\Livewire\DeliveryTracking::class)->name('delivery.tracking')->middleware('can:sales.index');
    Route::get('delivery/map', \App\Livewire\LiveDriverMap::class)->name('delivery.map')->middleware('can:sales.index');

    //generate pdf invoices
    Route::get('sales/{sale}', [Sales::class, 'generatePdfInvoice'])->name('pos.sales.generatePdfInvoice')->middleware('can:sales.pdf');
    Route::get('sales/{sale}/original', [Sales::class, 'generatePdfInvoiceOriginal'])->name('pos.sales.generatePdfInvoiceOriginal')->middleware('can:sales.pdf');
    Route::get('sales/{sale}/internal', [Sales::class, 'generatePdfInternalInvoice'])->name('pos.sales.generatePdfInternal')->middleware('can:sales.pdf');
    Route::get('sales/{sale}/internal-original', [Sales::class, 'generatePdfInternalInvoiceOriginal'])->name('pos.sales.generatePdfInternalOriginal')->middleware('can:sales.pdf');
    Route::get('returns/{saleReturn}/pdf', [Sales::class, 'generateCreditNotePdfEndpoint'])->name('pos.returns.generateCreditNotePdf')->middleware('can:sales.pdf');
    //generate pdf orders invoices
    Route::get('orders/{order}', [Sales::class, 'generatePdfOrderInvoice'])->name('pos.orders.generatePdfOrderInvoice')->middleware('can:sales.pdf');

    // Price List Generator
    Route::get('price-list', \App\Livewire\PriceListGenerator::class)->name('price-list.index')->middleware(['auth', 'can:sales.generate_price_list']);

    // Cash Register Routes
    Route::get('cash-register/open', \App\Livewire\CashRegisterOpen::class)->name('cash-register.open')->middleware('can:cash_register.open');
    Route::get('cash-register/close', \App\Livewire\CashRegister::class)->name('cash-register.close')->middleware('can:cash_register.close');
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
