<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyService
{
    public function bitcoinUsd(): float
    {
        try {
            return Cache::remember('bitcoin_usd', now()->addMinutes(300), function () {
                $response = Http::get(
                    'https://api.coingecko.com/api/v3/simple/price',
                    [
                        'ids' => 'bitcoin',
                        'vs_currencies' => 'usd',
                    ]
                )->throw()->json();

                return $response['bitcoin']['usd'];
            });
        } catch (\Exception $e) {
            return 80000.00;
        }

    }
}
