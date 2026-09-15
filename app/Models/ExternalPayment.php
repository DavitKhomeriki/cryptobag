<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPayment extends Model
{
    protected $fillable = [
        'idempotency_key',
        'order_id_for_merchant',
        'wallet_id',
        'address',
        'amount',
        'status',
        'transfer_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransactionStatus::class,
            'amount' => 'decimal:8',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
