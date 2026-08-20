<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LoginLockoutDetected extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $email,
        private readonly ?string $ipAddress,
    )
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
            'message' => "Login lockout detected for {$this->email}.",
            'icon' => 'shield-alert',
            'link' => route('admin.dashboard'),
            'ip' => $this->ipAddress,
        ];
    }
}
