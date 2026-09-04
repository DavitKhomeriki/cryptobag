<?php

namespace App\Models;

use Database\Factories\WalletAddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletAddress extends Model
{
    /** @use HasFactory<WalletAddressFactory> */
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'address',
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
