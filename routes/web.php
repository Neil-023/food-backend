<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CartController;
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
Route::post('login', [AuthController::class, 'login']);

Route::get('/', function () {
    return view('welcome');
});
Route::middleware(['web'])->group(function () {
  Route::post('/add-to-cart', [CartController::class, 'addToCart']);
});
Route::get("/__db", function(){
    return dd([
      'DB_CONNECTION' => env('DB_CONNECTION'),
      'DB_DATABASE'   => env('DB_DATABASE'),
      'default_conn'  => config('database.default'),
      'connections'   => array_keys(config('database.connections')),
    ]);
  });
  