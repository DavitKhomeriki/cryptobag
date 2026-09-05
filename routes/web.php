<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// for Dev
Route::get('/wallets', [WalletController::class, 'index'])
    ->middleware('auth')
    ->name('wallets.index');

require __DIR__.'/settings.php';
