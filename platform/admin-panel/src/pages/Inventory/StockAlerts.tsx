import { useState } from 'react';
import { useGetStockAlertsQuery, useResolveStockAlertMutation } from '../../services/inventory';
import Badge from '../../components/ui/badge/Badge';
import Button from '../../components/ui/button/Button';
import type { StockAlertFilters } from '../../types/inventory';

const alertTypeConfig: Record<string, { color: 'warning' | 'error'; label: string }> = {
  low_stock: { color: 'warning', label: 'Low Stock' },
  out_of_stock: { color: 'error', label: 'Out of Stock' },
};

const statusConfig: Record<string, { color: 'success' | 'error'; label: string }> = {
  active: { color: 'error', label: 'Active' },
  resolved: { color: 'success', label: 'Resolved' },
};

export default function StockAlertsPage() {
  const [filters, setFilters] = useState<StockAlertFilters>({ status: 'active', page: 1, per_page: 20 });
  const [resolvingId, setResolvingId] = useState<number | null>(null);

  const { data, isLoading, isFetching, isError } = useGetStockAlertsQuery(filters);
  const [resolveAlert] = useResolveStockAlertMutation();

  const handleResolve = async (id: number) => {
    setResolvingId(id);
    try {
      await resolveAlert(id).unwrap();
    } finally {
      setResolvingId(null);
    }
  };

  const handleStatusFilter = (status: string | undefined) => {
    setFilters((prev) => ({ ...prev, status: status as StockAlertFilters['status'], page: 1 }));
  };

  const handleTypeFilter = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const val = e.target.value;
    setFilters((prev) => ({
      ...prev,
      alert_type: val ? (val as StockAlertFilters['alert_type']) : undefined,
      page: 1,
    }));
  };

  const meta = data?.meta;

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Stock Alerts</h1>
          <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Monitor low stock and out-of-stock alerts across all warehouses
          </p>
        </div>

        {meta && (
          <div className="flex items-center gap-2">
            <span className="rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
              {data?.data?.filter((a) => a.status === 'active').length ?? 0} Active
            </span>
          </div>
        )}
      </div>

      {/* Filters */}
      <div className="mb-4 flex flex-wrap items-center gap-3">
        {/* Status filter tabs */}
        <div className="flex gap-1 rounded-lg border border-stroke bg-white p-1 dark:border-strokedark dark:bg-boxdark">
          {[
            { label: 'Active', value: 'active' },
            { label: 'Resolved', value: 'resolved' },
            { label: 'All', value: undefined },
          ].map(({ label, value }) => (
            <button
              key={label}
              onClick={() => handleStatusFilter(value)}
              className={`rounded-md px-4 py-1.5 text-sm font-medium transition-colors ${
                filters.status === value
                  ? 'bg-primary text-white'
                  : 'text-body hover:bg-gray-100 dark:hover:bg-gray-700'
              }`}
            >
              {label}
            </button>
          ))}
        </div>

        {/* Alert type filter */}
        <select
          value={filters.alert_type ?? ''}
          onChange={handleTypeFilter}
          className="rounded-lg border border-stroke bg-white px-4 py-2 text-sm text-body dark:border-strokedark dark:bg-boxdark dark:text-white"
        >
          <option value="">All Types</option>
          <option value="low_stock">Low Stock</option>
          <option value="out_of_stock">Out of Stock</option>
        </select>
      </div>

      {/* Table */}
      <div className="overflow-hidden rounded-lg border border-stroke bg-white shadow-sm dark:border-strokedark dark:bg-boxdark">
        {isLoading ? (
          <div className="p-12 text-center text-body">Loading alerts...</div>
        ) : isError ? (
          <div className="p-12 text-center text-danger">Failed to load stock alerts. Please try again.</div>
        ) : !data?.data?.length ? (
          <div className="p-12 text-center">
            <div className="mb-2 text-4xl">✅</div>
            <p className="font-medium text-gray-700 dark:text-gray-300">
              {filters.status === 'active' ? 'No active alerts!' : 'No alerts found.'}
            </p>
            <p className="mt-1 text-sm text-body">
              {filters.status === 'active' ? 'All stock levels are healthy.' : 'Try adjusting your filters.'}
            </p>
          </div>
        ) : (
          <div className={`transition-opacity ${isFetching ? 'opacity-60' : 'opacity-100'}`}>
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b border-stroke bg-gray-50 dark:border-strokedark dark:bg-gray-800/50">
                  <th className="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Product</th>
                  <th className="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Warehouse</th>
                  <th className="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Type</th>
                  <th className="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Threshold</th>
                  <th className="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Current Qty</th>
                  <th className="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Status</th>
                  <th className="px-6 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">Created</th>
                  <th className="px-6 py-3 text-right font-semibold text-gray-600 dark:text-gray-300">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-stroke dark:divide-strokedark">
                {data.data.map((alert) => {
                  const typeConf = alertTypeConfig[alert.alert_type] ?? { color: 'warning', label: alert.alert_type };
                  const statusConf = statusConfig[alert.status] ?? { color: 'danger', label: alert.status };
                  return (
                    <tr key={alert.id} className="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                      <td className="px-6 py-4">
                        <div className="font-medium text-gray-900 dark:text-white">
                          {alert.product?.name ?? `Product #${alert.product_id}`}
                        </div>
                        {alert.product?.sku && (
                          <div className="text-xs text-gray-500">SKU: {alert.product.sku}</div>
                        )}
                      </td>
                      <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                        {alert.warehouse?.name ?? (alert.warehouse_id ? `Warehouse #${alert.warehouse_id}` : '—')}
                      </td>
                      <td className="px-6 py-4">
                        <Badge color={typeConf.color as 'warning' | 'error'} size="sm">
                          {typeConf.label}
                        </Badge>
                      </td>
                      <td className="px-6 py-4 text-right font-mono text-gray-700 dark:text-gray-300">
                        {alert.threshold}
                      </td>
                      <td className="px-6 py-4 text-right font-mono">
                        <span
                          className={
                            alert.current_quantity === 0
                              ? 'font-semibold text-danger'
                              : alert.current_quantity <= alert.threshold
                              ? 'font-semibold text-warning'
                              : 'text-gray-700 dark:text-gray-300'
                          }
                        >
                          {alert.current_quantity}
                        </span>
                      </td>
                      <td className="px-6 py-4">
                        <Badge color={statusConf.color as 'success' | 'error'} size="sm">
                          {statusConf.label}
                        </Badge>
                      </td>
                      <td className="px-6 py-4 text-xs text-gray-500">
                        {new Date(alert.created_at).toLocaleDateString()}
                        {alert.resolved_at && (
                          <div>Resolved: {new Date(alert.resolved_at).toLocaleDateString()}</div>
                        )}
                      </td>
                      <td className="px-6 py-4 text-right">
                        {alert.status === 'active' && (
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleResolve(alert.id)}
                            disabled={resolvingId === alert.id}
                          >
                            {resolvingId === alert.id ? 'Resolving...' : 'Resolve'}
                          </Button>
                        )}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}

        {/* Pagination */}
        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-stroke px-6 py-4 dark:border-strokedark">
            <p className="text-sm text-body">
              Showing {(meta.current_page - 1) * meta.per_page + 1}–
              {Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total}
            </p>
            <div className="flex gap-2">
              <Button
                size="sm"
                variant="outline"
                disabled={meta.current_page === 1}
                onClick={() => setFilters((prev) => ({ ...prev, page: (prev.page ?? 1) - 1 }))}
              >
                Previous
              </Button>
              <Button
                size="sm"
                variant="outline"
                disabled={meta.current_page === meta.last_page}
                onClick={() => setFilters((prev) => ({ ...prev, page: (prev.page ?? 1) + 1 }))}
              >
                Next
              </Button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
