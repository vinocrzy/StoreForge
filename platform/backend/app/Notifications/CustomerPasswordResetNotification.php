<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [10, 60, 300];

    public function __construct(
        private string $token,
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
        $resetUrl = $this->buildResetUrl($notifiable);
        $expireMinutes = config('auth.passwords.customers.expire', 60);

        return (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("[{$this->store->name}] Reset Your Password")
            ->view('emails.password-reset', [
                'customer' => $notifiable,
                'store' => $this->store,
                'resetUrl' => $resetUrl,
                'expireMinutes' => $expireMinutes,
                'subject' => "[{$this->store->name}] Reset Your Password",
            ]);
    }

    private function buildResetUrl(object $notifiable): string
    {
        $domain = $this->store->custom_domain ?? $this->store->domain ?? config('app.frontend_url', config('app.url'));

        return rtrim($domain, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);
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
