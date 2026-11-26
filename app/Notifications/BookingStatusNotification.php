<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = $this->booking->booking_date?->format('Y-m-d') ?? '';

        return (new MailMessage)
            ->subject('Booking ' . $this->booking->status . ' - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name) . ',')
            ->line("Your booking {$this->booking->booking_reference} on {$date} is currently {$this->booking->status}.")
            ->line('Payment Status: ' . $this->booking->payment_status);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'booking_status',
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'workspace_id' => $this->booking->workspace_id,
            'status' => $this->booking->status,
            'payment_status' => $this->booking->payment_status,
            'total_amount' => $this->booking->total_amount,
            'currency' => $this->booking->currency,
        ];
    }
}
