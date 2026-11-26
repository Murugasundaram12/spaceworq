<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly string $activationType
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $path = $this->activationType === 'provider_activation'
            ? '/auth/activate-provider'
            : '/auth/verify-email';

        $url = url($path . '?token=' . urlencode($this->token));

        return (new MailMessage)
            ->subject('Activate your SpaceWorq account')
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name) . ',')
            ->line('Please activate your SpaceWorq account using the verification link below.')
            ->action('Activate Account', $url)
            ->line('This link will expire in 24 hours.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->activationType,
            'message' => 'Account activation email generated.',
        ];
    }
}
