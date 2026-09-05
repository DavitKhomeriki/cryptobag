<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAddress;
use App\Services\BitGoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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
            'password' => $this->passwordRules(),
        ])->validate();

        $passphrase = Str::random(64);

        return DB::transaction(function () use ($input, $passphrase) {
            $user = User::create([
                'username' => $input['username'],
                'email' => $input['email'],
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

            return $user;
        });

    }
}
