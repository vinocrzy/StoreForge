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

            $ownerEmail = $data['owner_email'] ?? $this->generateFallbackOwnerEmail($store->slug);

            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $ownerEmail,
                'phone' => $data['owner_phone'],
                'password' => Hash::make($data['owner_password']),
                'status' => 'active',
            ]);

            $owner->assignRole('owner');
            $owner->stores()->attach($store->id, ['role' => 'owner']);

            $superAdmin = User::query()->where('email', 'admin@ecommerce-platform.com')->first();
            if ($superAdmin) {
                $superAdmin->stores()->syncWithoutDetaching([
                    $store->id => ['role' => 'owner'],
                ]);
            }

            return [
                'store' => $store->fresh(),
                'owner' => $owner->fresh(),
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

    private function generateFallbackOwnerEmail(string $slug): string
    {
        $base = "owner+{$slug}@example.local";
        $candidate = $base;
        $counter = 1;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = "owner+{$slug}+{$counter}@example.local";
            $counter++;
        }

        return $candidate;
    }
}
