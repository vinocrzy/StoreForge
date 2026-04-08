<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeMockTenantData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:purge-mock-tenant-data
                            {--dry-run : Show what would be deleted without changing data}
                            {--demo-slug=demo-store : Slug of the demo store to keep}
                            {--keep-super-admin-email=admin@ecommerce-platform.com : Super admin email to preserve}';

    /**
     * The console command description.
     */
    protected $description = 'Remove non-demo stores and seeded tenant data while preserving one demo store and super admin credentials';

    public function handle(): int
    {
        $demoSlug = (string) $this->option('demo-slug');
        $keepSuperAdminEmail = (string) $this->option('keep-super-admin-email');
        $dryRun = (bool) $this->option('dry-run');

        $demoStore = Store::query()->where('slug', $demoSlug)->first();

        if (!$demoStore) {
            $this->error("Demo store not found for slug: {$demoSlug}");
            $this->line('Tip: create it first with `php artisan db:seed --class=Database\\Seeders\\DemoStoreSeeder`.');

            return self::FAILURE;
        }

        $storesToDelete = Store::query()
            ->where('id', '!=', $demoStore->id)
            ->pluck('id');

        $storeCount = $storesToDelete->count();
        $userCountBefore = User::count();

        $this->info('Mock data purge plan');
        $this->line("- Keep demo store: {$demoStore->name} ({$demoStore->slug})");
        $this->line("- Keep super admin: {$keepSuperAdminEmail}");
        $this->line("- Stores to delete: {$storeCount}");

        if ($dryRun) {
            $this->warn('Dry run enabled. No data was changed.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($storesToDelete, $keepSuperAdminEmail, $demoStore): void {
            if ($storesToDelete->isNotEmpty()) {
                Store::query()->whereIn('id', $storesToDelete)->delete();
            }

            // Remove orphaned seeded users while preserving super admin and demo-store users.
            User::query()
                ->where('email', '!=', $keepSuperAdminEmail)
                ->whereDoesntHave('stores')
                ->delete();

            // Ensure super admin is still linked to demo store.
            $superAdmin = User::query()->where('email', $keepSuperAdminEmail)->first();
            if ($superAdmin) {
                $superAdmin->stores()->syncWithoutDetaching([
                    $demoStore->id => ['role' => 'owner'],
                ]);
            }
        });

        $userCountAfter = User::count();

        $this->info('Purge completed.');
        $this->line('- Remaining stores: ' . Store::count());
        $this->line('- Users before: ' . $userCountBefore);
        $this->line('- Users after: ' . $userCountAfter);

        return self::SUCCESS;
    }
}
