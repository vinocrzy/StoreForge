import { useState } from 'react';
import { useGetLoyaltyConfigQuery, useUpdateLoyaltyConfigMutation } from '../../services/settingsExt';
import type { LoyaltyConfig } from '../../services/settingsExt';

const LoyaltySettingsPage = () => {
  const { data, isLoading, error } = useGetLoyaltyConfigQuery();
  const [updateConfig, { isLoading: isSaving }] = useUpdateLoyaltyConfigMutation();
  const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);
  const [form, setForm] = useState<Partial<LoyaltyConfig>>({});

  const config = data?.data;
  const getValue = <K extends keyof LoyaltyConfig>(key: K): LoyaltyConfig[K] =>
    (key in form ? form[key] : config?.[key]) as LoyaltyConfig[K];

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await updateConfig(form).unwrap();
      setAlert({ type: 'success', message: 'Loyalty program settings saved.' });
      setForm({});
    } catch {
      setAlert({ type: 'error', message: 'Failed to save settings.' });
    }
  };

  if (isLoading) return <div className="p-6 text-center text-gray-500">Loading loyalty settings…</div>;
  if (error) return <div className="p-6 text-red-500">Error loading settings.</div>;

  return (
    <div className="mx-auto max-w-2xl p-6">
      <h1 className="mb-6 text-2xl font-bold text-dark dark:text-white">Loyalty & Rewards Program</h1>

      {alert && (
        <div
          className={`mb-4 rounded-lg px-4 py-3 text-sm ${alert.type === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-900/20' : 'bg-red-50 text-red-700 dark:bg-red-900/20'}`}
        >
          {alert.message}
        </div>
      )}

      <div className="rounded-xl border border-stroke bg-white p-6 shadow-sm dark:border-strokedark dark:bg-boxdark">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Enable toggle */}
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-dark dark:text-white">Enable Loyalty Program</p>
              <p className="text-sm text-gray-500">Customers earn and redeem points on purchases.</p>
            </div>
            <label className="relative inline-flex cursor-pointer items-center">
              <input
                type="checkbox"
                className="peer sr-only"
                checked={getValue('enabled') ?? false}
                onChange={(e) => setForm((f) => ({ ...f, enabled: e.target.checked }))}
              />
              <div className="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-full dark:bg-gray-700" />
            </label>
          </div>

          {/* Program Name */}
          <div>
            <label className="mb-2 block text-sm font-medium text-dark dark:text-white">Program Name</label>
            <input
              type="text"
              value={getValue('program_name') ?? ''}
              onChange={(e) => setForm((f) => ({ ...f, program_name: e.target.value }))}
              placeholder="e.g. Honey Rewards"
              className="w-full rounded-lg border border-stroke bg-white py-3 px-4 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
            />
          </div>

          <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
            {/* Points per dollar */}
            <div>
              <label className="mb-2 block text-sm font-medium text-dark dark:text-white">Points Earned per $1 Spent</label>
              <input
                type="number"
                min={1}
                step={1}
                value={getValue('points_per_dollar') ?? 10}
                onChange={(e) => setForm((f) => ({ ...f, points_per_dollar: parseInt(e.target.value) }))}
                className="w-full rounded-lg border border-stroke bg-white py-3 px-4 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
              />
            </div>

            {/* Redemption threshold */}
            <div>
              <label className="mb-2 block text-sm font-medium text-dark dark:text-white">Minimum Points to Redeem</label>
              <input
                type="number"
                min={1}
                step={1}
                value={getValue('redemption_threshold') ?? 100}
                onChange={(e) => setForm((f) => ({ ...f, redemption_threshold: parseInt(e.target.value) }))}
                className="w-full rounded-lg border border-stroke bg-white py-3 px-4 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
              />
            </div>
          </div>

          {/* Points to dollar rate */}
          <div>
            <label className="mb-2 block text-sm font-medium text-dark dark:text-white">Point Value ($ per point)</label>
            <input
              type="number"
              min={0.001}
              step={0.001}
              value={getValue('points_to_dollar_rate') ?? 0.01}
              onChange={(e) => setForm((f) => ({ ...f, points_to_dollar_rate: parseFloat(e.target.value) }))}
              className="w-full rounded-lg border border-stroke bg-white py-3 px-4 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
            />
            <p className="mt-1 text-xs text-gray-500">
              Example: 0.01 means 100 points = $1.00 discount.
            </p>
          </div>

          <button
            type="submit"
            disabled={isSaving}
            className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-60"
          >
            {isSaving ? 'Saving…' : 'Save Settings'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default LoyaltySettingsPage;
