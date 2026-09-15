<?php

namespace App\Jobs;

use App\Models\ExternalPayment;
use App\Models\Merchant;
use App\Services\MerchantWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class DeliverMerchantWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 15;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900, 1800];

    public function __construct(
        public int $externalPaymentId,
        public int $merchantId,
    ) {}

    public function handle(MerchantWebhookService $webhooks): void
    {
        $payment = ExternalPayment::findOrFail($this->externalPaymentId);
        $merchant = Merchant::findOrFail($this->merchantId);

        if (! $merchant->default_callback_url) {
            return;
        }

        $payload = $webhooks->paymentUpdatedPayload($payment);
        $jsonPayload = $webhooks->encodePayload($payload);
        $timestamp = now()->timestamp;

        Http::connectTimeout(3)
            ->timeout(10)
            ->withHeaders($webhooks->headers('payment.updated', $jsonPayload, $merchant->webhook_secret, $timestamp))
            ->withBody($jsonPayload, 'application/json')
            ->post($merchant->default_callback_url)
            ->throw();
    }
}
