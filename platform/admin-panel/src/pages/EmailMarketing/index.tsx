import { useState } from 'react';
import { useGetEmailMarketingSettingsQuery, useUpdateEmailMarketingSettingsMutation } from '../../services/settingsExt';

const EmailMarketingPage = () => {
  const { data, isLoading, error, refetch } = useGetEmailMarketingSettingsQuery();
  const [updateSettings, { isLoading: isSaving }] = useUpdateEmailMarketingSettingsMutation();
  const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const [enabled, setEnabled] = useState<boolean | null>(null);
  const [apiKey, setApiKey] = useState('');
  const [listId, setListId] = useState('');

  const settings = data?.data;

  const isEnabled = enabled !== null ? enabled : (settings?.enabled ?? false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const payload: { enabled?: boolean; mailchimp_api_key?: string; mailchimp_list_id?: string } = {
      enabled: isEnabled,
    };
    if (apiKey.trim()) payload.mailchimp_api_key = apiKey;
    if (listId.trim()) payload.mailchimp_list_id = listId;

    try {
      await updateSettings(payload).unwrap();
      setAlert({ type: 'success', message: 'Email marketing settings saved.' });
      setApiKey('');
      setListId('');
      refetch();
    } catch {
      setAlert({ type: 'error', message: 'Failed to save settings.' });
    }
  };

  if (isLoading) return <div className="p-6 text-center text-gray-500">Loading email marketing settings…</div>;
  if (error) return <div className="p-6 text-red-500">Error loading settings.</div>;

  return (
    <div className="mx-auto max-w-2xl p-6">
      <h1 className="mb-2 text-2xl font-bold text-dark dark:text-white">Email Marketing</h1>
      <p className="mb-6 text-sm text-gray-500">Configure newsletter subscription and Mailchimp integration for your storefront.</p>

      {alert && (
        <div
          className={`mb-4 rounded-lg px-4 py-3 text-sm ${alert.type === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-900/20' : 'bg-red-50 text-red-700 dark:bg-red-900/20'}`}
        >
          {alert.message}
        </div>
      )}

      {/* Subscriber count card */}
      {settings && (
        <div className="mb-6 flex items-center gap-4 rounded-xl border border-stroke bg-white p-4 shadow-sm dark:border-strokedark dark:bg-boxdark">
          <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-2xl">
            ✉️
          </div>
          <div>
            <p className="text-sm text-gray-500">Total Subscribers</p>
            <p className="text-2xl font-bold text-dark dark:text-white">{settings.subscriber_count.toLocaleString()}</p>
          </div>
          {settings.api_key_set && (
            <span className="ml-auto rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
              Mailchimp Connected
            </span>
          )}
        </div>
      )}

      <div className="rounded-xl border border-stroke bg-white p-6 shadow-sm dark:border-strokedark dark:bg-boxdark">
        <form onSubmit={handleSubmit} className="space-y-5">
          {/* Enable toggle */}
          <div className="flex items-center justify-between">
            <div>
              <p className="font-medium text-dark dark:text-white">Enable Newsletter Subscription</p>
              <p className="text-sm text-gray-500">Show newsletter signup form on the storefront.</p>
            </div>
            <label className="relative inline-flex cursor-pointer items-center">
              <input
                type="checkbox"
                className="peer sr-only"
                checked={isEnabled}
                onChange={(e) => setEnabled(e.target.checked)}
              />
              <div className="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-primary peer-checked:after:translate-x-full dark:bg-gray-700" />
            </label>
          </div>

          {/* Mailchimp API Key */}
          <div>
            <label className="mb-2 block text-sm font-medium text-dark dark:text-white">
              Mailchimp API Key
              {settings?.api_key_set && (
                <span className="ml-2 text-xs font-normal text-green-600">(Key set — enter new key to replace)</span>
              )}
            </label>
            <input
              type="password"
              value={apiKey}
              onChange={(e) => setApiKey(e.target.value)}
              placeholder={settings?.api_key_set ? '••••••••••••••••' : 'paste-your-mailchimp-api-key'}
              autoComplete="new-password"
              className="w-full rounded-lg border border-stroke bg-white py-3 px-4 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
            />
          </div>

          {/* Mailchimp Audience/List ID */}
          <div>
            <label className="mb-2 block text-sm font-medium text-dark dark:text-white">Mailchimp Audience ID</label>
            <input
              type="text"
              value={listId || settings?.list_id || ''}
              onChange={(e) => setListId(e.target.value)}
              placeholder="e.g. abc123def4"
              className="w-full rounded-lg border border-stroke bg-white py-3 px-4 text-sm text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
            />
            <p className="mt-1 text-xs text-gray-500">
              Found in your Mailchimp account under Audience → Settings → Audience name and defaults.
            </p>
          </div>

          <button
            type="submit"
            disabled={isSaving}
            className="rounded-lg bg-primary px-6 py-3 text-sm font-medium text-white hover:bg-opacity-90 disabled:opacity-60"
          >
            {isSaving ? 'Saving…' : 'Save Email Marketing Settings'}
          </button>
        </form>
      </div>
    </div>
  );
};

export default EmailMarketingPage;
