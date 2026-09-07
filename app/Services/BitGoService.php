<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;

class BitGoService
{
    public function wallets(): array
    {
        return Http::withToken(config('services.bitgo.token'))
            ->get(config('services.bitgo.url').'/api/v2/tbtc4/wallet')
            ->throw()
            ->json();
    }

    public function wallet(Wallet $wallet): array
    {
        return Http::withToken(config('services.bitgo.token'))
            ->get(
                config('services.bitgo.url')
                ."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}"
            )
            ->throw()
            ->json();
    }

    public function balance(Wallet $wallet): float
    {
        $data = $this->wallet($wallet);

        return ((int) $data['balanceString']) / 100_000_000;
    }

    public function addresses(Wallet $wallet): array
    {
        return Http::withToken(config('services.bitgo.token'))
            ->get(
                config('services.bitgo.url')
                ."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}/addresses"
            )
            ->throw()
            ->json();
    }

    public function addAddress(Wallet $wallet): array
    {
        return Http::withToken(config('services.bitgo.token'))
            ->post(
                config('services.bitgo.url')
                ."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}/address", [
                    'label' => 'Receive Address',
                ]
            )
            ->throw()
            ->json();
    }

    public function send(
        Wallet $wallet,
        string $address,
        float $amount,
    ): array {

        $amountInSatoshis = (int) round(
            ((float) $amount) * 100_000_000
        );

        return Http::withToken(config('services.bitgo.token'))
            ->post(
                config('services.bitgo.express_url')
            ."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}/sendcoins",
                [
                    'address' => $address,
                    'amount' => (string) $amountInSatoshis,
                    'walletPassphrase' => $wallet->passphrase,
                ]
            )
            ->throw()
            ->json();
    }

    public function generateWallet(User $user, string $passphrase): array
    {

        return Http::withToken(config('services.bitgo.token'))
            ->post(config('services.bitgo.express_url')
            .'/api/v2/tbtc4/wallet/generate', [
                'label' => "User {$user->id} Wallet",
                'passphrase' => $passphrase,
                'type' => 'hot',
                'multisigType' => 'onchain',
                'enterprise' => config('services.bitgo.enterprise_id'),
            ])
            ->throw()
            ->json();
    }
}
