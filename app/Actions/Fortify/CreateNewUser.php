<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\MerchantStatus;
use App\Enums\UserAccountType;
use App\Jobs\RegisterWalletWebhook;
use App\Models\Merchant;
use App\Models\MerchantApiToken;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAddress;
use App\Services\BitGoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private BitGoService $bitGo,
    ) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'account_type' => ['required', Rule::enum(UserAccountType::class)],
            'merchant_name' => ['required_if:account_type,merchant', 'nullable', 'string', 'max:255'],
            'merchant_callback_url' => ['nullable', 'url:http,https', 'max:2048'],
            'password' => $this->passwordRules(),
        ])->validate();

        $passphrase = Str::random(64);

        return DB::transaction(function () use ($input, $passphrase) {
            $accountType = UserAccountType::from($input['account_type']);
            $user = User::create([
                'username' => $input['username'],
                'email' => $input['email'],
                'account_type' => $accountType,
                'password' => $input['password'],
            ]);

            $generatedWallet = $this->bitGo->generateWallet($user, $passphrase);

            $wallet = Wallet::create([
                'user_id' => $user->id,
                'bitgo_wallet_id' => $generatedWallet['wallet']['id'],
                'coin' => $generatedWallet['wallet']['coin'],
                'passphrase' => $passphrase,
            ]);

            WalletAddress::create([
                'wallet_id' => $wallet->id,
                'address' => $generatedWallet['wallet']['receiveAddress']['address'],
            ]);

            RegisterWalletWebhook::dispatch($wallet->id)->afterCommit();

            if ($accountType === UserAccountType::Merchant) {
                $merchant = Merchant::create([
                    'user_id' => $user->id,
                    'name' => $input['merchant_name'],
                    'status' => MerchantStatus::Active,
                    'default_callback_url' => $input['merchant_callback_url'] ?? null,
                ]);

                MerchantApiToken::issueFor($merchant, 'Default API token');
            }

            return $user;
        });

    }
}
