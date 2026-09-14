<?php

namespace Database\Factories;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'status' => MerchantStatus::Active,
            'webhook_secret' => Str::random(64),
            'default_callback_url' => 'https://merchant.example.com/webhooks/cryptobag',
        ];
    }
}
