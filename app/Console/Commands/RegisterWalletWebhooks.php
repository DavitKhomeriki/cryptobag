<?php

namespace App\Console\Commands;

use App\Jobs\RegisterWalletWebhook;
use App\Models\Wallet;
use App\Services\BitGoService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RegisterWalletWebhooks extends Command
{
    protected $signature = 'bitgo:register-webhooks {--wallet= : Local wallet ID; omit to queue all wallets}';

    protected $description = 'Queue transfer webhook registration, skipping existing matching webhooks';

    public function handle(BitGoService $bitGo): int
    {
        try {
            $bitGo->webhookUrl();
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $walletId = $this->option('wallet');
        $query = Wallet::query();

        if ($walletId !== null) {
            if (! ctype_digit($walletId) || ! Wallet::whereKey($walletId)->exists()) {
                $this->error('Wallet not found. Use the local wallet ID.');

                return self::FAILURE;
            }

            $query->whereKey($walletId);
        }

        $count = 0;
        foreach ($query->lazyById() as $wallet) {
            RegisterWalletWebhook::dispatch($wallet->id);
            $count++;
        }

        $this->info("Queued webhook registration for {$count} wallet(s). Run a queue worker to process them.");

        return self::SUCCESS;
    }
}
