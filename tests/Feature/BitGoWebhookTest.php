<?php

use App\Events\WalletUpdated;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BitGoService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config(['services.bitgo.url' => 'https://bitgo.test', 'services.bitgo.webhook_url' => 'https://wallet.example.com/api/bitgo/webhook']);
    Event::fake([WalletUpdated::class]);
});

test('a verified transfer broadcasts a refresh to the wallet owner without a login', function () {
    $wallet = Wallet::factory()->for(User::factory())->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    Http::fake([
        'https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/transfer/transfer-123' => Http::response([
            'id' => 'transfer-123', 'wallet' => 'wallet-123',
        ]),
    ]);

    $this->postJson(route('bitgo.webhook'), [
        'type' => 'transfer', 'wallet' => 'wallet-123', 'transfer' => 'transfer-123',
    ])->assertOk()->assertExactJson(['received' => true]);

    Event::assertDispatched(WalletUpdated::class, function (WalletUpdated $event) use ($wallet) {
        return $event->userId === $wallet->user_id
            && $event->broadcastOn()->name === 'private-wallet.'.$wallet->user_id
            && $event->broadcastAs() === 'wallet.updated'
            && $event->broadcastWith() === [];
    });
});

test('unknown wallets and unrelated event types are acknowledged without broadcasting', function (array $payload) {
    $this->postJson(route('bitgo.webhook'), $payload)
        ->assertOk()->assertExactJson(['received' => true]);

    Event::assertNotDispatched(WalletUpdated::class);
    Http::assertNothingSent();
})->with([
    'unknown wallet' => [['type' => 'transfer', 'wallet' => 'unknown', 'transfer' => 'transfer-123']],
    'unrelated type' => [['type' => 'block']],
]);

test('invalid transfer notifications return validation errors', function (array $payload, array $errors) {
    $this->postJson(route('bitgo.webhook'), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors($errors);

    Event::assertNotDispatched(WalletUpdated::class);
    Http::assertNothingSent();
})->with([
    'missing type' => [[], ['type']],
    'missing transfer fields' => [['type' => 'transfer'], ['wallet', 'transfer']],
    'unsafe transfer path' => [['type' => 'transfer', 'wallet' => 'wallet-123', 'transfer' => '../other'], ['transfer']],
]);

test('a transfer from another wallet is rejected without broadcasting', function () {
    Wallet::factory()->for(User::factory())->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    Http::fake([
        'https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/transfer/transfer-123' => Http::response([
            'id' => 'transfer-123', 'wallet' => 'another-wallet',
        ]),
    ]);

    $this->postJson(route('bitgo.webhook'), [
        'type' => 'transfer', 'wallet' => 'wallet-123', 'transfer' => 'transfer-123',
    ])->assertUnprocessable();

    Event::assertNotDispatched(WalletUpdated::class);
});

test('BitGo failures are not acknowledged as successful notifications', function () {
    Wallet::factory()->for(User::factory())->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    Http::fake([
        'https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/transfer/transfer-123' => Http::response([], 503),
    ]);

    $this->postJson(route('bitgo.webhook'), [
        'type' => 'transfer', 'wallet' => 'wallet-123', 'transfer' => 'transfer-123',
    ])->assertServerError();

    Event::assertNotDispatched(WalletUpdated::class);
});

test('webhook registration uses the configured public callback', function () {
    $wallet = Wallet::factory()->make(['bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4']);
    Http::fake(['https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/webhooks' => Http::response(['id' => 'webhook-123'])]);

    app(BitGoService::class)->addTransferWebhook($wallet);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request['url'] === 'https://wallet.example.com/api/bitgo/webhook'
        && $request['type'] === 'transfer');
});

test('users can only subscribe to their own wallet channel', function (bool $ownsChannel) {
    config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret', 'broadcasting.connections.reverb.app_id' => 'test-app']);
    require base_path('routes/channels.php');
    $user = User::factory()->create();
    $channelUser = $ownsChannel ? $user : User::factory()->create();

    $response = $this->actingAs($user)->postJson('/broadcasting/auth', [
        'socket_id' => '123.456', 'channel_name' => 'private-wallet.'.$channelUser->id,
    ]);

    if ($ownsChannel) {
        $response->assertOk()->assertJsonStructure(['auth']);
    } else {
        $response->assertForbidden();
    }
})->with([true, false]);
