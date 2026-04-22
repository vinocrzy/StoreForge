import { useState } from 'react';
import { useNavigate } from 'react-router';
import type { CreateCouponData } from '../../services/coupons';
import Button from '../../components/ui/button/Button';

interface CouponFormProps {
  initialData?: Partial<CreateCouponData>;
  onSubmit: (data: CreateCouponData) => Promise<void>;
  isSubmitting: boolean;
  title: string;
}

const inputClass =
  'w-full rounded-lg border border-stroke bg-white px-4 py-2.5 text-sm text-dark placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white';

const CouponForm: React.FC<CouponFormProps> = ({ initialData, onSubmit, isSubmitting, title }) => {
  const navigate = useNavigate();

  const [code, setCode] = useState(initialData?.code ?? '');
  const [type, setType] = useState<'percentage' | 'fixed'>(initialData?.type ?? 'percentage');
  const [value, setValue] = useState<number>(initialData?.value ?? 0);
  const [status, setStatus] = useState<string>(initialData?.status ?? 'active');
  const [usageLimit, setUsageLimit] = useState<string>(initialData?.usage_limit != null ? String(initialData.usage_limit) : '');
  const [perCustomerLimit, setPerCustomerLimit] = useState<string>(initialData?.usage_limit_per_customer != null ? String(initialData.usage_limit_per_customer) : '');
  const [minPurchase, setMinPurchase] = useState<string>(initialData?.minimum_purchase_amount != null ? String(initialData.minimum_purchase_amount) : '');
  const [maxDiscount, setMaxDiscount] = useState<string>(initialData?.maximum_discount_amount != null ? String(initialData.maximum_discount_amount) : '');
  const [startsAt, setStartsAt] = useState(initialData?.starts_at ?? '');
  const [expiresAt, setExpiresAt] = useState(initialData?.expires_at ?? '');

  const [errors, setErrors] = useState<Record<string, string>>({});

  const validate = (): boolean => {
    const errs: Record<string, string> = {};
    if (!code.trim()) errs.code = 'Coupon code is required';
    if (value <= 0) errs.value = 'Value must be greater than 0';
    if (type === 'percentage' && value > 100) errs.value = 'Percentage cannot exceed 100';
    if (startsAt && expiresAt && new Date(expiresAt) <= new Date(startsAt)) {
      errs.expires_at = 'End date must be after start date';
    }
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!validate()) return;

    await onSubmit({
      code: code.toUpperCase().trim(),
      type,
      value,
      status,
      usage_limit: usageLimit ? Number(usageLimit) : null,
      usage_limit_per_customer: perCustomerLimit ? Number(perCustomerLimit) : null,
      minimum_purchase_amount: minPurchase ? Number(minPurchase) : null,
      maximum_discount_amount: maxDiscount ? Number(maxDiscount) : null,
      starts_at: startsAt || null,
      expires_at: expiresAt || null,
    });
  };

  return (
    <div className="p-6">
      <div className="mb-6">
        <Button variant="ghost" onClick={() => navigate('/coupons')} className="mb-3">
          ← Back to Coupons
        </Button>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{title}</h1>
      </div>

      <div className="rounded-xl border border-stroke bg-white p-6 shadow-sm dark:border-strokedark dark:bg-boxdark">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Code */}
          <div>
            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
              Coupon Code <span className="text-danger">*</span>
            </label>
            <input
              type="text"
              className={inputClass}
              value={code}
              onChange={(e) => setCode(e.target.value.toUpperCase())}
              placeholder="e.g. SUMMER20"
            />
            {errors.code && <p className="mt-1 text-sm text-danger">{errors.code}</p>}
          </div>

          {/* Type & Value */}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Discount Type <span className="text-danger">*</span>
              </label>
              <select className={inputClass} value={type} onChange={(e) => setType(e.target.value as 'percentage' | 'fixed')}>
                <option value="percentage">Percentage (%)</option>
                <option value="fixed">Fixed Amount ($)</option>
              </select>
            </div>
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                Value <span className="text-danger">*</span>
              </label>
              <input
                type="number"
                step="0.01"
                min="0"
                className={inputClass}
                value={value || ''}
                onChange={(e) => setValue(Number(e.target.value))}
                placeholder={type === 'percentage' ? 'e.g. 20' : 'e.g. 10.00'}
              />
              {errors.value && <p className="mt-1 text-sm text-danger">{errors.value}</p>}
            </div>
          </div>

          {/* Status */}
          <div>
            <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
            <label className="flex cursor-pointer items-center gap-3">
              <div className="relative">
                <input
                  type="checkbox"
                  className="sr-only"
                  checked={status === 'active'}
                  onChange={(e) => setStatus(e.target.checked ? 'active' : 'inactive')}
                />
                <div className={`h-6 w-11 rounded-full transition-colors ${status === 'active' ? 'bg-primary' : 'bg-gray-300 dark:bg-gray-600'}`} />
                <div className={`absolute top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform ${status === 'active' ? 'left-5' : 'left-0.5'}`} />
              </div>
              <span className="text-sm text-gray-700 dark:text-gray-300">{status === 'active' ? 'Active' : 'Inactive'}</span>
            </label>
          </div>

          {/* Usage Limits */}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Usage Limit</label>
              <input
                type="number"
                min="0"
                className={inputClass}
                value={usageLimit}
                onChange={(e) => setUsageLimit(e.target.value)}
                placeholder="Unlimited"
              />
              <p className="mt-1 text-xs text-gray-400">Leave empty for unlimited</p>
            </div>
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Per-Customer Limit</label>
              <input
                type="number"
                min="0"
                className={inputClass}
                value={perCustomerLimit}
                onChange={(e) => setPerCustomerLimit(e.target.value)}
                placeholder="Unlimited"
              />
              <p className="mt-1 text-xs text-gray-400">Leave empty for unlimited</p>
            </div>
          </div>

          {/* Minimum Purchase & Max Discount */}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Purchase Amount</label>
              <input
                type="number"
                step="0.01"
                min="0"
                className={inputClass}
                value={minPurchase}
                onChange={(e) => setMinPurchase(e.target.value)}
                placeholder="No minimum"
              />
            </div>
            {type === 'percentage' && (
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Max Discount Amount</label>
                <input
                  type="number"
                  step="0.01"
                  min="0"
                  className={inputClass}
                  value={maxDiscount}
                  onChange={(e) => setMaxDiscount(e.target.value)}
                  placeholder="No maximum"
                />
              </div>
            )}
          </div>

          {/* Dates */}
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
              <input
                type="datetime-local"
                className={inputClass}
                value={startsAt ? startsAt.slice(0, 16) : ''}
                onChange={(e) => setStartsAt(e.target.value ? new Date(e.target.value).toISOString() : '')}
              />
            </div>
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
              <input
                type="datetime-local"
                className={inputClass}
                value={expiresAt ? expiresAt.slice(0, 16) : ''}
                onChange={(e) => setExpiresAt(e.target.value ? new Date(e.target.value).toISOString() : '')}
              />
              {errors.expires_at && <p className="mt-1 text-sm text-danger">{errors.expires_at}</p>}
            </div>
          </div>

          {/* Actions */}
          <div className="flex gap-3 justify-end border-t border-stroke dark:border-strokedark pt-5">
            <Button variant="outline" type="button" onClick={() => navigate('/coupons')}>
              Cancel
            </Button>
            <Button variant="primary" type="submit" disabled={isSubmitting}>
              {isSubmitting ? 'Saving...' : 'Save Coupon'}
            </Button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CouponForm;
