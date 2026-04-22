<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        private Order $order,
        private Store $store,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $fromName = $this->getFromName();
        $fromAddress = $this->getFromAddress();

        return (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("[{$this->store->name}] Order #{$this->order->order_number} Confirmed")
            ->view('emails.order-confirmation', [
                'order' => $this->order->load('items'),
                'customer' => $notifiable,
                'store' => $this->store,
                'subject' => "[{$this->store->name}] Order #{$this->order->order_number} Confirmed",
            ]);
    }

    private function getFromName(): string
    {
        return $this->store->getSetting('mail_from_name') ?? $this->store->name;
    }

    private function getFromAddress(): string
    {
        return $this->store->getSetting('mail_from_address') ?? $this->store->email ?? config('mail.from.address');
    }
}
