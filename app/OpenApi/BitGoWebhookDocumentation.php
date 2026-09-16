<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/bitgo/webhook',
    operationId: 'receiveBitGoWebhook',
    summary: 'Receive BitGo transfer webhook',
    description: 'Internal provider callback used by BitGo. This endpoint is documented for developers only and is not intended for merchant clients.',
    tags: ['Internal - BitGo Webhooks'],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['type'],
            properties: [
                new OA\Property(
                    property: 'type',
                    description: 'BitGo webhook event type. Transfer events include wallet and transfer identifiers.',
                    type: 'string',
                    example: 'transfer',
                ),
                new OA\Property(
                    property: 'wallet',
                    description: 'BitGo wallet identifier. Required when type is transfer.',
                    type: 'string',
                    maxLength: 255,
                    example: 'wallet-123',
                ),
                new OA\Property(
                    property: 'transfer',
                    description: 'BitGo transfer identifier. Required when type is transfer.',
                    type: 'string',
                    maxLength: 255,
                    pattern: '^[a-zA-Z0-9_-]+$',
                    example: 'transfer-123',
                ),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Webhook acknowledged',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'received', type: 'boolean', example: true),
                ],
            ),
        ),
        new OA\Response(response: 422, description: 'Invalid webhook payload or transfer mismatch'),
        new OA\Response(response: 500, description: 'BitGo verification failed'),
    ],
)]
class BitGoWebhookDocumentation {}
