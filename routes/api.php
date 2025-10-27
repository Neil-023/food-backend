<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AnalyticsController;
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


Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/sellers/{seller}/products', [SellerController::class, 'products']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::post('/categories', [CategoryController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::get('cart', [CartController::class, 'viewCart']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::delete('/products/{product}', [ProductController::class,'destroy']);
    Route::put('/cart/{product}', [CartController::class, 'updateQuantity']);
    Route::get('analytics/frequent-combos',   [AnalyticsController::class, 'frequentCombos']);
    Route::get('/sellers', [SellerController::class, 'index']);
});

Route::middleware('auth:sanctum')->post('/apply-seller', [\App\Http\Controllers\SellerController::class, 'applySeller']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/checkout', [CartController::class, 'checkout']);
Route::middleware('auth:sanctum')
    ->get('/orders', [OrderController::class, 'index']);
Route::get('analytics/top-products',      [AnalyticsController::class, 'topProducts']);
Route::get('analytics/top-categories',    [AnalyticsController::class, 'topCategories']);
Route::middleware('auth:sanctum')->get(
    'analytics/top-customers', 
    [AnalyticsController::class, 'topCustomers']
);
Route::middleware('auth:sanctum')->get('/analytics/recent-orders', [AnalyticsController::class, 'recentOrders']);
Route::middleware('auth:sanctum')->get('/seller/orders', [OrderController::class, 'sellerOrders']);
Route::middleware('auth:sanctum')->put('/orders/{id}/status', [OrderController::class, 'updateOrderStatus']);
Route::patch('/products/{id}/stock', [ProductController::class, 'updateStock']);

