# Payment Strategy

## Current Implementation (Phase 1-2)

### Manual Payment System

**Status**: Active (No payment gateway integration)  
**Purpose**: Allow vendors to process orders and accept payments manually

### How It Works

1. **Customer Places Order**
   - Customer browses products and adds items to cart
   - Customer proceeds to checkout and fills shipping/billing information
   - Customer selects payment method (Cash on Delivery, Bank Transfer, etc.)
   - Order is created with `payment_status: 'pending'`
   - Order confirmation email sent to customer

2. **Vendor Reviews Order**
   - Vendor logs into admin panel
   - Views new orders in "Pending Payment" section
   - Reviews order details (items, customer info, shipping address)
   - Customer contacts vendor via phone/email/WhatsApp for payment

3. **Manual Payment Processing**
   - Vendor receives payment outside the system (bank transfer, cash, etc.)
   - Vendor marks order as "Paid" in admin panel
   - System updates:
     ```
     payment_status: 'paid'
     payment_method: 'manual' (or 'bank_transfer', 'cash', etc.)
     paid_at: current_timestamp
     paid_by_user_id: vendor_user_id
     payment_notes: "Received via bank transfer - Ref: ABC123"
     ```
   - Order moves to "Processing" stage
   - Payment confirmation email sent to customer

4. **Order Fulfillment**
   - Vendor processes and ships the order
   - Updates order status: `confirmed → processing → shipped → delivered`
   - Customer receives tracking updates

### Database Schema Additions

#### orders table (additional fields)
```sql
ALTER TABLE orders ADD COLUMN (
    payment_method VARCHAR(100) NULL,           -- 'manual', 'bank_transfer', 'cash_on_delivery', etc.
    paid_at TIMESTAMP NULL,                     -- When payment was marked as received
    paid_by_user_id BIGINT UNSIGNED NULL,       -- Which admin/vendor marked it as paid
    payment_notes TEXT NULL,                    -- Internal notes about payment (reference numbers, etc.)
    payment_proof_url VARCHAR(500) NULL,        -- Customer uploaded payment proof (screenshot, etc.)
    
    FOREIGN KEY (paid_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_paid_at (paid_at)
);
```

#### payments table (updated for manual payments)
```sql
-- gateway: 'manual', 'stripe', 'paypal', etc. (default: 'manual')
-- payment_method: 'bank_transfer', 'cash', 'upi', 'check', etc.
-- status: 'pending' → 'completed' (when vendor marks as paid)
-- metadata: Store additional info like reference numbers, proof URLs, etc.
```

### API Endpoints

#### Admin: Mark Order as Paid
```http
POST /api/v1/admin/orders/{id}/mark-as-paid
Authorization: Bearer {token}
X-Store-ID: {store_id}

{
  "payment_method": "bank_transfer",
  "payment_notes": "Received via Bank of America - Ref: TXN123456",
  "amount": 150.00
}

Response:
{
  "data": {
    "order": {
      "id": 123,
      "order_number": "ORD-2026-001",
      "payment_status": "paid",
      "paid_at": "2026-04-06T10:30:00Z",
      "paid_by": {
        "id": 5,
        "name": "Store Manager"
      }
    }
  }
}
```

#### Admin: Get Pending Payment Orders
```http
GET /api/v1/admin/orders?payment_status=pending&sort=-created_at

Response:
{
  "data": [
    {
      "id": 123,
      "order_number": "ORD-2026-001",
      "customer": {
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "+1234567890"
      },
      "total": 150.00,
      "payment_status": "pending",
      "created_at": "2026-04-06T09:00:00Z",
      "payment_proof_url": "https://..."  // if customer uploaded
    }
  ]
}
```

#### Customer: Upload Payment Proof
```http
POST /api/v1/orders/{orderNumber}/upload-payment-proof
Content-Type: multipart/form-data

{
  "proof": [FILE],
  "notes": "Paid via bank transfer on 2026-04-06"
}
```

### Admin Panel UI

**Pending Payments View**:
```
┌─────────────────────────────────────────────────────────┐
│  Orders > Pending Payment (12)                          │
├─────────────────────────────────────────────────────────┤
│  Order#      │ Customer    │ Total   │ Date      │ Actions │
│──────────────┼─────────────┼─────────┼───────────┼─────────│
│  ORD-001     │ John Doe    │ $150.00 │ 2hr ago   │ [Mark  ]│
│              │ +1234567890 │         │           │   Paid  │
│              │ View Proof ↗│         │           │ [Cancel]│
├──────────────┼─────────────┼─────────┼───────────┼─────────┤
│  ORD-002     │ Jane Smith  │ $89.50  │ 5hr ago   │ [Mark  ]│
│              │ +0987654321 │         │           │   Paid  │
│              │             │         │           │ [Cancel]│
└─────────────────────────────────────────────────────────┘
```

