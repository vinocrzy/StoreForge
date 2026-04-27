<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Models\Customer;
use App\Notifications\AbandonedCartNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendAbandonedCartEmails extends Command
{
    protected $signature   = 'carts:send-recovery-emails';
    protected $description = 'Send recovery emails to customers with abandoned carts (up to 2 emails per cart).';

    public function handle(): int
    {
        $sent = 0;

        // First email: cart abandoned, no email sent yet
        $firstBatch = Cart::withoutGlobalScope('store')
            ->whereNotNull('abandoned_at')
            ->whereNull('recovery_email_sent_at')
            ->where('recovery_email_count', 0)
            ->whereNotNull('customer_id')
            ->with('customer')
            ->get();

        foreach ($firstBatch as $cart) {
            if ($this->sendEmail($cart)) {
                $sent++;
            }
        }

        // Second email: first sent >= 24 hours ago, only 1 sent so far
        $secondBatch = Cart::withoutGlobalScope('store')
            ->whereNotNull('abandoned_at')
            ->where('recovery_email_count', 1)
            ->where('recovery_email_sent_at', '<=', now()->subHours(24))
            ->whereNotNull('customer_id')
            ->with('customer')
            ->get();

        foreach ($secondBatch as $cart) {
            if ($this->sendEmail($cart)) {
                $sent++;
            }
        }

        $this->info("Sent {$sent} abandoned cart email(s).");

        return self::SUCCESS;
    }

    private function sendEmail(Cart $cart): bool
    {
        /** @var Customer|null $customer */
        $customer = $cart->customer;

        if (!$customer || !$customer->email) {
            return false;
        }

        $recoveryLink = url('/cart?recover=' . $cart->token);

        try {
            $customer->notify(new AbandonedCartNotification($cart, $recoveryLink));

            DB::table('carts')->where('id', $cart->id)->update([
                'recovery_email_sent_at' => now(),
                'recovery_email_count'   => DB::raw('recovery_email_count + 1'),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->error("Failed to send email for cart #{$cart->id}: " . $e->getMessage());
            return false;
        }
    }
}
