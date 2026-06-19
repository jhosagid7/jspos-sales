<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('print', function (Request $request) {
    Log::info(json_encode($request->name));
    return '200 ok';
});

// VIP Customer App Routes
Route::prefix('vip')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\Vip\CustomerAuthController::class, 'login']);

    // Protected VIP Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [App\Http\Controllers\Api\Vip\CustomerAuthController::class, 'me']);
        Route::post('/logout', [App\Http\Controllers\Api\Vip\CustomerAuthController::class, 'logout']);
        
        Route::get('/products', [App\Http\Controllers\Api\Vip\ProductController::class, 'index']);
        Route::get('/orders', [App\Http\Controllers\Api\Vip\OrderController::class, 'index']);
        Route::post('/orders', [App\Http\Controllers\Api\Vip\OrderController::class, 'store']);
        Route::get('/orders/{id}/logs', [App\Http\Controllers\Api\Vip\OrderController::class, 'logs']);
        Route::post('/orders/{id}/send', [App\Http\Controllers\Api\Vip\OrderController::class, 'sendToOffice']);
        Route::delete('/orders/{id}', [App\Http\Controllers\Api\Vip\OrderController::class, 'destroy']);

        // Sales (Purchases) Module
        Route::get('/sales', [App\Http\Controllers\Api\Vip\SaleController::class, 'index']);

        // Payments Upload Module for VIP
        Route::get('/payments/form-data', [App\Http\Controllers\Api\Vip\PaymentController::class, 'formData']);
        Route::get('/sales/pending', [App\Http\Controllers\Api\Vip\PaymentController::class, 'pendingSales']);
        Route::post('/payments/upload', [App\Http\Controllers\Api\Vip\PaymentController::class, 'upload']);
        Route::get('/payments/upload', [App\Http\Controllers\Api\Vip\PaymentController::class, 'history']); // Used dynamically sometimes
        Route::get('/payments/history', [App\Http\Controllers\Api\Vip\PaymentController::class, 'history']);
        Route::get('/payments/history/global', [App\Http\Controllers\Api\Vip\PaymentController::class, 'globalHistory']);
    });
});

// Mobile App Routes
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index']);
    Route::get('/customers', [App\Http\Controllers\Api\CustomerController::class, 'index']);
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/{id}/logs', [App\Http\Controllers\Api\OrderController::class, 'logs']);
    Route::post('/orders/{id}/send', [App\Http\Controllers\Api\OrderController::class, 'sendToOffice']);
    Route::delete('/orders/{id}', [App\Http\Controllers\Api\OrderController::class, 'destroy']);

    // Payments Upload Module
    Route::get('/payments/form-data', [App\Http\Controllers\Api\PaymentController::class, 'formData']);
    Route::get('/sales/pending', [App\Http\Controllers\Api\PaymentController::class, 'pendingSales']);
    Route::post('/payments/upload', [App\Http\Controllers\Api\PaymentController::class, 'upload']);
    Route::get('/payments/upload', [App\Http\Controllers\Api\PaymentController::class, 'history']); // Existing but improved later
    Route::get('/payments/history', [App\Http\Controllers\Api\PaymentController::class, 'history']);
    Route::get('/payments/history/global', [App\Http\Controllers\Api\PaymentController::class, 'globalHistory']);

    // Dashboard
    Route::get('/seller/dashboard', [App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('/seller/dashboard/commissions', [App\Http\Controllers\Api\DashboardController::class, 'commissions']);
    Route::get('/seller/dashboard/debt', [App\Http\Controllers\Api\DashboardController::class, 'debt']);
});


// Soplados Manufacturing App Routes
Route::prefix('soplados')->middleware('auth:sanctum')->group(function () {
    // Shifts
    Route::get('/shifts/current', [App\Http\Controllers\Api\Soplados\ShiftController::class, 'current']);
    Route::get('/shifts/history', [App\Http\Controllers\Api\Soplados\ShiftController::class, 'history']);
    Route::post('/shifts/open', [App\Http\Controllers\Api\Soplados\ShiftController::class, 'open']);
    Route::post('/shifts/close', [App\Http\Controllers\Api\Soplados\ShiftController::class, 'close']);

    // Production
    Route::get('/products', [App\Http\Controllers\Api\Soplados\ProductionController::class, 'products']);
    Route::get('/products/{id}/formula', [App\Http\Controllers\Api\Soplados\ProductionController::class, 'formula']);
    Route::get('/production/history', [App\Http\Controllers\Api\Soplados\ProductionController::class, 'history']);
    Route::post('/production', [App\Http\Controllers\Api\Soplados\ProductionController::class, 'store']);

    // Transfers
    Route::get('/transfers/counts', [App\Http\Controllers\Api\Soplados\TransferController::class, 'counts']);
    Route::get('/transfers/pending', [App\Http\Controllers\Api\Soplados\TransferController::class, 'pending']);
    Route::post('/transfers/{id}/dispatch', [App\Http\Controllers\Api\Soplados\TransferController::class, 'dispatchTransfer']);
    Route::get('/transfers/returns/pending', [App\Http\Controllers\Api\Soplados\TransferController::class, 'pendingReturns']);
    Route::post('/transfers/{id}/returns/receive', [App\Http\Controllers\Api\Soplados\TransferController::class, 'receiveReturn']);

    // Inventory & Receipts
    Route::get('/inventory', [App\Http\Controllers\Api\Soplados\InventoryController::class, 'index']);
    Route::get('/receipts/pending', [App\Http\Controllers\Api\Soplados\InventoryController::class, 'pendingReceipts']);
    Route::post('/receipts/{id}/receive', [App\Http\Controllers\Api\Soplados\InventoryController::class, 'receiveReceipt']);
});

// Bolsas Manufacturing App Routes
Route::prefix('bolsas')->middleware('auth:sanctum')->group(function () {
    Route::get('/products', [App\Http\Controllers\Api\BagsProductionApiController::class, 'products']);
    Route::post('/production', [App\Http\Controllers\Api\BagsProductionApiController::class, 'store']);
    Route::get('/production/history', [App\Http\Controllers\Api\BagsProductionApiController::class, 'history']);
});
