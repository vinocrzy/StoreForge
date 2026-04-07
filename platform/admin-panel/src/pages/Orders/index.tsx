import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useGetOrdersQuery } from '../../services/orders';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import type { OrderFilters, OrderStatus, PaymentStatus, FulfillmentStatus } from '../../types/order';
import { formatPrice, getStoreCurrency } from '../../utils/currency';

const OrdersPage = () => {
  const navigate = useNavigate();
  const [filters, setFilters] = useState<OrderFilters>({
    page: 1,
    per_page: 20,
    search: '',
    status: undefined,
    payment_status: undefined,
    fulfillment_status: undefined,
  });

  const { data: ordersData, isLoading, error } = useGetOrdersQuery(filters);
  const orders = ordersData?.data || [];
  const meta = ordersData?.meta;

  // TODO: Implement alert state when needed
  // const [alert, setAlert] = useState<{type: 'success' | 'error', message: string} | null>(null);

  // Handle filter changes
  const handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setFilters(prev => ({ ...prev, search: event.target.value, page: 1 }));
  };

  const handleStatusChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    const value = event.target.value;
    setFilters(prev => ({ 
      ...prev, 
      status: value ? value as OrderStatus : undefined, 
      page: 1 
    }));
  };

  const handlePaymentStatusChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    const value = event.target.value;
    setFilters(prev => ({ 
      ...prev, 
      payment_status: value ? value as PaymentStatus : undefined, 
      page: 1 
    }));
  };

  const handleFulfillmentStatusChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    const value = event.target.value;
    setFilters(prev => ({ 
      ...prev, 
      fulfillment_status: value ? value as FulfillmentStatus : undefined, 
      page: 1 
    }));
  };

  const handlePageChange = (newPage: number) => {
    setFilters(prev => ({ ...prev, page: newPage }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleViewOrder = (id: number) => {
    navigate(`/orders/${id}`);
  };

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

  if (error) {
    return (
      <div className="p-6">
        <Alert 
          variant="error" 
          title="Error Loading Orders"
          message="Unable to load orders. Please try again later." 
        />
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* TODO: Implement alert display when needed */}

      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Orders</h1>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage customer orders and fulfillment
          </p>
        </div>
        <Button
          variant="primary"
          onClick={() => navigate('/orders/new')}
        >
          + Create Order
        </Button>
      </div>

      {/* Filters */}
      <div className="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-white p-4 shadow dark:bg-boxdark md:grid-cols-4">
        {/* Search */}
        <div>
          <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Search
          </label>
          <input
            type="text"
            value={filters.search}
            onChange={handleSearchChange}
            placeholder="Order number, customer..."
            className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-2 px-3 text-dark dark:text-white focus:border-primary focus:outline-none"
          />
        </div>

        {/* Status Filter */}
        <div>
          <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Order Status
          </label>
          <select
            value={filters.status || ''}
            onChange={handleStatusChange}
            className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-2 px-3 text-dark dark:text-white focus:border-primary focus:outline-none"
          >
            <option value="">All Statuses</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
            <option value="refunded">Refunded</option>
          </select>
        </div>

        {/* Payment Status Filter */}
        <div>
          <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Payment Status
          </label>
          <select
            value={filters.payment_status || ''}
            onChange={handlePaymentStatusChange}
            className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-2 px-3 text-dark dark:text-white focus:border-primary focus:outline-none"
          >
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="paid">Paid</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
            <option value="partially_refunded">Partially Refunded</option>
          </select>
        </div>

        {/* Fulfillment Status Filter */}
        <div>
          <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Fulfillment
          </label>
          <select
            value={filters.fulfillment_status || ''}
            onChange={handleFulfillmentStatusChange}
            className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-2 px-3 text-dark dark:text-white focus:border-primary focus:outline-none"
          >
            <option value="">All</option>
            <option value="unfulfilled">Unfulfilled</option>
            <option value="partial">Partial</option>
            <option value="fulfilled">Fulfilled</option>
          </select>
        </div>
      </div>

      {/* Results Count */}
      {meta && (
        <div className="mb-4 text-sm text-gray-600 dark:text-gray-400">
          Showing {((meta.current_page - 1) * meta.per_page) + 1} to {Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total} orders
        </div>
      )}

      {/* Orders Table */}
      <div className="overflow-hidden rounded-lg bg-white shadow dark:bg-boxdark">
        {isLoading ? (
          <div className="p-12 text-center text-gray-500 dark:text-gray-400">
            Loading orders...
          </div>
        ) : orders.length === 0 ? (
          <div className="p-12 text-center">
            <p className="text-gray-500 dark:text-gray-400">No orders found</p>
            <Button
              variant="primary"
              onClick={() => navigate('/orders/new')}
              className="mt-4"
            >
              Create First Order
            </Button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-stroke dark:border-strokedark bg-gray-50 dark:bg-meta-4">
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Order
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Customer
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Total
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Status
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Payment
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Fulfillment
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Date
                  </th>
                  <th className="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {orders.map((order) => (
                  <tr
                    key={order.id}
                    className="border-b border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-meta-4 cursor-pointer"
                    onClick={() => handleViewOrder(order.id)}
                  >
                    {/* Order Number */}
                    <td className="px-6 py-4">
                      <div>
                        <p className="font-medium text-gray-900 dark:text-white">
                          {order.order_number}
                        </p>
                        {order.items && (
                          <p className="text-sm text-gray-500 dark:text-gray-400">
                            {order.items.length} item{order.items.length !== 1 ? 's' : ''}
                          </p>
                        )}
                      </div>
                    </td>

                    {/* Customer */}
                    <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                      {order.customer ? (
                        <div>
                          <p className="font-medium">
                            {order.customer.first_name} {order.customer.last_name}
                          </p>
                          <p className="text-sm text-gray-500 dark:text-gray-400">
                            {order.customer.email}
                          </p>
                        </div>
                      ) : (
                        <span className="text-gray-500 dark:text-gray-400">Guest</span>
                      )}
                    </td>

                    {/* Total */}
                    <td className="px-6 py-4">
                      <p className="font-semibold text-gray-900 dark:text-white">
                        {formatPrice(order.total, order.currency || getStoreCurrency())}
                      </p>
                    </td>

                    {/* Status */}
                    <td className="px-6 py-4">
                      <Badge color={getStatusBadgeColor(order.status)} size="sm">
                        {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                      </Badge>
                    </td>

                    {/* Payment */}
                    <td className="px-6 py-4">
                      <Badge color={getPaymentBadgeColor(order.payment_status)} size="sm">
                        {order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1).replace('_', ' ')}
                      </Badge>
                    </td>

                    {/* Fulfillment */}
                    <td className="px-6 py-4">
                      <Badge color={getFulfillmentBadgeColor(order.fulfillment_status)} size="sm">
                        {order.fulfillment_status.charAt(0).toUpperCase() + order.fulfillment_status.slice(1)}
                      </Badge>
                    </td>

                    {/* Date */}
                    <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                      <p className="text-sm">
                        {new Date(order.placed_at || order.created_at).toLocaleDateString('en-US', {
                          month: 'short',
                          day: 'numeric',
                          year: 'numeric'
                        })}
                      </p>
                    </td>

                    {/* Actions */}
                    <td className="px-6 py-4 text-right">
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => handleViewOrder(order.id)}
                      >
                        View
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="mt-6 flex items-center justify-between">
          <div className="text-sm text-gray-600 dark:text-gray-400">
            Page {meta.current_page} of {meta.last_page}
          </div>
          <div className="flex gap-2">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handlePageChange(meta.current_page - 1)}
              disabled={meta.current_page === 1}
            >
              Previous
            </Button>
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handlePageChange(meta.current_page + 1)}
              disabled={meta.current_page === meta.last_page}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};

export default OrdersPage;
