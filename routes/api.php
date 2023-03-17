<?php

use App\Http\Controllers\api\StripeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Stripe Payment
Route::prefix('stripe')->group(function () {
    Route::post('payment' , [StripeController::class, 'payment']);
    Route::any('paymentSuccess' , [StripeController::class, 'paymentSuccess'])->name('paymentSuccess');
    Route::any('paymentFail' , [StripeController::class, 'paymentFail'])->name('paymentFail');
    Route::post('webhook' , [StripeController::class, 'webhookHandler']);
});
