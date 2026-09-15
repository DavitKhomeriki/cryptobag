<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;

class ExternalPaymentService
{
    public function getWallet(string $walletId, string $passphrase): Wallet
    {
        return Wallet::where('passphrase', $passphrase)->where('id', $walletId)->firstOrFail();
    }

    public function getMerchant(string $merchantAddress): User
    {
        return User::with(['merchant', 'wallet'])
            ->whereHas('wallet.walletAddress', function ($query) use ($merchantAddress) {
                $query->where('address', $merchantAddress);
            })
            ->firstOrFail();
    }
}
