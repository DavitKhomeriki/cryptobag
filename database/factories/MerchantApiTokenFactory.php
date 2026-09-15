<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\MerchantApiToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MerchantApiToken>
 */
class MerchantApiTokenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainTextToken = 'cbag_'.Str::random(64);

        return [
            'merchant_id' => Merchant::factory(),
            'name' => 'Test token',
            'token' => $plainTextToken,
            'token_hash' => MerchantApiToken::hashToken($plainTextToken),
            'expires_at' => null,
            'revoked_at' => null,
        ];
    }
}
