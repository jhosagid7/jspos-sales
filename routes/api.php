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


