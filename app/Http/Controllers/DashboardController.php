<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendTransactionRequest;
use App\Services\BitGoService;
use App\Services\CurrencyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(CurrencyService $currencyService, BitGoService $bitGoService): Response
    {
        $user = Auth::user();
        $userWallet = $user->wallet;
        $merchantApiToken = $user->merchant?->apiTokens()
            ->whereNull('revoked_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>', now()))
            ->latest()
            ->first()
            ?->token;

        $transactions = $bitGoService->transactions(
            $userWallet,
            5,
            request('prevId')
        );

        return inertia('dashboard/dashboard', [
            'walletId' => $userWallet->id,
            'passphrase' => $userWallet->passphrase,
            'bitcoinUsd' => $currencyService->bitcoinUsd(),
            'walletBalance' => $bitGoService->balance($userWallet),
            'addresses' => $bitGoService->addresses($userWallet),
            'transactions' => $transactions['transfers'] ?? [],
            'transactionsNextCursor' => $transactions['nextBatchPrevId'] ?? null,
            'merchantApiToken' => $merchantApiToken,
        ]);
    }

    public function sendTransaction(BitGoService $bitGoService, SendTransactionRequest $request): RedirectResponse
    {

        $bitGoService->send(Auth::user()->wallet, $request->address, $request->amount);

        return redirect()->route('dashboard');
    }

    public function addAddress(BitGoService $bitGoService): RedirectResponse
    {
        $bitGoService->addAddress(Auth::user()->wallet);

        return redirect()->route('dashboard');
    }
}
