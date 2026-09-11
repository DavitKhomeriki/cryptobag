<?php

namespace App\Jobs;

use App\Models\Wallet;
use App\Services\BitGoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RegisterWalletWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 50;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public int $walletId) {}

    public function handle(BitGoService $bitGo): void
    {
        $wallet = Wallet::find($this->walletId);

        if ($wallet) {
            $bitGo->ensureTransferWebhook($wallet);
        }
    }
}
