<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('wallet.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
