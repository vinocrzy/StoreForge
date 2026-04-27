import { useState, useEffect } from 'react';
import {
  useGetTaxSettingsQuery,
  useUpdateTaxSettingsMutation,
  type TaxSettings,
} from '../../services/shipping';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';

const EMPTY: TaxSettings = {
  tax_enabled: false,
  tax_rate: 0,
  tax_display: 'exclusive',
  tax_label: 'Tax',
  category_tax_rates: {},
};

export default function TaxSettingsPage() {
  const { data, isLoading } = useGetTaxSettingsQuery();
  const [updateTax, { isLoading: isSaving }] = useUpdateTaxSettingsMutation();
  const [form, setForm] = useState<TaxSettings>(EMPTY);
  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);

  useEffect(() => {
    if (data?.data) setForm(data.data);
  }, [data]);

  const handleSave = async () => {
    try {
      await updateTax(form).unwrap();
      setAlert({ variant: 'success', title: 'Saved', message: 'Tax settings updated successfully.' });
    } catch {
      setAlert({ variant: 'error', title: 'Error', message: 'Failed to save tax settings.' });
    }
    setTimeout(() => setAlert(null), 4000);
  };

  const inputCls = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white disabled:opacity-50';

  if (isLoading) return <div className="p-6 text-center text-gray-500">Loading...</div>;

  return (
    <div className="p-6 max-w-2xl">
      {alert && <div className="mb-4"><Alert variant={alert.variant} title={alert.title} message={alert.message} /></div>}

      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Tax Settings</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Configure automatic tax calculation for your store
        </p>
      </div>

      <div className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800 space-y-6">
        {/* Enable/Disable */}
        <div className="flex items-center justify-between">
          <div>
            <p className="font-medium text-gray-900 dark:text-white">Enable Tax Calculation</p>
            <p className="text-sm text-gray-500 dark:text-gray-400">Automatically calculate tax on all orders</p>
          </div>
          <button
            onClick={() => setForm({ ...form, tax_enabled: !form.tax_enabled })}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
              form.tax_enabled ? 'bg-brand-600' : 'bg-gray-200 dark:bg-gray-700'
            }`}
          >
            <span className={`inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform ${
              form.tax_enabled ? 'translate-x-6' : 'translate-x-1'
            }`} />
          </button>
        </div>

        <hr className="border-gray-100 dark:border-gray-700" />

        {/* Tax label */}
        <div>
          <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tax Label</label>
          <input
            className={inputCls}
            value={form.tax_label}
            onChange={(e) => setForm({ ...form, tax_label: e.target.value })}
            disabled={!form.tax_enabled}
            placeholder="e.g. GST, VAT, Sales Tax"
          />
          <p className="text-xs text-gray-400 mt-1">Shown to customers at checkout</p>
        </div>

        {/* Default tax rate */}
        <div>
          <label className="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Default Tax Rate (%)</label>
          <input
            type="number"
            min="0"
            max="100"
            step="0.1"
            className={inputCls}
            value={form.tax_rate}
            onChange={(e) => setForm({ ...form, tax_rate: parseFloat(e.target.value) || 0 })}
            disabled={!form.tax_enabled}
          />
        </div>

        {/* Tax display */}
        <div>
          <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tax Display Mode</label>
          <div className="flex gap-4">
            {(['exclusive', 'inclusive'] as const).map((mode) => (
              <label key={mode} className={`flex items-center gap-2 cursor-pointer ${!form.tax_enabled ? 'opacity-50' : ''}`}>
                <input
                  type="radio"
                  name="taxDisplay"
                  value={mode}
                  checked={form.tax_display === mode}
                  onChange={() => setForm({ ...form, tax_display: mode })}
                  disabled={!form.tax_enabled}
                  className="w-4 h-4 accent-brand-600"
                />
                <div>
                  <p className="text-sm font-medium text-gray-900 dark:text-white capitalize">{mode}</p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    {mode === 'exclusive' ? 'Tax added on top of price' : 'Tax included in displayed price'}
                  </p>
                </div>
              </label>
            ))}
          </div>
        </div>

        <div className="flex justify-end pt-2">
          <Button variant="primary" onClick={handleSave} disabled={isSaving}>
            Save Tax Settings
          </Button>
        </div>
      </div>
    </div>
  );
}
