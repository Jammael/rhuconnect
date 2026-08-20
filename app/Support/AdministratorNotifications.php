<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Notifications\Notification;

class AdministratorNotifications
{
    public static function send(Notification $notification): void
    {
        User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Administrator'))
            ->get()
            ->each
            ->notify($notification);
    }
}
