<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, string $id) {
    return (int) $user->id === (int) $id;
});
