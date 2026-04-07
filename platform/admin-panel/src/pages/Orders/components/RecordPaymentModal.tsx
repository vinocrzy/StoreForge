import { useState } from 'react';
import { useRecordPaymentMutation } from '../../../services/orders';
import Button from '../../../components/ui/button/Button';
import { formatPrice } from '../../../utils/currency';

interface RecordPaymentModalProps {
  orderId: number;
  orderTotal: number;
  currency: string;
  onClose: () => void;
  onSuccess: () => void;
}

const RecordPaymentModal: React.FC<RecordPaymentModalProps> = ({
  orderId,
  orderTotal,
  currency,
  onClose,
  onSuccess,
}) => {
  const [formData, setFormData] = useState({
    payment_method: 'bank_transfer',
    amount: orderTotal.toString(),
    transaction_id: '',
    payment_notes: '',
  });

  const [recordPayment, { isLoading }] = useRecordPaymentMutation();
  const [error, setError] = useState<string | null>(null);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const amount = parseFloat(formData.amount);
    if (isNaN(amount) || amount <= 0) {
      setError('Please enter a valid payment amount');
      return;
    }

    if (amount > orderTotal) {
      setError('Payment amount cannot exceed order total');
      return;
    }

    try {
      await recordPayment({
        id: orderId,
        data: {
          payment_method: formData.payment_method,
          amount,
          transaction_id: formData.transaction_id || undefined,
          payment_notes: formData.payment_notes || undefined,
        },
      }).unwrap();
      onSuccess();
    } catch (err) {
      setError('Failed to record payment. Please try again.');
    }
  };

  return (
    <div className="fixed inset-0 z-999999 flex items-center justify-center bg-black bg-opacity-50">
      <div className="w-full max-w-md rounded-lg bg-white dark:bg-boxdark p-6 shadow-lg">
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-xl font-bold text-gray-900 dark:text-white">Record Payment</h2>
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

        <div className="mb-4 rounded-lg bg-gray-50 dark:bg-meta-4 p-3">
          <div className="flex justify-between text-sm">
            <span className="text-gray-600 dark:text-gray-400">Order Total:</span>
            <span className="font-semibold text-gray-900 dark:text-white">
              {formatPrice(orderTotal, currency)}
            </span>
          </div>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="space-y-4">
            {/* Payment Method */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Payment Method <span className="text-error-500">*</span>
              </label>
              <select
                name="payment_method"
                value={formData.payment_method}
                onChange={handleChange}
                required
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
              >
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="manual">Manual</option>
                <option value="stripe">Stripe</option>
                <option value="paypal">PayPal</option>
                <option value="razorpay">Razorpay</option>
              </select>
            </div>

            {/* Amount */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Amount <span className="text-error-500">*</span>
              </label>
              <input
                type="number"
                name="amount"
                value={formData.amount}
                onChange={handleChange}
                step="0.01"
                min="0"
                max={orderTotal}
                required
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                placeholder="0.00"
              />
            </div>

            {/* Transaction ID */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Transaction/Reference ID
              </label>
              <input
                type="text"
                name="transaction_id"
                value={formData.transaction_id}
                onChange={handleChange}
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                placeholder="e.g., TXN-123456"
              />
            </div>

            {/* Payment Notes */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Payment Notes
              </label>
              <textarea
                name="payment_notes"
                value={formData.payment_notes}
                onChange={handleChange}
                rows={3}
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none resize-none"
                placeholder="Additional notes about this payment..."
              />
            </div>
          </div>

          <div className="mt-6 flex gap-3">
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
              variant="success"
              className="flex-1"
              disabled={isLoading}
            >
              {isLoading ? 'Recording...' : 'Record Payment'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default RecordPaymentModal;
