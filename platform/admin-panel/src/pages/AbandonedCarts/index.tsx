import { useState } from 'react';
import { useGetAbandonedCartsQuery } from '../../services/analytics';
import Alert from '../../components/ui/alert/Alert';

const fmtCurrency = (v: number) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v);

const timeAgo = (dateStr: string) => {
  const diff = Date.now() - new Date(dateStr).getTime();
  const h = Math.floor(diff / 3600000);
  if (h < 24) return `${h}h ago`;
  const d = Math.floor(h / 24);
  return `${d}d ago`;
};

export default function AbandonedCartsPage() {
  const { data, isLoading, error } = useGetAbandonedCartsQuery();
  const d = data?.data;
  const [search, setSearch] = useState('');

  const carts = (d?.carts ?? []).filter(
    (c) =>
      !search ||
      c.customer_name?.toLowerCase().includes(search.toLowerCase()) ||
      c.customer_email?.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Abandoned Carts</h1>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Recover lost sales via automated email reminders
        </p>
      </div>

      {error && <Alert variant="error" title="Error" message="Failed to load abandoned carts." />}
      {isLoading && <div className="py-12 text-center text-gray-500">Loading...</div>}

      {d && (
        <>
          {/* Stats */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {[
              { label: 'Abandoned Carts', value: d.total_abandoned },
              { label: 'Abandoned Value', value: fmtCurrency(d.total_value) },
              { label: 'Recovered', value: d.recovered_count },
              { label: 'Recovery Rate', value: `${d.recovery_rate.toFixed(1)}%` },
            ].map((s) => (
              <div
                key={s.label}
                className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
              >
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">{s.label}</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{s.value}</p>
              </div>
            ))}
          </div>

          {/* Search */}
          <div className="mb-4">
            <input
              type="text"
              placeholder="Search by customer name or email..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full max-w-sm rounded-lg border border-gray-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
            />
          </div>

          {/* Table */}
          <div className="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 dark:bg-gray-700/50">
                <tr>
                  {['Customer', 'Email', 'Items', 'Value', 'Abandoned', 'Emails Sent'].map((h) => (
                    <th key={h} className="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100 dark:divide-gray-700">
                {carts.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="px-4 py-8 text-center text-gray-400">
                      {search ? 'No results found.' : 'No abandoned carts.'}
                    </td>
                  </tr>
                ) : (
                  carts.map((c) => (
                    <tr key={c.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                      <td className="px-4 py-3 font-medium text-gray-900 dark:text-white">{c.customer_name || '—'}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{c.customer_email || '—'}</td>
                      <td className="px-4 py-3 text-gray-600 dark:text-gray-300">{c.item_count}</td>
                      <td className="px-4 py-3 font-semibold text-gray-900 dark:text-white">{fmtCurrency(c.total_value)}</td>
                      <td className="px-4 py-3 text-gray-500 dark:text-gray-400">{timeAgo(c.abandoned_at)}</td>
                      <td className="px-4 py-3">
                        <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                          c.recovery_email_count === 0
                            ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                            : c.recovery_email_count >= 2
                            ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                            : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                        }`}>
                          {c.recovery_email_count} / 2
                        </span>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </>
      )}
    </div>
  );
}
