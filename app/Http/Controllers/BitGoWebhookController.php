<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Events\WalletUpdated;
use App\Models\ExternalPayment;
use App\Models\Wallet;
use App\Services\BitGoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BitGoWebhookController extends Controller
{
    public function __invoke(Request $request, BitGoService $bitGoService): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'wallet' => ['required_if:type,transfer', 'string', 'max:255'],
            'transfer' => ['required_if:type,transfer', 'string', 'max:255', 'regex:/\A[a-zA-Z0-9_-]+\z/'],
        ]);

        if ($data['type'] !== 'transfer') {
            return response()->json(['received' => true]);
        }

        $wallet = Wallet::with('user')->where('bitgo_wallet_id', $data['wallet'])->first();

        if (! $wallet) {
            return response()->json(['received' => true]);
        }

        $transfer = $bitGoService->transfer($wallet, $data['transfer']);

        if ($wallet->user->type === 'merchant') {
            $payment = ExternalPayment::where('transfer_id', $data['transfer'])->first();
            if ($payment) {
                $status = match ($transfer['state']) {
                    'confirmed' => TransactionStatus::Completed,
                    'failed', 'rejected' => TransactionStatus::Failed,
                    'pending', 'unconfirmed' => TransactionStatus::Pending,
                    default => TransactionStatus::Pending,
                };

                $payment->update([
                    'status' => $status,
                ]);
            }

        }

        abort_unless(
            ($transfer['id'] ?? null) === $data['transfer']
                && ($transfer['wallet'] ?? null) === $wallet->bitgo_wallet_id,
            422,
            'The transfer does not match the wallet.',
        );

        WalletUpdated::dispatch($wallet->user_id);

        return response()->json(['received' => true]);
    }
}