**Mark as Paid Modal**:
```
┌──────────────────────────────────────────────┐
│  Mark Order ORD-001 as Paid                  │
├──────────────────────────────────────────────┤
│  Order Total: $150.00                        │
│                                               │
│  Payment Method: [Bank Transfer ▼]           │
│  - Bank Transfer                              │
│  - Cash on Delivery                           │
│  - UPI/QR Code                                │
│  - Check                                      │
│  - Other                                      │
│                                               │
│  Amount Received: [$150.00     ]             │
│                                               │
│  Payment Notes (optional):                    │
│  ┌───────────────────────────────────────┐   │
│  │ Reference: TXN123456                  │   │
│  │ Bank: Bank of America                 │   │
│  │                                        │   │
│  └───────────────────────────────────────┘   │
│                                               │
│  [Cancel]              [Confirm Payment]     │
└──────────────────────────────────────────────┘
```

### Permissions

```php
// Spatie Permission names
'view orders'           // Can see orders list
'edit orders'           // Can update order details
'process orders'        // Can mark as paid, ship, etc.
'cancel orders'         // Can cancel orders
'refund orders'         // Can mark as refunded
```

**Role Assignments**:
- `owner`: All order permissions
- `admin`: All order permissions
- `manager`: view, edit, process orders
- `staff`: view orders only

## Future Implementation (Phase 3+)

### Automated Payment Gateway Integration

**Target Gateways**:
1. **Stripe** (Primary - International)
2. **PayPal** (Secondary - International)
3. **Razorpay** (India)
4. **Flutterwave** (Africa)
5. **Local gateways** per store requirement

### Migration Path

#### Step 1: Add Gateway Support (Non-Breaking)
```sql
-- Add new fields to orders table
ALTER TABLE orders ADD COLUMN (
    payment_gateway VARCHAR(50) DEFAULT 'manual',  -- 'manual', 'stripe', 'paypal', etc.
    gateway_transaction_id VARCHAR(255) NULL,      -- Gateway's transaction ID
    gateway_customer_id VARCHAR(255) NULL,         -- Gateway's customer ID
    gateway_metadata JSON NULL                     -- Gateway-specific data
);

-- Existing orders remain with payment_gateway = 'manual'
```

#### Step 2: Implement Gateway Abstraction
```php
interface PaymentGatewayInterface {
    public function createPaymentIntent(Order $order): PaymentIntent;
    public function capturePayment(string $transactionId): Payment;
    public function refund(Payment $payment, float $amount): Refund;
    public function verifyWebhook(Request $request): bool;
}

class StripeGateway implements PaymentGatewayInterface { }
class PayPalGateway implements PaymentGatewayInterface { }
class ManualGateway implements PaymentGatewayInterface { }
```

#### Step 3: Store Configuration
```sql
-- Add to stores table
ALTER TABLE stores ADD COLUMN (
    payment_gateways JSON NULL  -- Enabled gateways per store
);

-- Example data:
{
  "manual": {
    "enabled": true,
    "methods": ["bank_transfer", "cash_on_delivery", "upi"]
  },
  "stripe": {
    "enabled": true,
    "public_key": "pk_...",
    "secret_key": "sk_...",
    "webhook_secret": "whsec_..."
  }
}
```

#### Step 4: Checkout Flow Update
**Current (Manual)**:
1. Customer selects "Bank Transfer" → Order created → Vendor marks paid

**Future (Automated)**:
1. Customer selects "Card Payment"
2. Frontend calls: `POST /api/v1/checkout/create-payment-intent`
3. Stripe widget appears, customer enters card
4. Payment processed automatically
5. Webhook updates order: `payment_status: 'paid'`
6. Order automatically moves to processing

**Both systems run in parallel** - stores can support both manual and automated payments.

### Backward Compatibility

✅ All existing manual payment orders remain functional  
✅ Stores can enable/disable gateways individually  
✅ Manual payment flow always available as fallback  
✅ No breaking changes to existing API endpoints  

## Security Considerations

### Manual Payments
- ✅ Only authenticated admin users can mark orders as paid
- ✅ Log all payment status changes with user ID and timestamp
- ✅ Require permission: `process orders`
- ✅ Validate amount matches order total
- ✅ Prevent duplicate "mark as paid" actions

### Future Gateway Integration
- ✅ Verify webhook signatures
- ✅ Store gateway credentials encrypted
- ✅ PCI compliance for card storage
- ✅ 3D Secure authentication
- ✅ Fraud detection integration

## Testing Checklist

### Manual Payment Tests
- [ ] Admin can view pending payment orders
- [ ] Admin can mark order as paid
- [ ] Payment status updates correctly
- [ ] Email notification sent on payment confirmation
- [ ] Customer can upload payment proof
- [ ] Only authorized users can mark as paid
- [ ] Payment history logged correctly
- [ ] Refund process works

### Integration Tests (Future)
- [ ] Stripe payment intent creation
- [ ] Webhook signature verification
- [ ] Payment capture on successful charge
- [ ] Refund processing
- [ ] Failed payment handling

## Timeline

**Phase 1** (Current): Manual payments only - **COMPLETE**  
**Phase 2** (Months 1-3): Product catalog, order management  
**Phase 3** (Months 4-6): Core gateway integration (Stripe)  
**Phase 4** (Months 7-9): Additional gateways (PayPal, Razorpay, etc.)  
**Phase 5** (Month 10+): Advanced features (subscriptions, split payments, etc.)

---

**Last Updated**: April 6, 2026  
**Status**: Phase 1 (Manual Payments) - Active
