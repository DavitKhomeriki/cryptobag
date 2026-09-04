<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'status',
        'usd_amount',
        'exchange_rate',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'amount' => 'decimal:2',
        'usd_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);

    }
}
