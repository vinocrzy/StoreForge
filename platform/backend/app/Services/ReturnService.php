<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\ReturnItem;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Create a return request from a customer.
     *
     * Validates:
     *  - Order belongs to the authenticated customer
     *  - Order status is 'delivered'
     *  - No pending/approved return already exists for the same order
     */
    public function createReturn(array $data, int $customerId): ReturnRequest
    {
        $order = Order::findOrFail($data['order_id']);

        if ((int) $order->customer_id !== $customerId) {
            abort(403, 'You do not have permission to return this order.');
        }

        if ($order->status !== 'delivered') {
            abort(422, 'Only delivered orders can be returned.');
        }

        $existing = ReturnRequest::withoutGlobalScope('store')
            ->where('store_id', $order->store_id)
            ->where('order_id', $order->id)
            ->whereIn('status', ['requested', 'approved'])
            ->first();

        if ($existing) {
            abort(422, 'A return request already exists for this order.');
        }

        return DB::transaction(function () use ($data, $customerId, $order) {
            $return = ReturnRequest::create([
                'store_id'       => $order->store_id,
                'order_id'       => $order->id,
                'customer_id'    => $customerId,
                'reason'         => $data['reason'],
                'reason_details' => $data['reason_details'] ?? null,
                'status'         => 'requested',
            ]);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    ReturnItem::create([
                        'return_id'     => $return->id,
                        'order_item_id' => $item['order_item_id'],
                        'quantity'      => $item['quantity'],
                        'reason'        => $item['reason'] ?? null,
                        'created_at'    => now(),
                    ]);
                }
            }

            return $return->load('items.orderItem', 'order');
        });
    }

    /**
     * Approve a return and set the refund amount.
     */
    public function approveReturn(ReturnRequest $return, float $refundAmount, string $notes = ''): ReturnRequest
    {
        $return->update([
            'status'        => 'approved',
            'refund_amount' => $refundAmount,
            'admin_notes'   => $notes,
        ]);

        return $return->fresh();
    }

    /**
     * Reject a return with admin notes.
     */
    public function rejectReturn(ReturnRequest $return, string $notes = ''): ReturnRequest
    {
        $return->update([
            'status'      => 'rejected',
            'admin_notes' => $notes,
        ]);

        return $return->fresh();
    }

    /**
     * Process the refund via PaymentService and mark return as refunded.
     *
     * @return array  Gateway response
     */
    public function processRefund(ReturnRequest $return): array
    {
        if ($return->status !== 'approved') {
            abort(422, 'Return must be approved before processing a refund.');
        }

        if (is_null($return->refund_amount) || $return->refund_amount <= 0) {
            abort(422, 'Refund amount must be set before processing.');
        }

        $order = $return->order()->withoutGlobalScope('store')->first();

        $result = $this->paymentService->processRefund($order, (float) $return->refund_amount, 'Customer return #' . $return->return_number);

        $return->update(['status' => 'refunded']);

        return $result;
    }
}
