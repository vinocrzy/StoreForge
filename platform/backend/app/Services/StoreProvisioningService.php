<?php

namespace App\Services;

use App\Models\Store;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StoreProvisioningService
{
    public function getStores(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Store::query()->with(['users' => function ($q) {
            $q->wherePivot('role', 'owner');
        }]);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    public function createStore(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $store = Store::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'domain' => $data['domain'] ?? null,
                'status' => $data['status'] ?? 'active',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'currency' => strtoupper($data['currency'] ?? 'USD'),
                'timezone' => $data['timezone'] ?? 'UTC',
                'language' => strtolower($data['language'] ?? 'en'),
                'settings' => $data['settings'] ?? [
                    'theme' => 'default',
                    'logo_text' => $data['name'],
                ],
            ]);

            $adminEmail = $data['admin_email'] ?? $this->generateFallbackAdminEmail($store->slug);

            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $adminEmail,
                'phone' => $data['admin_phone'],
                'password' => Hash::make($data['admin_password']),
                'status' => 'active',
            ]);

            $admin->assignRole('admin');
            $admin->stores()->attach($store->id, ['role' => 'admin']);

            $superAdmin = User::query()->where('email', 'admin@ecommerce-platform.com')->first();
            if ($superAdmin) {
                $superAdmin->stores()->syncWithoutDetaching([
                    $store->id => ['role' => 'owner'],
                ]);
            }

            return [
                'store' => $store->fresh(),
                'admin' => $admin->fresh(),
            ];
        });
    }

    public function getStore(int $id): Store
    {
        return Store::query()->with(['users'])->findOrFail($id);
    }

    public function updateStatus(int $id, string $status): Store
    {
        $store = Store::query()->findOrFail($id);
        $store->update(['status' => $status]);

        return $store->fresh();
    }

    private function generateFallbackAdminEmail(string $slug): string
    {
        $base = "admin+{$slug}@example.local";
        $candidate = $base;
        $counter = 1;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = "admin+{$slug}+{$counter}@example.local";
            $counter++;
        }

        return $candidate;
    }
}
