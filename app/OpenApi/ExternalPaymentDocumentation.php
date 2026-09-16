<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

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
class ExternalPaymentDocumentation {}
