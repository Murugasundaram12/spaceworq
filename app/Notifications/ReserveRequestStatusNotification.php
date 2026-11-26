<?php

namespace App\Notifications;

use App\Models\ReserveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReserveRequestStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ReserveRequest $reserveRequest,
        private readonly string $event = 'updated'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Reservation request {$this->event} - {$this->reserveRequest->request_code}")
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name) . ',')
            ->line("Your reservation inquiry ({$this->reserveRequest->request_code}) is currently {$this->reserveRequest->status}.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reserve_request_status',
            'event' => $this->event,
            'reserve_request_id' => $this->reserveRequest->id,
            'request_code' => $this->reserveRequest->request_code,
            'workspace_id' => $this->reserveRequest->workspace_id,
            'status' => $this->reserveRequest->status,
        ];
    }
}
