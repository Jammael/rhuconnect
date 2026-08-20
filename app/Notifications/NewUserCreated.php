<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserCreated extends Notification
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
            'message' => "New staff account created for {$this->user->name}.",
            'icon' => 'user-plus',
            'link' => route('admin.users.show', $this->user),
        ];
    }
}
