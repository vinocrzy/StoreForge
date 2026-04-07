import { useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { useGetOrderQuery, useCancelOrderMutation, useFulfillOrderMutation } from '../../services/orders';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import type { OrderStatus, PaymentStatus, FulfillmentStatus } from '../../types/order';
import { formatPrice, getStoreCurrency } from '../../utils/currency';
import UpdateOrderStatusModal from './components/UpdateOrderStatusModal';
import RecordPaymentModal from './components/RecordPaymentModal';

const OrderDetailsPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const orderId = parseInt(id!, 10);

  const { data: order, isLoading, error } = useGetOrderQuery(orderId);
  const [cancelOrder] = useCancelOrderMutation();
  const [fulfillOrder] = useFulfillOrderMutation();

  const [showStatusModal, setShowStatusModal] = useState(false);
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [alert, setAlert] = useState<{variant: 'success' | 'error', title: string, message: string} | null>(null);

  // Helper function to get order status badge color
  const getStatusBadgeColor = (status: OrderStatus): 'primary' | 'success' | 'error' | 'warning' | 'info' => {
    switch (status) {
      case 'confirmed':
      case 'processing':
        return 'info';
      case 'shipped':
        return 'primary';
      case 'delivered':
        return 'success';
      case 'cancelled':
      case 'refunded':
        return 'error';
      default:
        return 'warning';
    }
  };

  // Helper function to get payment status badge color
  const getPaymentBadgeColor = (status: PaymentStatus): 'primary' | 'success' | 'error' | 'warning' => {
    switch (status) {
      case 'paid':
        return 'success';
      case 'failed':
      case 'refunded':
      case 'partially_refunded':
        return 'error';
      default:
        return 'warning';
    }
  };

  // Helper function to get fulfillment status badge color
  const getFulfillmentBadgeColor = (status: FulfillmentStatus): 'success' | 'warning' | 'info' => {
    switch (status) {
      case 'fulfilled':
        return 'success';
      case 'partial':
        return 'warning';
      default:
        return 'info';
    }
  };

  const handleFulfillOrder = async () => {
    try {
      await fulfillOrder(orderId).unwrap();
      setAlert({
        variant: 'success',
        title: 'Order Fulfilled',
        message: 'Order has been successfully fulfilled and inventory has been updated.'
      });
    } catch (error) {
      setAlert({
        variant: 'error',
        title: 'Fulfillment Failed',
        message: 'Failed to fulfill order. Please try again.'
      });
    }
  };

  const handleCancelOrder = async () => {
    if (!confirm('Are you sure you want to cancel this order?')) return;

    try {
      await cancelOrder({ id: orderId, data: { reason: 'Admin cancelled' } }).unwrap();
      setAlert({
        variant: 'success',
        title: 'Order Cancelled',
        message: 'Order has been successfully cancelled.'
      });
    } catch (error) {
      setAlert({
        variant: 'error',
        title: 'Cancellation Failed',
        message: 'Failed to cancel order. Please try again.'
      });
    }
  };

  if (isLoading) {
    return (
      <div className="p-6">
        <div className="text-center text-gray-500 dark:text-gray-400">
          Loading order details...
        </div>
      </div>
    );
  }

  if (error || !order) {
    return (
      <div className="p-6">
        <Alert 
          variant="error" 
          title="Error Loading Order"
          message="Unable to load order details. Please try again later." 
        />
        <Button variant="secondary" onClick={() => navigate('/orders')} className="mt-4">
          Back to Orders
        </Button>
      </div>
    );
  }

  const currency = order.currency || getStoreCurrency();

  return (
    <div className="p-6">
      {alert && (
        <div className="mb-4">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}

      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div>
          <Button variant="ghost" onClick={() => navigate('/orders')} className="mb-3">
            ← Back to Orders
          </Button>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Order {order.order_number}
          </h1>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Placed on {new Date(order.placed_at || order.created_at).toLocaleDateString('en-US', {
              month: 'long',
              day: 'numeric',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            })}
          </p>
        </div>
        <div className="flex gap-2">
          {order.payment_status === 'pending' && (
            <Button variant="success" onClick={() => setShowPaymentModal(true)}>
              Record Payment
            </Button>
          )}
          {order.status !== 'cancelled' && order.status !== 'delivered' && (
            <Button variant="primary" onClick={() => setShowStatusModal(true)}>
              Update Status
            </Button>
          )}
          {order.fulfillment_status === 'unfulfilled' && order.status !== 'cancelled' && (
            <Button variant="primary" onClick={handleFulfillOrder}>
              Fulfill Order
            </Button>
          )}
          {order.status !== 'cancelled' && order.status !== 'delivered' && (
            <Button variant="danger" onClick={handleCancelOrder}>
              Cancel Order
            </Button>
          )}
        </div>
      </div>

      {/* Status Cards */}
      <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <div className="rounded-lg bg-white p-4 shadow dark:bg-boxdark">
          <p className="text-sm text-gray-600 dark:text-gray-400">Order Status</p>
          <div className="mt-2">
            <Badge color={getStatusBadgeColor(order.status)}>
              {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
            </Badge>
          </div>
        </div>
        <div className="rounded-lg bg-white p-4 shadow dark:bg-boxdark">
          <p className="text-sm text-gray-600 dark:text-gray-400">Payment Status</p>
          <div className="mt-2">
            <Badge color={getPaymentBadgeColor(order.payment_status)}>
              {order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1).replace('_', ' ')}
            </Badge>
          </div>
        </div>
        <div className="rounded-lg bg-white p-4 shadow dark:bg-boxdark">
          <p className="text-sm text-gray-600 dark:text-gray-400">Fulfillment Status</p>
          <div className="mt-2">
            <Badge color={getFulfillmentBadgeColor(order.fulfillment_status)}>
              {order.fulfillment_status.charAt(0).toUpperCase() + order.fulfillment_status.slice(1)}
            </Badge>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left Column - Order Items & Customer */}
        <div className="lg:col-span-2 space-y-6">
          {/* Order Items */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Order Items</h2>
            </div>
            <div className="p-4">
              <table className="w-full">
                <thead>
                  <tr className="border-b border-stroke dark:border-strokedark">
                    <th className="pb-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">Product</th>
                    <th className="pb-3 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">Quantity</th>
                    <th className="pb-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">Price</th>
                    <th className="pb-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {order.items?.map((item) => (
                    <tr key={item.id} className="border-b border-stroke dark:border-strokedark">
                      <td className="py-3">
                        <div>
                          <p className="font-medium text-gray-900 dark:text-white">
                            {item.product_snapshot.name}
                          </p>
                          <p className="text-sm text-gray-500 dark:text-gray-400">
                            SKU: {item.product_snapshot.sku}
                          </p>
                        </div>
                      </td>
                      <td className="py-3 text-center text-gray-700 dark:text-gray-300">
                        {item.quantity}
                      </td>
                      <td className="py-3 text-right text-gray-700 dark:text-gray-300">
                        {formatPrice(item.price, currency)}
                      </td>
                      <td className="py-3 text-right font-medium text-gray-900 dark:text-white">
                        {formatPrice(item.total, currency)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>

              {/* Order Totals */}
              <div className="mt-6 space-y-2 border-t border-stroke dark:border-strokedark pt-4">
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600 dark:text-gray-400">Subtotal</span>
                  <span className="text-gray-900 dark:text-white">{formatPrice(order.subtotal, currency)}</span>
                </div>
                {Number(order.discount_amount) > 0 && (
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-600 dark:text-gray-400">Discount</span>
                    <span className="text-success-500">-{formatPrice(order.discount_amount, currency)}</span>
                  </div>
                )}
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600 dark:text-gray-400">Shipping</span>
                  <span className="text-gray-900 dark:text-white">{formatPrice(order.shipping_amount, currency)}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600 dark:text-gray-400">Tax</span>
                  <span className="text-gray-900 dark:text-white">{formatPrice(order.tax_amount, currency)}</span>
                </div>
                <div className="flex justify-between border-t border-stroke dark:border-strokedark pt-2 text-base font-bold">
                  <span className="text-gray-900 dark:text-white">Total</span>
                  <span className="text-gray-900 dark:text-white">{formatPrice(order.total, currency)}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Customer Information */}
          {order.customer && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Customer Information</h2>
              </div>
              <div className="p-4">
                <div className="space-y-2">
                  <div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">Name</p>
                    <p className="font-medium text-gray-900 dark:text-white">
                      {order.customer.first_name} {order.customer.last_name}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">Email</p>
                    <p className="text-gray-900 dark:text-white">{order.customer.email}</p>
                  </div>
                  <div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">Phone</p>
                    <p className="text-gray-900 dark:text-white">{order.customer.phone}</p>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Right Column - Payment & Addresses */}
        <div className="space-y-6">
          {/* Payment Information */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Payment</h2>
            </div>
            <div className="p-4 space-y-3">
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Status</p>
                <Badge color={getPaymentBadgeColor(order.payment_status)}>
                  {order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1).replace('_', ' ')}
                </Badge>
              </div>
              {order.payment_method && (
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Payment Method</p>
                  <p className="text-gray-900 dark:text-white capitalize">{order.payment_method.replace('_', ' ')}</p>
                </div>
              )}
              {order.paid_at && (
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Paid At</p>
                  <p className="text-gray-900 dark:text-white">
                    {new Date(order.paid_at).toLocaleDateString('en-US', {
                      month: 'short',
                      day: 'numeric',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    })}
                  </p>
                </div>
              )}
              {order.payments && order.payments.length > 0 && (
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">Payment History</p>
                  <div className="space-y-2">
                    {order.payments.map((payment) => (
                      <div key={payment.id} className="rounded border border-stroke dark:border-strokedark p-2">
                        <div className="flex justify-between text-sm">
                          <span className="text-gray-900 dark:text-white">{formatPrice(payment.amount, currency)}</span>
                          <Badge color={payment.status === 'completed' ? 'success' : 'warning'} size="sm">
                            {payment.status}
                          </Badge>
                        </div>
                        {payment.transaction_id && (
                          <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Ref: {payment.transaction_id}
                          </p>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          {/* Shipping Address */}
          {order.shipping_address && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Shipping Address</h2>
              </div>
              <div className="p-4">
                <div className="text-gray-900 dark:text-white space-y-1">
                  <p>{order.shipping_address.first_name} {order.shipping_address.last_name}</p>
                  <p>{order.shipping_address.address_line1}</p>
                  {order.shipping_address.address_line2 && <p>{order.shipping_address.address_line2}</p>}
                  <p>{order.shipping_address.city}, {order.shipping_address.state} {order.shipping_address.postal_code}</p>
                  <p>{order.shipping_address.country}</p>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Phone: {order.shipping_address.phone}</p>
                </div>
              </div>
            </div>
          )}

          {/* Notes */}
          {(order.customer_note || order.admin_note) && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Notes</h2>
              </div>
              <div className="p-4 space-y-3">
                {order.customer_note && (
                  <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Customer Note</p>
                    <p className="text-gray-900 dark:text-white">{order.customer_note}</p>
                  </div>
                )}
                {order.admin_note && (
                  <div>
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Admin Note</p>
                    <p className="text-gray-900 dark:text-white">{order.admin_note}</p>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Modals */}
      {showStatusModal && (
        <UpdateOrderStatusModal
          orderId={orderId}
          currentStatus={order.status}
          onClose={() => setShowStatusModal(false)}
          onSuccess={() => {
            setShowStatusModal(false);
            setAlert({
              variant: 'success',
              title: 'Status Updated',
              message: 'Order status has been successfully updated.'
            });
          }}
        />
      )}

      {showPaymentModal && (
        <RecordPaymentModal
          orderId={orderId}
          orderTotal={Number(order.total)}
          currency={currency}
          onClose={() => setShowPaymentModal(false)}
          onSuccess={() => {
            setShowPaymentModal(false);
            setAlert({
              variant: 'success',
              title: 'Payment Recorded',
              message: 'Payment has been successfully recorded.'
            });
          }}
        />
      )}
    </div>
  );
};

export default OrderDetailsPage;
