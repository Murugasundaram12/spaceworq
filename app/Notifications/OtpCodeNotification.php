<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $otpCode,
        private readonly int $ttlMinutes
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your SpaceWorq Verification Code')
            ->line("Your 6-digit verification code is: {$this->otpCode}")
            ->line("This code expires in {$this->ttlMinutes} minutes. Do not share it with anyone.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'otp_verification',
            'expires_in_minutes' => $this->ttlMinutes,
        ];
    }
}
