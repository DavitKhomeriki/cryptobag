<?php

namespace App\Models;

use App\Enums\MerchantStatus;
use Database\Factories\MerchantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Merchant extends Model
{
    /** @use HasFactory<MerchantFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'status',
        'default_callback_url',
    ];

    protected $hidden = [
        'webhook_secret',
    ];

    protected $attributes = [
        'status' => 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (Merchant $merchant): void {
            $merchant->public_id ??= 'mrc_'.Str::random(24);
            $merchant->webhook_secret ??= Str::random(64);
        });
    }

    protected function casts(): array
    {
        return [
            'status' => MerchantStatus::class,
            'webhook_secret' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(MerchantApiToken::class);
    }

    public function isActive(): bool
    {
        return $this->status === MerchantStatus::Active;
    }
}
