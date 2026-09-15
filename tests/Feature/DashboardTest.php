<?php

use App\Enums\UserAccountType;
use App\Models\Merchant;
use App\Models\MerchantApiToken;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

function fakeDashboardServices(): void
{
    config(['services.bitgo.url' => 'https://bitgo.test', 'inertia.ssr.enabled' => false]);
    Http::preventStrayRequests();
    Http::fake([
        'https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/transfer?*' => Http::response([
            'transfers' => [['id' => 'transfer-123', 'valueString' => '150000000']],
        ]),
        'https://bitgo.test/api/v2/tbtc4/wallet/wallet-123' => Http::response(['balanceString' => '150000000']),
        'https://bitgo.test/api/v2/tbtc4/wallet/wallet-123/addresses' => Http::response(['addresses' => []]),
        'https://api.coingecko.com/api/v3/simple/price?*' => Http::response(['bitcoin' => ['usd' => 80000]]),
    ]);
}

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    fakeDashboardServices();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard/dashboard')
        ->where('walletBalance', 1.5)
        ->where('transactions.0.id', 'transfer-123')
        ->where('transactionsNextCursor', null));
});

test('merchant users can see their dashboard api token', function () {
    $user = User::factory()->create(['account_type' => UserAccountType::Merchant]);
    $merchant = Merchant::factory()->for($user)->create();
    $plainTextToken = 'cbag_test_dashboard_token';
    Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    MerchantApiToken::factory()->for($merchant)->create([
        'token' => $plainTextToken,
        'token_hash' => MerchantApiToken::hashToken($plainTextToken),
    ]);
    fakeDashboardServices();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard/dashboard')
        ->where('merchantApiToken', $plainTextToken)
        ->etc());
});

test('non-merchant users do not see a dashboard api token', function () {
    $user = User::factory()->create();
    Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    fakeDashboardServices();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard/dashboard')
        ->where('merchantApiToken', null)
        ->etc());
});

test('merchant users do not see unusable dashboard api tokens', function () {
    $user = User::factory()->create(['account_type' => UserAccountType::Merchant]);
    $merchant = Merchant::factory()->for($user)->create();
    Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123', 'coin' => 'tbtc4', 'passphrase' => 'test-only',
    ]);
    MerchantApiToken::factory()->for($merchant)->create([
        'revoked_at' => now(),
    ]);
    MerchantApiToken::factory()->for($merchant)->create([
        'expires_at' => now()->subMinute(),
    ]);
    fakeDashboardServices();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard/dashboard')
        ->where('merchantApiToken', null)
        ->etc());
});
