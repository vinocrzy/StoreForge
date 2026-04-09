<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerAddressRequest;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @group Customers
 * 
 * Manage customers for the authenticated store. All operations are automatically scoped to the current tenant.
 * Supports phone-first authentication strategy with optional email.
 * 
 * @authenticated
 */
class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     * List customers
     * 
     * Get a paginated list of customers with optional filtering and sorting.
     * Customers are automatically scoped to the authenticated store.
     * 
     * @queryParam search string Search customers by name, email, or phone. Example: john
     * @queryParam status string Filter by status: active, inactive, banned. Example: active
     * @queryParam is_active boolean Filter active customers. Example: 1
     * @queryParam email_verified boolean Filter by email verification status. Example: 1
     * @queryParam phone_verified boolean Filter by phone verification status. Example: 1
     * @queryParam sort_by string Sort field. Example: created_at
     * @queryParam sort_order string Sort direction: asc, desc. Example: desc
     * @queryParam per_page integer Items per page (max 100). Example: 20
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "first_name": "John",
     *       "last_name": "Doe",
     *       "email": "john@example.com",
     *       "phone": "+12025551234",
     *       "status": "active",
     *       "email_verified_at": "2026-04-01T10:00:00Z",
     *       "phone_verified_at": "2026-04-01T10:00:00Z",
     *       "created_at": "2026-04-01T10:00:00Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "per_page": 20,
     *     "total": 45
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'status', 'is_active', 'email_verified',
            'phone_verified', 'sort_by', 'sort_order'
        ]);

        $perPage = min($request->input('per_page', 20), 100);
        
        $customers = $this->customerService->getCustomers($filters, $perPage);

        return response()->json($customers);
    }

    /**
     * Get customer details
     * 
     * Retrieve a single customer with all related data (addresses, default address).
     * 
     * @urlParam id integer required Customer ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     "phone": "+12025551234",
     *     "status": "active",
     *     "date_of_birth": "1990-01-15",
     *     "gender": "male",
     *     "email_verified_at": "2026-04-01T10:00:00Z",
     *     "phone_verified_at": "2026-04-01T10:00:00Z",
     *     "last_login_at": "2026-04-06T08:00:00Z",
     *     "created_at": "2026-04-01T10:00:00Z",
     *     "addresses": [],
     *     "default_address": null
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Customer not found"
     * }
     */
    public function show(int $id): JsonResponse
    {
        $customer = $this->customerService->getCustomer($id);

        return response()->json(['data' => $customer]);
    }

    /**
     * Create customer
     * 
     * Create a new customer for the authenticated store. Phone is required (E.164 format), email is optional.
     * 
     * @bodyParam first_name string required Customer first name. Example: John
     * @bodyParam last_name string required Customer last name. Example: Doe
     * @bodyParam phone string required Phone number in E.164 format. Example: +12025551234
     * @bodyParam email string Customer email (optional). Example: john@example.com
     * @bodyParam password string required Password (min 8 characters). Example: SecurePass123
     * @bodyParam status string Customer status: active, inactive, banned. Example: active
     * @bodyParam date_of_birth date Date of birth. Example: 1990-01-15
     * @bodyParam gender string Gender: male, female, other, prefer_not_to_say. Example: male
     * @bodyParam address object Optional address to create with customer.
     * @bodyParam address.type string Address type: billing, shipping, both. Example: both
     * @bodyParam address.label string Address label. Example: Home
     * @bodyParam address.first_name string Address first name. Example: John
     * @bodyParam address.last_name string Address last name. Example: Doe
     * @bodyParam address.company string Company name. Example: Tech Corp
     * @bodyParam address.address_line1 string Street address. Example: 123 Main St
     * @bodyParam address.address_line2 string Apartment/Suite. Example: Apt 4B
     * @bodyParam address.city string City. Example: New York
     * @bodyParam address.state_province string State/Province. Example: NY
     * @bodyParam address.postal_code string Postal code. Example: 10001
     * @bodyParam address.country string Country code (ISO 3166-1 alpha-2). Example: US
     * @bodyParam address.phone string Address phone (E.164). Example: +12025551234
     * 
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 1,
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     "phone": "+12025551234",
     *     "status": "active",
     *     "created_at": "2026-04-06T10:00:00Z"
     *   }
     * }
     * 
     * @response 422 scenario="Validation failed" {
     *   "message": "The given data was invalid",
     *   "errors": {
     *     "phone": ["Phone number is required for customer accounts"],
     *     "phone.unique": ["This phone number is already registered"]
     *   }
     * }
     */
    public function store(CustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->createCustomer($request->validated());

        return response()->json(['data' => $customer], 201);
    }

    /**
     * Update customer
     * 
     * Update an existing customer. All fields are optional for updates.
     * 
     * @urlParam id integer required Customer ID. Example: 1
     * 
     * @bodyParam first_name string Customer first name. Example: John
     * @bodyParam last_name string Customer last name. Example: Doe
     * @bodyParam phone string Phone number in E.164 format. Example: +12025551234
     * @bodyParam email string Customer email. Example: john@example.com
     * @bodyParam password string Password (min 8 characters). Example: NewSecurePass123
     * @bodyParam status string Customer status: active, inactive, banned. Example: active
     * @bodyParam date_of_birth date Date of birth. Example: 1990-01-15
     * @bodyParam gender string Gender: male, female, other, prefer_not_to_say. Example: male
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 1,
     *     "first_name": "John",
     *     "last_name": "Doe",
     *     "email": "john@example.com",
     *     "phone": "+12025551234",
     *     "status": "active",
     *     "updated_at": "2026-04-06T10:00:00Z"
     *   }
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Customer not found"
     * }
     */
    public function update(CustomerRequest $request, int $id): JsonResponse
    {
        $customer = $this->customerService->updateCustomer($id, $request->validated());

        return response()->json(['data' => $customer]);
    }

    /**
     * Delete customer
     * 
     * Soft delete a customer. Customer data is preserved but marked as deleted.
     * 
     * @urlParam id integer required Customer ID. Example: 1
     * 
     * @response 200 scenario="Deleted" {
     *   "message": "Customer deleted successfully"
     * }
     * 
     * @response 404 scenario="Not found" {
     *   "message": "Customer not found"
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        $this->customerService->deleteCustomer($id);

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    /**
     * Update customer status
     * 
     * Update customer status (active, inactive, banned).
     * 
     * @urlParam id integer required Customer ID. Example: 1
     * 
     * @bodyParam status string required New status: active, inactive, banned. Example: banned
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 1,
     *     "status": "banned",
     *     "updated_at": "2026-04-06T10:00:00Z"
     *   }
     * }
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:active,inactive,banned',
        ]);

        $customer = $this->customerService->updateStatus($id, $request->status);

        return response()->json(['data' => $customer]);
    }

    /**
     * Verify customer email
     * 
     * Mark customer email as verified.
     * 
     * @urlParam id integer required Customer ID. Example: 1
     * 
     * @response 200 scenario="Verified" {
     *   "message": "Email verified successfully",
     *   "data": {
     *     "id": 1,
     *     "email_verified_at": "2026-04-06T10:00:00Z"
     *   }
     * }
     */
    public function verifyEmail(int $id): JsonResponse
    {
        $customer = $this->customerService->verifyEmail($id);

        return response()->json([
            'message' => 'Email verified successfully',
            'data' => $customer
        ]);
    }

    /**
     * Verify customer phone
     * 
     * Mark customer phone as verified.
     * 
     * @urlParam id integer required Customer ID. Example: 1
     * 
     * @response 200 scenario="Verified" {
     *   "message": "Phone verified successfully",
     *   "data": {
     *     "id": 1,
     *     "phone_verified_at": "2026-04-06T10:00:00Z"
     *   }
     * }
     */
    public function verifyPhone(int $id): JsonResponse
    {
        $customer = $this->customerService->verifyPhone($id);

        return response()->json([
            'message' => 'Phone verified successfully',
            'data' => $customer
        ]);
    }

    /**
     * Get customer statistics
     * 
     * Get statistics about customers for the current store.
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "total": 45,
     *     "active": 40,
     *     "inactive": 3,
     *     "banned": 2,
     *     "email_verified": 38,
     *     "phone_verified": 45,
     *     "new_this_month": 12
     *   }
     * }
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->customerService->getStatistics();

        return response()->json(['data' => $stats]);
    }

    /**
     * Export customers to CSV
     *
     * Download a CSV file of all customers for the current store, with optional filters.
     *
     * @queryParam search string Search customers by name, email, or phone. Example: john
     * @queryParam status string Filter by status. Example: active
     * @queryParam sort_by string Sort field. Example: created_at
     * @queryParam sort_order string Sort direction: asc, desc. Example: desc
     *
     * @response 200 scenario="Success" Binary CSV file
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
        $customers = $this->customerService->getCustomersForExport($filters);

        $filename = 'customers_export_' . now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        return response()->stream(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID', 'First Name', 'Last Name', 'Email', 'Phone',
                'Status', 'Gender',
                'Email Verified', 'Phone Verified',
                'Last Login', 'Created At',
            ]);

            foreach ($customers as $customer) {
                fputcsv($handle, [
                    $customer->id,
                    $customer->first_name,
                    $customer->last_name,
                    $customer->email ?? '',
                    $customer->phone,
                    $customer->status,
                    $customer->gender ?? '',
                    $customer->email_verified_at ? 'Yes' : 'No',
                    $customer->phone_verified_at ? 'Yes' : 'No',
                    $customer->last_login_at?->toISOString() ?? '',
                    $customer->created_at->toISOString(),
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Bulk update customers
     *
     * Perform bulk actions on multiple customers (e.g., status update).
     *
     * @bodyParam ids array required Array of customer IDs. Example: [1, 2, 3]
     * @bodyParam action string required Action to perform: update_status. Example: update_status
     * @bodyParam status string required (for update_status) New status: active, inactive, banned. Example: active
     *
     * @response 200 {
     *  "message": "3 customers updated successfully",
     *  "updated": 3
     * }
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:customers,id',
            'action' => 'required|in:update_status',
            'status' => 'required_if:action,update_status|in:active,inactive,banned',
        ]);

        $updated = 0;

        if ($request->action === 'update_status') {
            $updated = $this->customerService->bulkUpdateStatus($request->ids, $request->status);
        }

        return response()->json([
            'message' => "{$updated} customers updated successfully",
            'updated' => $updated,
        ]);
    }

    // ===== Address Management =====

    /**
     * List customer addresses
     * 
     * Get all addresses for a specific customer.
     * 
     * @urlParam customerId integer required Customer ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "type": "both",
     *       "label": "Home",
     *       "first_name": "John",
     *       "last_name": "Doe",
     *       "address_line1": "123 Main St",
     *       "city": "New York",
     *       "state_province": "NY",
     *       "postal_code": "10001",
     *       "country": "US",
     *       "phone": "+12025551234",
     *       "is_default": true
     *     }
     *   ]
     * }
     */
    public function listAddresses(int $customerId): JsonResponse
    {
        $addresses = $this->customerService->getAddresses($customerId);

        return response()->json(['data' => $addresses]);
    }

    /**
     * Get address details
     * 
     * Get a specific address for a customer.
     * 
     * @urlParam customerId integer required Customer ID. Example: 1
     * @urlParam addressId integer required Address ID. Example: 1
     * 
     * @response 200 scenario="Success" {
     *   "data": {
     *     "id": 1,
     *     "type": "both",
     *     "label": "Home",
     *     "address_line1": "123 Main St",
     *     "city": "New York",
     *     "is_default": true
     *   }
     * }
     */
    public function showAddress(int $customerId, int $addressId): JsonResponse
    {
        $address = $this->customerService->getAddress($customerId, $addressId);

        return response()->json(['data' => $address]);
    }

    /**
     * Create address
     * 
     * Add a new address for a customer.
     * 
     * @urlParam customerId integer required Customer ID. Example: 1
     * 
     * @bodyParam type string required Address type: billing, shipping, both. Example: both
     * @bodyParam label string Address label. Example: Home
     * @bodyParam first_name string required First name. Example: John
     * @bodyParam last_name string required Last name. Example: Doe
     * @bodyParam company string Company name. Example: Tech Corp
     * @bodyParam address_line1 string required Street address. Example: 123 Main St
     * @bodyParam address_line2 string Apartment/Suite. Example: Apt 4B
     * @bodyParam city string required City. Example: New York
     * @bodyParam state_province string required State/Province. Example: NY
     * @bodyParam postal_code string required Postal code. Example: 10001
     * @bodyParam country string required Country code (ISO 3166-1 alpha-2). Example: US
     * @bodyParam phone string required Phone (E.164). Example: +12025551234
     * @bodyParam is_default boolean Set as default address. Example: false
     * 
     * @response 201 scenario="Created" {
     *   "data": {
     *     "id": 2,
     *     "type": "shipping",
     *     "label": "Office",
     *     "address_line1": "456 Business Ave",
     *     "city": "New York",
     *     "is_default": false
     *   }
     * }
     */
    public function storeAddress(CustomerAddressRequest $request, int $customerId): JsonResponse
    {
        $address = $this->customerService->createAddress($customerId, $request->validated());

        return response()->json(['data' => $address], 201);
    }

    /**
     * Update address
     * 
     * Update an existing customer address.
     * 
     * @urlParam customerId integer required Customer ID. Example: 1
     * @urlParam addressId integer required Address ID. Example: 1
     * 
     * @bodyParam type string Address type: billing, shipping, both. Example: both
     * @bodyParam label string Address label. Example: Home
     * @bodyParam address_line1 string Street address. Example: 123 Main St
     * @bodyParam city string City. Example: New York
     * @bodyParam is_default boolean Set as default address. Example: true
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 1,
     *     "label": "Home - Updated",
     *     "is_default": true
     *   }
     * }
     */
    public function updateAddress(CustomerAddressRequest $request, int $customerId, int $addressId): JsonResponse
    {
        $address = $this->customerService->updateAddress($customerId, $addressId, $request->validated());

        return response()->json(['data' => $address]);
    }

    /**
     * Delete address
     * 
     * Delete a customer address. Cannot delete the only default address.
     * 
     * @urlParam customerId integer required Customer ID. Example: 1
     * @urlParam addressId integer required Address ID. Example: 2
     * 
     * @response 200 scenario="Deleted" {
     *   "message": "Address deleted successfully"
     * }
     * 
     * @response 400 scenario="Cannot delete" {
     *   "message": "Cannot delete the only default address. Set another address as default first."
     * }
     */
    public function destroyAddress(int $customerId, int $addressId): JsonResponse
    {
        try {
            $this->customerService->deleteAddress($customerId, $addressId);

            return response()->json(['message' => 'Address deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Set default address
     * 
     * Set an address as the default for a customer. Automatically unsets other default addresses.
     * 
     * @urlParam customerId integer required Customer ID. Example: 1
     * @urlParam addressId integer required Address ID. Example: 2
     * 
     * @response 200 scenario="Updated" {
     *   "data": {
     *     "id": 2,
     *     "is_default": true
     *   }
     * }
     */
    public function setDefaultAddress(int $customerId, int $addressId): JsonResponse
    {
        $address = $this->customerService->setDefaultAddress($customerId, $addressId);

        return response()->json(['data' => $address]);
    }
}
