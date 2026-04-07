import { useState } from 'react';
import { useUpdateOrderStatusMutation } from '../../../services/orders';
import Button from '../../../components/ui/button/Button';
import type { OrderStatus } from '../../../types/order';

interface UpdateOrderStatusModalProps {
  orderId: number;
  currentStatus: OrderStatus;
  onClose: () => void;
  onSuccess: () => void;
}

const UpdateOrderStatusModal: React.FC<UpdateOrderStatusModalProps> = ({
  orderId,
  currentStatus,
  onClose,
  onSuccess,
}) => {
  const [status, setStatus] = useState<OrderStatus>(currentStatus);
  const [updateOrderStatus, { isLoading }] = useUpdateOrderStatusMutation();
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (status === currentStatus) {
      setError('Please select a different status');
      return;
    }

    try {
      await updateOrderStatus({ id: orderId, data: { status } }).unwrap();
      onSuccess();
    } catch (err) {
      setError('Failed to update order status. Please try again.');
    }
  };

  return (
    <div className="fixed inset-0 z-999999 flex items-center justify-center bg-black bg-opacity-50">
      <div className="w-full max-w-md rounded-lg bg-white dark:bg-boxdark p-6 shadow-lg">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-xl font-bold text-gray-900 dark:text-white">Update Order Status</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          >
            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {error && (
          <div className="mb-4 rounded-lg bg-error-50 dark:bg-error-500/15 border border-error-500 p-3 text-sm text-error-500">
            {error}
          </div>
        )}

        <form onSubmit={handleSubmit}>
          <div className="mb-6">
            <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
              Order Status
            </label>
            <select
              value={status}
              onChange={(e) => setStatus(e.target.value as OrderStatus)}
              className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
            >
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="processing">Processing</option>
              <option value="shipped">Shipped</option>
              <option value="delivered">Delivered</option>
              <option value="cancelled">Cancelled</option>
              <option value="refunded">Refunded</option>
            </select>
            <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
              Current status: <span className="font-medium">{currentStatus}</span>
            </p>
          </div>

          <div className="flex gap-3">
            <Button
              type="button"
              variant="secondary"
              onClick={onClose}
              className="flex-1"
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              className="flex-1"
              disabled={isLoading}
            >
              {isLoading ? 'Updating...' : 'Update Status'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default UpdateOrderStatusModal;
