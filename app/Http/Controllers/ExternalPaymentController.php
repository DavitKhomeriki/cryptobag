<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Http\Requests\ExternalPaymentRequest;
use App\Models\ExternalPayment;
use App\Services\BitGoService;
use App\Services\ExternalPaymentService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ExternalPaymentController extends Controller
{
    #[OA\Post(
        path: '/api/v1/bitgo/payment',
        operationId: 'createExternalPayment',
        summary: 'Create an external BitGo payment',
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key',
                description: 'Unique key used to safely retry the same payment request.',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: 'payment-key-123'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'wallet_id', 'passphrase', 'amount', 'address'],
                properties: [
                    new OA\Property(property: 'order_id', type: 'integer', example: 123),
                    new OA\Property(property: 'wallet_id', type: 'integer', example: 1),
                    new OA\Property(property: 'passphrase', type: 'string', example: 'test-only'),
                    new OA\Property(property: 'amount', type: 'string', example: '0.00100000'),
                    new OA\Property(property: 'address', type: 'string', example: 'merchant-address'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'payment_id', type: 'string', example: '1'),
                        new OA\Property(property: 'order_id', type: 'string', example: '123'),
                        new OA\Property(property: 'status', type: 'string', example: 'pending'),
                        new OA\Property(property: 'amount', type: 'string', example: '0.00100000'),
                        new OA\Property(property: 'transfer_id', type: 'string', nullable: true, example: 'transfer-123'),
                    ]
                )
            ),
            new OA\Response(response: 200, description: 'Existing idempotent payment returned'),
            new OA\Response(response: 400, description: 'Missing Idempotency-Key header'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
