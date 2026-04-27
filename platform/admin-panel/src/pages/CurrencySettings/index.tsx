import { useState } from 'react';
import { useGetCurrencySettingsQuery, useUpdateCurrencySettingsMutation } from '../../services/settingsExt';

const COMMON_CURRENCIES = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'INR', 'JPY', 'SGD', 'AED', 'MXN', 'BRL'];

const CurrencySettingsPage = () => {
  const { data, isLoading, error } = useGetCurrencySettingsQuery();
  const [updateSettings, { isLoading: isSaving }] = useUpdateCurrencySettingsMutation();
  const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [baseCurrency, setBaseCurrency] = useState('');
  const [rates, setRates] = useState<Record<string, string>>({});

  const settings = data?.data;

  const getBase = () => baseCurrency || settings?.base_currency || 'USD';
  const getRate = (code: string) =>
    rates[code] !== undefined ? rates[code] : (settings?.exchange_rates?.[code] ?? '').toString();

  const handleRateChange = (code: string, val: string) => {
    setRates((r) => ({ ...r, [code]: val }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const parsedRates: Record<string, number> = {};
    for (const [code, val] of Object.entries(rates)) {
      const n = parseFloat(val);
      if (!isNaN(n) && n > 0) parsedRates[code] = n;
    }
    // Merge with existing rates
    const existing = settings?.exchange_rates ?? {};
    const merged = { ...existing, ...parsedRates };

    try {
      await updateSettings({ base_currency: getBase(), exchange_rates: merged }).unwrap();
      setAlert({ type: 'success', message: 'Currency settings saved.' });
      setRates({});
    } catch {
      setAlert({ type: 'error', message: 'Failed to save currency settings.' });
    }
  };

  if (isLoading) return <div className="p-6 text-center text-gray-500">Loading currency settings…</div>;
  if (error) return <div className="p-6 text-red-500">Error loading settings.</div>;

  const displayCurrencies = COMMON_CURRENCIES.filter((c) => c !== getBase());

  return (
    <div className="mx-auto max-w-2xl p-6">
      <h1 className="mb-2 text-2xl font-bold text-dark dark:text-white">Currency Settings</h1>
      <p className="mb-6 text-sm text-gray-500">Configure your store's base currency and display exchange rates. Payments always process in the base currency.</p>

      {alert && (
        <div
          className={`mb-4 rounded-lg px-4 py-3 text-sm ${alert.type === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-900/20' : 'bg-red-50 text-red-700 dark:bg-red-900/20'}`}
        >
          {alert.message}
        </div>
      )}

      <div className="rounded-xl border border-stroke bg-white p-6 shadow-sm dark:border-strokedark dark:bg-boxdark">
        <form onSubmit={handleSubmit} className="space-y-6">
          {/* Base currency */}
          <div>
            <label className="mb-2 block text-sm font-medium text-dark dark:text-white">Base Currency</label>
            <select
              value={getBase()}
              onChange={(e) => setBaseCurrency(e.target.value)}
              className="w-full rounded-lg border border-stroke bg-white py-3 px-4 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
            >
              {COMMON_CURRENCIES.map((c) => (
                <option key={c} value={c}>{c}</option>
              ))}
            </select>
            <p className="mt-1 text-xs text-gray-500">All transactions are settled in this currency.</p>
          </div>

          {/* Exchange rates */}
          <div>
            <p className="mb-3 text-sm font-medium text-dark dark:text-white">Display Exchange Rates (1 {getBase()} =)</p>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
              {displayCurrencies.map((code) => (
                <div key={code}>
                  <label className="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{code}</label>
                  <input
                    type="number"
                    min={0.0001}
                    step={0.0001}
                    value={getRate(code)}
                    onChange={(e) => handleRateChange(code, e.target.value)}
                    placeholder="—"
                    className="w-full rounded-lg border border-stroke bg-white py-2 px-3 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
                  />
                </div>
              ))}
            </div>
            <p className="mt-2 text-xs text-gray-500">Leave blank to hide that currency from the storefront selector.</p>
          </div>

          <button
            type="submit"
            disabled={isSaving}
            className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-60"
          >
            {isSaving ? 'Saving…' : 'Save Currency Settings'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default CurrencySettingsPage;
