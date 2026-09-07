<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendTransactionRequest;
use App\Services\BitGoService;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(CurrencyService $currencyService, BitGoService $bitGoService)
    {
        $userWallet = Auth::User()->wallet;

        $transactions = $bitGoService->transactions(
            $userWallet,
            5,
            request('prevId')
        );

        return inertia('dashboard/dashboard', [
            'bitcoinUsd' => $currencyService->bitcoinUsd(),
            'walletBalance' => $bitGoService->balance($userWallet),
            'addresses' => $bitGoService->addresses($userWallet),
            'transactions' => $transactions['transfers'] ?? [],
            'transactionsNextCursor' => $transactions['nextBatchPrevId'] ?? null,
        ]);
    }

    public function sendTransaction(BitGoService $bitGoService, SendTransactionRequest $request)
    {

        $bitGoService->send(Auth::User()->wallet, $request->address, $request->amount);

        return redirect()->route('dashboard');
    }

    public function addAddress(BitGoService $bitGoService)
    {
        $bitGoService->addAddress(Auth::User()->wallet);

        return redirect()->route('dashboard');
    }
}
