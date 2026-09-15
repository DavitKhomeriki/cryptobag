<?php

use App\Enums\TransactionStatus;
use App\Jobs\DeliverMerchantWebhook;
use App\Models\ExternalPayment;
use App\Models\Merchant;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MerchantWebhookService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

test('merchant webhook delivery sends signed payment updated payload', function () {
    $this->freezeTime();
    Http::preventStrayRequests();
    Http::fake([
        'https://merchant.example.com/webhooks/cryptobag' => Http::response(['ok' => true]),
    ]);
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    $merchant = Merchant::factory()->for($user)->create([
        'default_callback_url' => 'https://merchant.example.com/webhooks/cryptobag',
        'webhook_secret' => 'merchant-webhook-secret',
    ]);
    $payment = ExternalPayment::create([
        'idempotency_key' => 'payment-key-123',
        'order_id_for_merchant' => 'order-123',
        'wallet_id' => $wallet->id,
        'address' => 'merchant-address',
        'amount' => '0.00100000',
        'status' => TransactionStatus::Completed,
        'transfer_id' => 'transfer-123',
    ]);

    (new DeliverMerchantWebhook($payment->id, $merchant->id))->handle(app(MerchantWebhookService::class));

    $expectedPayload = json_encode([
        'event' => 'payment.updated',
        'data' => [
            'payment_id' => (string) $payment->id,
            'order_id' => 'order-123',
            'status' => 'completed',
            'amount' => '0.00100000',
        ],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $expectedSignature = 't='.now()->timestamp.',v1='.hash_hmac('sha256', now()->timestamp.'.'.$expectedPayload, 'merchant-webhook-secret');

    Http::assertSent(fn ($request) => $request->url() === 'https://merchant.example.com/webhooks/cryptobag'
        && $request->method() === 'POST'
        && $request->body() === $expectedPayload
        && $request->hasHeader('X-CryptoBag-Event', 'payment.updated')
        && $request->hasHeader('X-CryptoBag-Timestamp', (string) now()->timestamp)
        && $request->hasHeader('X-CryptoBag-Signature', $expectedSignature)
        && $request->hasHeader('Content-Type', 'application/json'));
});

test('merchant webhook delivery fails for unsuccessful responses', function () {
    Http::preventStrayRequests();
    Http::fake([
        'https://merchant.example.com/webhooks/cryptobag' => Http::response([], 503),
    ]);
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    $merchant = Merchant::factory()->for($user)->create([
        'default_callback_url' => 'https://merchant.example.com/webhooks/cryptobag',
    ]);
    $payment = ExternalPayment::create([
        'idempotency_key' => 'payment-key-123',
        'order_id_for_merchant' => 'order-123',
        'wallet_id' => $wallet->id,
        'address' => 'merchant-address',
        'amount' => '0.00100000',
        'status' => TransactionStatus::Completed,
        'transfer_id' => 'transfer-123',
    ]);

    (new DeliverMerchantWebhook($payment->id, $merchant->id))->handle(app(MerchantWebhookService::class));
})->throws(RequestException::class);
