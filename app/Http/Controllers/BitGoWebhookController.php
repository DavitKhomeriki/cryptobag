<?php

namespace App\Http\Controllers;

use App\Enums\UserAccountType;
use App\Events\WalletUpdated;
use App\Models\Wallet;
use App\Services\BitGoService;
use App\Services\MerchantWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BitGoWebhookController extends Controller
{
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
    public function __invoke(
        Request $request,
        BitGoService $bitGoService,
        MerchantWebhookService $merchantWebhookService,
    ): JsonResponse {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'wallet' => ['required_if:type,transfer', 'string', 'max:255'],
            'transfer' => ['required_if:type,transfer', 'string', 'max:255', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
        ]);

        if ($data['type'] !== 'transfer') {
            return response()->json(['received' => true]);
        }

        $wallet = Wallet::with('user.merchant')->where('bitgo_wallet_id', $data['wallet'])->first();

        if (! $wallet) {
            return response()->json(['received' => true]);
        }

        $transfer = $bitGoService->transfer($wallet, $data['transfer']);

        abort_unless(
            ($transfer['id'] ?? null) === $data['transfer']
                && ($transfer['wallet'] ?? null) === $wallet->bitgo_wallet_id,
            422,
            'The transfer does not match the wallet.',
        );

        if ($wallet->user->account_type === UserAccountType::Merchant) {
            $merchantWebhookService->updateExternalPaymentFromTransfer($wallet, $transfer);
        }

        WalletUpdated::dispatch($wallet->user_id);

        return response()->json(['received' => true]);
    }
}
