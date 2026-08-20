<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserAccountDeactivated extends Notification
{
    use Queueable;

    public function __construct(private readonly User $user)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "{$this->user->name}'s account was deactivated.",
            'icon' => 'user-x',
            'link' => route('admin.users.show', $this->user),
        ];
    }
}
