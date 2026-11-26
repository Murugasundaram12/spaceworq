<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProviderApprovalNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $entityType,
        private readonly int $entityId,
        private readonly string $entityName,
        private readonly string $decision,
        private readonly ?string $remarks = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("SpaceWorq {$this->entityType} update: {$this->decision}")
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name) . ',')
            ->line("{$this->entityName} status updated to: {$this->decision}.");

        if ($this->remarks) {
            $mail->line('Notes: ' . $this->remarks);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'provider_approval',
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'entity_name' => $this->entityName,
            'decision' => $this->decision,
            'remarks' => $this->remarks,
        ];
    }
}
