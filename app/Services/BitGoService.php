<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

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

    public function transactions(
        Wallet $wallet,
        int $limit = 25,
        ?string $prevId = null,
    ): array {
        $query = [
            'limit' => $limit,
        ];

        if ($prevId) {
            $query['prevId'] = $prevId;
        }

        return Http::withToken(config('services.bitgo.token'))
            ->get(
                config('services.bitgo.url')
                ."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}/transfer",
                $query
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

    public function webhookUrl(): string
    {
        $url = config('services.bitgo.webhook_url');
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;

        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)
            || parse_url($url, PHP_URL_SCHEME) !== 'https'
            || ! is_string($host) || ! str_contains($host, '.')
            || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')
            || (filter_var($host, FILTER_VALIDATE_IP) && ! filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
            throw new InvalidArgumentException('Set BITGO_WEBHOOK_URL to your public HTTPS callback URL, including /api/bitgo/webhook.');
        }

        return $url;
    }

    public function ensureTransferWebhook(Wallet $wallet): void
    {
        $url = $this->webhookUrl();

        Cache::lock('bitgo-webhook:'.$wallet->id, 60)->block(5, function () use ($wallet, $url): void {
            $response = Http::withToken(config('services.bitgo.token'))
                ->connectTimeout(5)->timeout(15)
                ->get(config('services.bitgo.url')."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}/webhooks")
                ->throw()->json();

            foreach ($response['webhooks'] as $webhook) {
                if ($webhook['type'] === 'transfer' && $webhook['url'] === $url) {
                    return;
                }
            }

            $this->addTransferWebhook($wallet);
        });
    }

    public function addTransferWebhook(Wallet $wallet): array
    {
        $response = Http::withToken(config('services.bitgo.token'))
            ->connectTimeout(5)->timeout(15)
            ->post(
                config('services.bitgo.url')
                    ."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}/webhooks",
                [
                    'type' => 'transfer',
                    'url' => $this->webhookUrl(),
                    'label' => 'CryptoBag transfer webhook',
                    'numConfirmations' => 0,
                ]
            );

        $response->throw();

        return $response->json();
    }

    public function transfer(Wallet $wallet, string $transferId): array
    {
        return Http::withToken(config('services.bitgo.token'))
            ->get(
                config('services.bitgo.url')
                    ."/api/v2/{$wallet->coin}/wallet/{$wallet->bitgo_wallet_id}/transfer/{$transferId}"
            )
            ->throw()
            ->json();
    }
}
