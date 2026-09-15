<?php

use App\Http\Controllers\ExternalPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/bitgo/payment', [ExternalPaymentController::class, 'store'])->name('api.bitgo.payment');
});
