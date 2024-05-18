<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

// Registration route
Route::post('/register', [AuthController::class, 'register']);

// Otp route
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);

//resent OTP route
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);


// Login route
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-token', [AuthController::class, 'validateToken']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::post('/reset-password', [AuthController::class,'resetPassword']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::put('/user', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::post('/user/update', [AuthController::class, 'update'])->middleware('auth:sanctum');
Route::post('/contact/save', [AuthController::class, 'store']);
Route::post('/store-rare-medicine', [AuthController::class, 'storeRareMedicine'])->middleware('auth:sanctum');

Route::post('/upload-prescription', [AuthController::class, 'uploadPrescription']);
Route::post('/upload-medicalTest', [AuthController::class, 'uploadMedicalTest']);

Route::get('/products', [AuthController::class, 'product']);
Route::get('/products/{product}', [AuthController::class, 'show']);
Route::post('/favorites/add/{productId}', [AuthController::class, 'add'])->middleware('auth:sanctum');
Route::get('/favorites', [AuthController::class, 'listFavorites'])->middleware('auth:sanctum');
Route::delete('/favorites/remove/{id}', [AuthController::class, 'removeFromFavorites'])->middleware('auth:sanctum');
Route::delete('/favorites/clear', [AuthController::class, 'clearAll'])->middleware('auth:sanctum');
Route::post('/create-payment-intent', [AuthController::class, 'createPaymentIntent']);
Route::get('/products/category/{categorySlug}', [AuthController::class, 'getByCategory']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add', [AuthController::class, 'addToCart']);
    Route::delete('/cart/clear', [AuthController::class, 'clearCart']);
    Route::get('/cart', [AuthController::class, 'getCart']);
    Route::patch('/cart/{cartItem}', [AuthController::class, 'updateQuantity']);
    Route::delete('/cart/{cartItem}', [AuthController::class, 'removeItem']);
    Route::get('/cart/quantity', [AuthController::class, 'getCartQuantity']);

});
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/feedback', [AuthController::class, 'storeFeedback']);
    Route::get('/feedback', [AuthController::class, 'index']);
});


Route::post('/checkout', [AuthController::class, 'checkout'])->middleware('auth:sanctum');
Route::get('/orders', [AuthController::class, 'getOrders'])->middleware('auth:sanctum');
Route::post('/donate', [AuthController::class, 'initiateDonation']);
Route::post('/payment/callback', [AuthController::class, 'handleCallback'])->middleware('auth:sanctum');
Route::get('/check-payment-status/{paymentId}', [AuthController::class, 'checkPaymentStatus']);


Route::get('/test', function () {
    return 'API is working!';
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
