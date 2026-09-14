<?php

namespace App\Models;

use Database\Factories\MerchantApiTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MerchantApiToken extends Model
{
    /** @use HasFactory<MerchantApiTokenFactory> */
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'name',
        'token_hash',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function issueFor(Merchant $merchant, string $name): string
    {
        $plainTextToken = 'cbag_'.Str::random(64);

        static::create([
            'merchant_id' => $merchant->id,
            'name' => $name,
            'token_hash' => static::hashToken($plainTextToken),
        ]);

        return $plainTextToken;
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function isUsable(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
