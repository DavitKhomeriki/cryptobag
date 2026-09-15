<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Requests\ExternalPaymentRequest;
use App\Models\ExternalPayment;
use App\Services\BitGoService;
use App\Services\ExternalPaymentService;
use Illuminate\Http\RedirectResponse;

class ExternalPaymentController extends Controller
{
    public function store(
        ExternalPaymentRequest $request,
        BitGoService $bitGoService,
        ExternalPaymentService $externalPaymentService
    ): RedirectResponse {
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

        // Duplicate request
        if (! $payment->wasRecentlyCreated) {
            $merchantUser = $externalPaymentService->getMerchant(
                $payment->address
            );

            return redirect()->away(
                $merchantUser->merchant->default_callback_url
                .'?payment_id='.$payment->id
            );
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

        $merchantUser = $externalPaymentService->getMerchant(
            $request->address
        );

        return redirect()->away(
            $merchantUser->merchant->default_callback_url
            .'?payment_id='.$payment->id
        );
    }
}
