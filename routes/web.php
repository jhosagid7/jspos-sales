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

Route::get('/dashboard', function () {
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
    Route::get('sales', Sales::class)->name('sales')->middleware('can:ventas');

    Route::get('purchases', Purchases::class)->name('purchases')->middleware('can:compras');
    Route::get('inventories', Inventory::class)->name('inventories')->middleware('can:inventarios');


    //personas / roles y permisos
    Route::get('users', Users::class)->name('users')->middleware('can:usuarios');
    Route::get('roles', Roles::class)->name('roles')->middleware('can:roles');
    Route::get('asignar', AsignarPermisos::class)->name('asignar')->middleware('can:asignacion');



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
    });

    //corte de caja
    Route::get('cash-count', CashCount::class)->name('cash.count');

    //settings
    Route::get('settings', Settings::class)->name('settings');

    //generate pdf invoices
    Route::get('sales/{sale}', [Sales::class, 'generatePdfInvoice'])->name('pos.sales.generatePdfInvoice');
    //generate pdf orders invoices
    Route::get('orders/{order}', [Sales::class, 'generatePdfOrderInvoice'])->name('pos.orders.generatePdfOrderInvoice');
});




//provar event
// Route::get('evento', function () {
//     $users = \App\Models\User::all();
//     event(new PrintEvent(json_encode($users)));
//     return 'event ok';
// });

// Route::get('tester', Tester::class);

require __DIR__ . '/auth.php';




