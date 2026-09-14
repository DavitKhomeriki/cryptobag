<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\MerchantApiToken;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        return [
            'merchant_id' => Merchant::factory(),
            'name' => 'Test token',
            'token_hash' => MerchantApiToken::hashToken(fake()->sha256()),
            'expires_at' => null,
            'revoked_at' => null,
        ];
    }
}
