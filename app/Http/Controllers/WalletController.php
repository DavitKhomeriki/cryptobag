<?php

namespace App\Http\Controllers;

use App\Services\BitGoService;

class WalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(BitGoService $bitGo)
    {
        $wallets = $bitGo->wallets();

        return inertia('wallets/wallet', [
            'wallets' => $wallets,
        ]);
    }
}
