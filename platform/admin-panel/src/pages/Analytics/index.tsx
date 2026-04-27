import { useState } from 'react';
import { useGetDashboardQuery, type AnalyticsPeriod } from '../../services/analytics';
import Alert from '../../components/ui/alert/Alert';

const PERIODS: { label: string; value: AnalyticsPeriod }[] = [
  { label: '7 Days', value: '7d' },
  { label: '30 Days', value: '30d' },
  { label: '90 Days', value: '90d' },
];

const trendArrow = (trend: 'up' | 'down' | 'flat') =>
  trend === 'up' ? '↑' : trend === 'down' ? '↓' : '→';
const trendColor = (trend: 'up' | 'down' | 'flat') =>
  trend === 'up'
    ? 'text-green-600 dark:text-green-400'
    : trend === 'down'
    ? 'text-red-500 dark:text-red-400'
    : 'text-gray-500';

const fmtCurrency = (v: number) =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(v);
const fmtNum = (v: number) => new Intl.NumberFormat('en-US').format(v);

const STATUS_COLORS: Record<string, string> = {
  pending: 'bg-yellow-400',
  processing: 'bg-blue-400',
  shipped: 'bg-indigo-400',
  delivered: 'bg-green-400',
  cancelled: 'bg-red-400',
  refunded: 'bg-gray-400',
};

export default function AnalyticsDashboard() {
  const [period, setPeriod] = useState<AnalyticsPeriod>('30d');
  const { data, isLoading, error } = useGetDashboardQuery({ period });
  const d = data?.data;

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Analytics</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">Store performance overview</p>
        </div>
        <div className="flex gap-2">
          {PERIODS.map((p) => (
            <button
              key={p.value}
              onClick={() => setPeriod(p.value)}
              className={`px-4 py-2 text-sm font-medium rounded-lg transition-colors ${
                period === p.value
                  ? 'bg-brand-600 text-white'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'
              }`}
            >
              {p.label}
            </button>
          ))}
        </div>
      </div>

      {error && (
        <Alert variant="error" title="Error" message="Failed to load analytics data. Ensure the backend is running." />
      )}

      {isLoading && (
        <div className="text-center py-12 text-gray-500 dark:text-gray-400">Loading analytics...</div>
      )}

      {d && (
        <>
          {/* KPI Cards */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          {([
            { label: 'Revenue', display: fmtCurrency(d.kpis.revenue.value), change_pct: d.kpis.revenue.change_pct, trend: d.kpis.revenue.trend },
            { label: 'Orders', display: fmtNum(d.kpis.orders.value), change_pct: d.kpis.orders.change_pct, trend: d.kpis.orders.trend },
            { label: 'Avg Order Value', display: fmtCurrency(d.kpis.aov.value), change_pct: d.kpis.aov.change_pct, trend: d.kpis.aov.trend },
            { label: 'New Customers', display: fmtNum(d.kpis.new_customers.value), change_pct: d.kpis.new_customers.change_pct, trend: d.kpis.new_customers.trend },
          ] as const).map((kpi) => (
              <div
                key={kpi.label}
                className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
              >
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-1">{kpi.label}</p>
                <p className="text-2xl font-bold text-gray-900 dark:text-white">{kpi.display}</p>
                <p className={`text-xs mt-1 font-medium ${trendColor(kpi.trend)}`}>
                  {trendArrow(kpi.trend)} {Math.abs(kpi.change_pct).toFixed(1)}% vs prev period
                </p>
              </div>
            ))}
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            {/* Revenue Chart — simple bar chart */}
            <div className="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
              <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">Revenue Trend</h2>
              {d.revenue_chart.length === 0 ? (
                <p className="text-sm text-gray-400">No data for this period.</p>
              ) : (
                <div className="flex items-end gap-1 h-32">
                  {d.revenue_chart.map((pt) => {
                    const max = Math.max(...d.revenue_chart.map((x) => x.revenue), 1);
                    const pct = (pt.revenue / max) * 100;
                    return (
                      <div
                        key={pt.date}
                        className="flex-1 group relative"
                        title={`${pt.date}: ${fmtCurrency(pt.revenue)}`}
                      >
                        <div
                          className="bg-brand-500 rounded-t hover:bg-brand-600 transition-colors"
                          style={{ height: `${Math.max(pct, 2)}%` }}
                        />
                        <div className="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block bg-gray-800 text-white text-xs rounded px-2 py-1 whitespace-nowrap z-10">
                          {pt.date}: {fmtCurrency(pt.revenue)}
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </div>

            {/* Order Status Breakdown */}
            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
              <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">Order Status</h2>
              <div className="space-y-3">
                {d.order_status.map((s) => (
                  <div key={s.status}>
                    <div className="flex justify-between text-sm mb-1">
                      <span className="capitalize text-gray-700 dark:text-gray-300">{s.status}</span>
                      <span className="text-gray-500 dark:text-gray-400">{s.count} ({s.percentage.toFixed(0)}%)</span>
                    </div>
                    <div className="h-2 rounded-full bg-gray-100 dark:bg-gray-700">
                      <div
                        className={`h-2 rounded-full ${STATUS_COLORS[s.status] ?? 'bg-gray-400'}`}
                        style={{ width: `${s.percentage}%` }}
                      />
                    </div>
                  </div>
                ))}
                {d.order_status.length === 0 && (
                  <p className="text-sm text-gray-400">No orders in this period.</p>
                )}
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {/* Top Products */}
            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
              <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">Top Products</h2>
              {d.top_products.length === 0 ? (
                <p className="text-sm text-gray-400">No sales in this period.</p>
              ) : (
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b border-gray-100 dark:border-gray-700">
                      <th className="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">#</th>
                      <th className="pb-2 text-left font-medium text-gray-500 dark:text-gray-400">Product</th>
                      <th className="pb-2 text-right font-medium text-gray-500 dark:text-gray-400">Units</th>
                      <th className="pb-2 text-right font-medium text-gray-500 dark:text-gray-400">Revenue</th>
                    </tr>
                  </thead>
                  <tbody>
                    {d.top_products.map((p) => (
                      <tr key={p.id} className="border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                        <td className="py-2 text-gray-400">{p.rank}</td>
                        <td className="py-2 text-gray-900 dark:text-white truncate max-w-[150px]">{p.name}</td>
                        <td className="py-2 text-right text-gray-600 dark:text-gray-400">{fmtNum(p.units_sold)}</td>
                        <td className="py-2 text-right font-semibold text-gray-900 dark:text-white">{fmtCurrency(p.revenue)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>

            {/* Recent Orders */}
            <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
              <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">Recent Orders</h2>
              {d.recent_orders.length === 0 ? (
                <p className="text-sm text-gray-400">No recent orders.</p>
              ) : (
                <div className="space-y-2">
                  {d.recent_orders.map((o) => (
                    <div key={o.id} className="flex items-center justify-between py-2 border-b border-gray-50 dark:border-gray-700/50 last:border-0">
                      <div>
                        <p className="text-sm font-medium text-gray-900 dark:text-white">{o.order_number}</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400">{o.customer_name}</p>
                      </div>
                      <div className="text-right">
                        <p className="text-sm font-semibold text-gray-900 dark:text-white">{fmtCurrency(o.total)}</p>
                        <span className={`text-xs capitalize px-2 py-0.5 rounded-full ${STATUS_COLORS[o.status] ?? 'bg-gray-300'} text-white`}>
                          {o.status}
                        </span>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  );
}
