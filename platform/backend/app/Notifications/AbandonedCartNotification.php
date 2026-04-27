<?php

namespace App\Notifications;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AbandonedCartNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Cart $cart,
        private string $recoveryLink
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $items = $this->cart->items ?? [];
        $total = collect($items)->sum(fn($item) => $item['total_price'] ?? 0);

        $mail = (new MailMessage())
            ->subject('You left something behind!')
            ->greeting('Hi ' . ($notifiable->first_name ?? 'there') . ',')
            ->line('You have items waiting in your cart.')
            ->line('**Your cart total: $' . number_format($total, 2) . '**');

        foreach ($items as $item) {
            $mail->line('• ' . ($item['product_name'] ?? 'Item') . ' × ' . ($item['quantity'] ?? 1));
        }

        $mail->action('Complete Your Purchase', $this->recoveryLink)
             ->line('This link is valid for 48 hours.')
             ->salutation('Happy shopping!');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'cart_id'       => $this->cart->id,
            'recovery_link' => $this->recoveryLink,
        ];
    }
}
