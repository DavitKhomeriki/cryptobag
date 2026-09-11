<?php

use App\Jobs\RegisterWalletWebhook;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BitGoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.bitgo.url' => 'https://bitgo.test', 'services.bitgo.webhook_url' => 'https://wallet.example.com/api/bitgo/webhook']);
});

test('registration creates only one webhook when a job is run again', function () {
    $wallet = Wallet::factory()->for(User::factory())->create(['bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test']);
    $endpoint = 'https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/webhooks';
    Http::fake([$endpoint => Http::sequence()
        ->push(['webhooks' => []])
        ->push(['id' => 'webhook-123'])
        ->push(['webhooks' => [['type' => 'transfer', 'url' => 'https://wallet.example.com/api/bitgo/webhook']]])]);

    $job = new RegisterWalletWebhook($wallet->id);
    $job->handle(app(BitGoService::class));
    $job->handle(app(BitGoService::class));

    Http::assertSentCount(3);
    expect(Http::recorded(fn ($request) => $request->method() === 'POST'))->toHaveCount(1);
});

test('failed webhook lookup propagates for queue retry without creating a webhook', function () {
    $wallet = Wallet::factory()->for(User::factory())->create(['bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test']);
    Http::fake(['https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/webhooks' => Http::response([], 503)]);

    expect(fn () => (new RegisterWalletWebhook($wallet->id))->handle(app(BitGoService::class)))
        ->toThrow(RequestException::class);

    Http::assertNotSent(fn ($request) => $request->method() === 'POST');
});

test('setup command queues all existing wallets', function () {
    Queue::fake([RegisterWalletWebhook::class]);
    $wallet = Wallet::factory()->for(User::factory())->create(['bitgo_wallet_id' => 'wallet-123', 'passphrase' => 'test']);

    $this->artisan('bitgo:register-webhooks')->assertSuccessful();

    Queue::assertPushed(RegisterWalletWebhook::class, fn ($job) => $job->walletId === $wallet->id);
});

test('setup command rejects a missing or nonpublic HTTPS callback', function (?string $url) {
    Queue::fake([RegisterWalletWebhook::class]);
    config(['services.bitgo.webhook_url' => $url]);

    $this->artisan('bitgo:register-webhooks')->assertFailed();

    Queue::assertNothingPushed();
})->with([null, '', 'http://wallet.example.com/api/bitgo/webhook', 'https://localhost/api/bitgo/webhook', 'https://127.0.0.1/api/bitgo/webhook']);

test('setup command rejects an unknown wallet', function () {
    Queue::fake([RegisterWalletWebhook::class]);

    $this->artisan('bitgo:register-webhooks', ['--wallet' => '999'])->assertFailed();

    Queue::assertNothingPushed();
});

test('registration ignores wallets deleted before the job runs', function () {
    (new RegisterWalletWebhook(999))->handle(app(BitGoService::class));

    Http::assertNothingSent();
});
