<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly PaymentTransaction $transaction
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Payment ' . $this->transaction->status . ' - ' . $this->booking->booking_reference)
            ->greeting('Hello ' . ($notifiable->first_name ?? $notifiable->name) . ',')
            ->line("Your payment of {$this->transaction->currency} {$this->transaction->amount} for booking {$this->booking->booking_reference} is {$this->transaction->status}.")
            ->line('Booking Status: ' . $this->booking->status);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_status',
            'booking_id' => $this->booking->id,
            'booking_reference' => $this->booking->booking_reference,
            'transaction_id' => $this->transaction->id,
            'status' => $this->transaction->status,
            'amount' => $this->transaction->amount,
            'currency' => $this->transaction->currency,
        ];
    }
}
