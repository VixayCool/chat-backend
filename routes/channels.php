<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('message.{id}', function ($user, $id) {
    return true;
    // return (int) $user->id === (int) $id;  
});
Broadcast::channel('group.message.{id}', function ($user, $id) {
    return true;
    // return (int) $user->id === (int) $id;  
});

