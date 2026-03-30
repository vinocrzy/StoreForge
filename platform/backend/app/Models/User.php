<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)->withPivot('role')->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasAccessToStore(int $storeId): bool
    {
        return $this->stores()->where('stores.id', $storeId)->exists();
    }

    public function getRoleInStore(int $storeId): ?string
    {
        $store = $this->stores()->where('stores.id', $storeId)->first();
        return $store?->pivot->role;
    }

    public function isOwnerOfStore(int $storeId): bool
    {
        return $this->getRoleInStore($storeId) === 'owner';
    }

    public function isAdminInStore(int $storeId): bool
    {
        $role = $this->getRoleInStore($storeId);
        return in_array($role, ['owner', 'admin']);
    }
}
