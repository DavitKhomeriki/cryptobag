<?php

namespace App\Http\Controllers;

use App\Services\BitGoService;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(CurrencyService $currencyService, BitGoService $bitGoService)
    {

        return inertia('dashboard/dashboard', [
            'bitcoinUsd' => $currencyService->bitcoinUsd(),
            'walletBalance' => $bitGoService->balance(Auth::User()->wallet),
        ]);
    }
}
