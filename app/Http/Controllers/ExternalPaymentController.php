<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Requests\ExternalPaymentRequest;
use App\Models\ExternalPayment;
use App\Services\BitGoService;
use App\Services\ExternalPaymentService;
use Illuminate\Http\JsonResponse;

class ExternalPaymentController extends Controller
{
    public function store(
        ExternalPaymentRequest $request,
        BitGoService $bitGoService,
        ExternalPaymentService $externalPaymentService
    ): JsonResponse {
        $idempotencyKey = $request->header('Idempotency-Key');

        abort_if(
            ! $idempotencyKey,
            400,
            'Idempotency-Key header is required.'
        );

        $userWallet = $externalPaymentService->getWallet(
            $request->wallet_id,
            $request->passphrase
        );

        $payment = ExternalPayment::firstOrCreate(
            [
                'idempotency_key' => $idempotencyKey,
            ],
            [
                'order_id_for_merchant' => $request->order_id,
                'wallet_id' => $userWallet->id,
                'address' => $request->address,
                'amount' => $request->amount,
                'status' => TransactionStatus::Pending,
            ]
        );

        if (! $payment->wasRecentlyCreated) {
            return $this->paymentResponse($payment);
        }

        try {
            $response = $bitGoService->send(
                $userWallet,
                $request->address,
                (float) $request->amount
            );

            $payment->update([
                'transfer_id' => $response['transfer']['id'],
                'status' => TransactionStatus::Pending,
            ]);
        } catch (\Throwable $e) {
            $payment->update([
                'status' => TransactionStatus::Failed,
            ]);

            throw $e;
        }

        return $this->paymentResponse($payment, 201);
    }

    private function paymentResponse(ExternalPayment $payment, int $status = 200): JsonResponse
    {
        return response()->json([
            'payment_id' => (string) $payment->id,
            'order_id' => (string) $payment->order_id_for_merchant,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'transfer_id' => $payment->transfer_id,
        ], $status);
    }
}
