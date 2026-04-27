<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DetectAbandonedCarts extends Command
{
    protected $signature   = 'carts:detect-abandoned';
    protected $description = 'Mark carts with items that have been inactive for more than 1 hour as abandoned.';

    public function handle(): int
    {
        $threshold = now()->subHour();

        // Carts with items, not yet marked abandoned, inactive for > 1 hour
        $count = DB::table('carts')
            ->whereNotNull('items')
            ->whereRaw("JSON_LENGTH(items) > 0")
            ->where(function ($q) use ($threshold) {
                $q->where('last_activity_at', '<', $threshold)
                  ->orWhere(function ($q2) use ($threshold) {
                      $q2->whereNull('last_activity_at')
                         ->where('updated_at', '<', $threshold);
                  });
            })
            ->whereNull('abandoned_at')
            ->update(['abandoned_at' => now()]);

        $this->info("Marked {$count} cart(s) as abandoned.");

        return self::SUCCESS;
    }
}
