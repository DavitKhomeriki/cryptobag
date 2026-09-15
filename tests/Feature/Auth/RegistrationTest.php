<?php

use App\Jobs\RegisterWalletWebhook;
use App\Models\MerchantApiToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    Queue::fake([RegisterWalletWebhook::class]);
    Http::preventStrayRequests();
    config(['services.bitgo.express_url' => 'https://express.test']);
    Http::fake(['https://express.test/api/v2/tbtc4/wallet/generate' => Http::response([
        'wallet' => ['id' => 'wallet-123', 'coin' => 'tbtc4', 'receiveAddress' => ['address' => 'test-address']],
    ])]);
    $response = $this->post(route('register.store'), [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'account_type' => 'user',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Queue::assertPushed(RegisterWalletWebhook::class, fn ($job) => $job->walletId === auth()->user()->wallet->id && $job->afterCommit === true);
    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('new merchants can register and receive an api token', function () {
    Queue::fake([RegisterWalletWebhook::class]);
    Http::preventStrayRequests();
    config(['services.bitgo.express_url' => 'https://express.test']);
    Http::fake(['https://express.test/api/v2/tbtc4/wallet/generate' => Http::response([
        'wallet' => ['id' => 'wallet-123', 'coin' => 'tbtc4', 'receiveAddress' => ['address' => 'test-address']],
    ])]);

    $response = $this->post(route('register.store'), [
        'username' => 'merchantuser',
        'email' => 'merchant@example.com',
        'account_type' => 'merchant',
        'merchant_name' => 'Acme Market',
        'merchant_callback_url' => 'https://acme.test/webhooks/cryptobag',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $apiToken = MerchantApiToken::first();

    Queue::assertPushed(RegisterWalletWebhook::class, fn ($job) => $job->walletId === auth()->user()->wallet->id && $job->afterCommit === true);
    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
    $this->assertDatabaseHas('users', [
        'username' => 'merchantuser',
        'email' => 'merchant@example.com',
        'account_type' => 'merchant',
    ]);
    $this->assertDatabaseHas('merchants', [
        'user_id' => auth()->id(),
        'name' => 'Acme Market',
        'status' => 'active',
        'default_callback_url' => 'https://acme.test/webhooks/cryptobag',
    ]);
    $this->assertDatabaseHas('merchant_api_tokens', [
        'token_hash' => MerchantApiToken::hashToken($apiToken->token),
        'name' => 'Default API token',
    ]);
    expect(str_starts_with($apiToken->token, 'cbag_'))->toBeTrue();
});

test('merchant registration requires a merchant name', function () {
    Queue::fake([RegisterWalletWebhook::class]);
    Http::preventStrayRequests();

    $response = $this->post(route('register.store'), [
        'username' => 'merchantuser',
        'email' => 'merchant@example.com',
        'account_type' => 'merchant',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('merchant_name');
    $this->assertGuest();
    Queue::assertNothingPushed();
    Http::assertNothingSent();
});
