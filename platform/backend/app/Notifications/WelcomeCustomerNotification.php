<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
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
            ->subject("Welcome to {$this->store->name}!")
            ->view('emails.welcome', [
                'customer' => $notifiable,
                'store' => $this->store,
                'subject' => "Welcome to {$this->store->name}!",
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
