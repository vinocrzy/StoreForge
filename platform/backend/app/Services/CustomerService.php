<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class CustomerService
{
    /**
     * Get paginated customers with optional filtering and search
     */
    public function getCustomers(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Customer::query()->with(['addresses', 'defaultAddress']);

        // Search by name, email, phone
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by active status
        if (isset($filters['is_active'])) {
            if ($filters['is_active']) {
                $query->active();
            } else {
                $query->where('status', '!=', 'active');
            }
        }

        // Filter by email verification
        if (isset($filters['email_verified'])) {
            if ($filters['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Filter by phone verification
        if (isset($filters['phone_verified'])) {
            if ($filters['phone_verified']) {
                $query->whereNotNull('phone_verified_at');
            } else {
                $query->whereNull('phone_verified_at');
            }
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get single customer by ID with relationships
     */
    public function getCustomer(int $id): Customer
    {
        return Customer::with(['addresses', 'defaultAddress'])->findOrFail($id);
    }

    /**
     * Get customer by phone number
     */
    public function getCustomerByPhone(string $phone): ?Customer
    {
        return Customer::where('phone', $phone)->first();
    }

    /**
     * Get customer by email
     */
    public function getCustomerByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }

    /**
     * Create a new customer
     */
    public function createCustomer(array $data): Customer
    {
        // Hash password if provided
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Set default status
        $data['status'] = $data['status'] ?? 'active';

        $customer = Customer::create($data);

        // Create address if provided
        if (!empty($data['address'])) {
            $this->createAddress($customer->id, array_merge(
                $data['address'],
                ['is_default' => true]
            ));
        }

        return $customer->load(['addresses', 'defaultAddress']);
    }

    /**
     * Update existing customer
     */
    public function updateCustomer(int $id, array $data): Customer
    {
        $customer = Customer::findOrFail($id);

        // Hash password if being updated
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $customer->update($data);

        return $customer->load(['addresses', 'defaultAddress']);
    }

    /**
     * Delete customer (soft delete)
     */
    public function deleteCustomer(int $id): bool
    {
        $customer = Customer::findOrFail($id);
        return $customer->delete();
    }

    /**
     * Restore soft-deleted customer
     */
    public function restoreCustomer(int $id): Customer
    {
        $customer = Customer::withTrashed()->findOrFail($id);
        $customer->restore();
        return $customer->load(['addresses', 'defaultAddress']);
    }

    /**
     * Update customer status
     */
    public function updateStatus(int $id, string $status): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->update(['status' => $status]);
        return $customer;
    }

    /**
     * Verify customer email
     */
    public function verifyEmail(int $id): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->markEmailAsVerified();
        return $customer;
    }

    /**
     * Verify customer phone
     */
    public function verifyPhone(int $id): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->markPhoneAsVerified();
        return $customer;
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(int $id): Customer
    {
        $customer = Customer::findOrFail($id);
        $customer->updateLastLogin();
        return $customer;
    }

    /**
     * Get customer addresses
     */
    public function getAddresses(int $customerId): Collection
    {
        return CustomerAddress::where('customer_id', $customerId)->get();
    }

    /**
     * Get single address
     */
    public function getAddress(int $customerId, int $addressId): CustomerAddress
    {
        return CustomerAddress::where('customer_id', $customerId)
            ->findOrFail($addressId);
    }

    /**
     * Create new address for customer
     */
    public function createAddress(int $customerId, array $data): CustomerAddress
    {
        // Verify customer exists and belongs to current tenant
        $customer = Customer::findOrFail($customerId);

        $data['customer_id'] = $customerId;
        $data['store_id'] = tenant()->id; // Explicit tenant scoping

        // If this is set as default, the model will auto-unset others
        $address = CustomerAddress::create($data);

        return $address->fresh();
    }

    /**
     * Update existing address
     */
    public function updateAddress(int $customerId, int $addressId, array $data): CustomerAddress
    {
        $address = CustomerAddress::where('customer_id', $customerId)
            ->findOrFail($addressId);

        $address->update($data);

        return $address->fresh();
    }

    /**
     * Delete address
     */
    public function deleteAddress(int $customerId, int $addressId): bool
    {
        $address = CustomerAddress::where('customer_id', $customerId)
            ->findOrFail($addressId);

        // Don't allow deleting the only address if it's default
        if ($address->is_default) {
            $otherAddresses = CustomerAddress::where('customer_id', $customerId)
                ->where('id', '!=', $addressId)
                ->count();

            if ($otherAddresses === 0) {
                throw new \Exception('Cannot delete the only default address. Set another address as default first.');
            }

            // Set another address as default before deleting
            $newDefault = CustomerAddress::where('customer_id', $customerId)
                ->where('id', '!=', $addressId)
                ->first();

            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return $address->delete();
    }

    /**
     * Set address as default
     */
    public function setDefaultAddress(int $customerId, int $addressId): CustomerAddress
    {
        $address = CustomerAddress::where('customer_id', $customerId)
            ->findOrFail($addressId);

        $address->update(['is_default' => true]);

        return $address->fresh();
    }

    /**
     * Get customer statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Customer::count(),
            'active' => Customer::active()->count(),
            'inactive' => Customer::where('status', 'inactive')->count(),
            'banned' => Customer::where('status', 'banned')->count(),
            'email_verified' => Customer::whereNotNull('email_verified_at')->count(),
            'phone_verified' => Customer::whereNotNull('phone_verified_at')->count(),
            'new_this_month' => Customer::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }
}
