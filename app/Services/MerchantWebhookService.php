<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Jobs\DeliverMerchantWebhook;
use App\Models\ExternalPayment;
use App\Models\Wallet;
use Illuminate\Support\Arr;
use JsonException;

class MerchantWebhookService
{
    /**
     * @return array{event: string, data: array{payment_id: string, order_id: string, status: string, amount: string}}
     */
    public function paymentUpdatedPayload(ExternalPayment $payment): array
    {
        return [
            'event' => 'payment.updated',
            'data' => [
                'payment_id' => (string) $payment->id,
                'order_id' => $payment->order_id_for_merchant,
                'status' => $payment->status instanceof TransactionStatus ? $payment->status->value : (string) $payment->status,
                'amount' => $payment->amount,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    public function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return array<string, string>
     */
    public function headers(string $event, string $payload, string $secret, int $timestamp): array
    {
        return [
            'X-CryptoBag-Event' => $event,
            'X-CryptoBag-Timestamp' => (string) $timestamp,
            'X-CryptoBag-Signature' => $this->signature($payload, $secret, $timestamp),
        ];
    }

    public function signature(string $payload, string $secret, int $timestamp): string
    {
        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }

    /**
     * @param  array<string, mixed>  $transfer
     */
    public function updateExternalPaymentFromTransfer(Wallet $wallet, array $transfer): ?ExternalPayment
    {
        $merchant = $wallet->user->merchant;

        if (! $merchant) {
            return null;
        }

        $payment = ExternalPayment::where('transfer_id', Arr::get($transfer, 'id'))->first();

        if (! $payment) {
            return null;
        }

        $payment->status = $this->statusFromTransferState((string) Arr::get($transfer, 'state', 'pending'));

        if ($payment->isClean('status')) {
            return $payment;
        }

        $payment->save();

        if ($merchant->default_callback_url) {
            DeliverMerchantWebhook::dispatch($payment->id, $merchant->id);
        }

        return $payment;
    }

    public function statusFromTransferState(string $state): TransactionStatus
    {
        return match ($state) {
            'confirmed' => TransactionStatus::Completed,
            'failed', 'rejected' => TransactionStatus::Failed,
            'pending', 'unconfirmed' => TransactionStatus::Pending,
            default => TransactionStatus::Pending,
        };
    }
}
