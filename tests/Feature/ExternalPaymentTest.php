<?php

use App\Enums\TransactionStatus;
use App\Models\ExternalPayment;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;

test('external payments return payment details as json after sending transaction', function () {
    Http::preventStrayRequests();
    config(['services.bitgo.express_url' => 'https://express.test']);
    Http::fake([
        'https://express.test/api/v2/tbtc4/wallet/wallet-123/sendcoins' => Http::response([
            'transfer' => ['id' => 'transfer-123'],
        ]),
    ]);
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123',
        'coin' => 'tbtc4',
        'passphrase' => 'test-only',
    ]);

    $response = $this->postJson(route('api.bitgo.payment'), [
        'order_id' => 123,
        'wallet_id' => $wallet->id,
        'passphrase' => 'test-only',
        'amount' => '0.00100000',
        'address' => 'merchant-address',
    ], [
        'Idempotency-Key' => 'payment-key-123',
    ]);

    $payment = ExternalPayment::first();
    $response->assertCreated()->assertExactJson([
        'payment_id' => (string) $payment->id,
        'order_id' => '123',
        'status' => 'pending',
        'amount' => '0.00100000',
        'transfer_id' => 'transfer-123',
    ]);
    $this->assertDatabaseHas('external_payments', [
        'idempotency_key' => 'payment-key-123',
        'order_id_for_merchant' => '123',
        'wallet_id' => $wallet->id,
        'address' => 'merchant-address',
        'status' => 'pending',
        'transfer_id' => 'transfer-123',
    ]);
});

test('duplicate external payments return the existing payment details without sending again', function () {
    Http::preventStrayRequests();
    $user = User::factory()->create();
    $wallet = Wallet::factory()->for($user)->create([
        'bitgo_wallet_id' => 'wallet-123',
        'coin' => 'tbtc4',
        'passphrase' => 'test-only',
    ]);
    $payment = ExternalPayment::create([
        'idempotency_key' => 'payment-key-123',
        'order_id_for_merchant' => '123',
        'wallet_id' => $wallet->id,
        'address' => 'merchant-address',
        'amount' => '0.00100000',
        'status' => TransactionStatus::Pending,
        'transfer_id' => 'transfer-123',
    ]);

    $response = $this->postJson(route('api.bitgo.payment'), [
        'order_id' => 123,
        'wallet_id' => $wallet->id,
        'passphrase' => 'test-only',
        'amount' => '0.00100000',
        'address' => 'merchant-address',
    ], [
        'Idempotency-Key' => 'payment-key-123',
    ]);

    $response->assertOk()->assertExactJson([
        'payment_id' => (string) $payment->id,
        'order_id' => '123',
        'status' => 'pending',
        'amount' => '0.00100000',
        'transfer_id' => 'transfer-123',
    ]);
    Http::assertNothingSent();
});
