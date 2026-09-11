<?php

use App\Jobs\RegisterWalletWebhook;
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
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    Queue::assertPushed(RegisterWalletWebhook::class, fn ($job) => $job->walletId === auth()->user()->wallet->id && $job->afterCommit === true);
    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
