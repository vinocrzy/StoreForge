import { useState } from 'react';
import Alert from '../../components/ui/alert/Alert';
import Badge from '../../components/ui/badge/Badge';
import Button from '../../components/ui/button/Button';
import { useGetStockMovementsQuery } from '../../services/inventory';
import type { StockMovementFilters } from '../../types/inventory';

const movementTypeColors: Record<string, 'primary' | 'success' | 'warning' | 'error' | 'info'> = {
  purchase: 'success',
  sale: 'primary',
  return: 'info',
  adjustment: 'warning',
  damage: 'error',
  lost: 'error',
  transfer_in: 'success',
  transfer_out: 'warning',
};

const StockMovementsPage = () => {
  const [filters, setFilters] = useState<StockMovementFilters>({
    page: 1,
    per_page: 20,
    type: '',
  });

  const { data, isLoading, error } = useGetStockMovementsQuery(filters);
  const movements = data?.data ?? [];
  const meta = data?.meta;

  const formatDateTime = (value: string) => {
    const date = new Date(value);
    return date.toLocaleString();
  };

  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Stock Movements</h1>
        <p className="mt-2 text-gray-600 dark:text-gray-400">
          Track inventory changes and transfers ({meta?.total ?? 0} records)
        </p>
      </div>

      <div className="mb-6 rounded-lg border border-stroke bg-white p-4 shadow dark:border-strokedark dark:bg-boxdark">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
          <select
            value={filters.type || ''}
            onChange={(e) => setFilters((prev) => ({ ...prev, page: 1, type: e.target.value || undefined }))}
            className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark sm:w-72"
          >
            <option value="">All Movement Types</option>
            <option value="purchase">Purchase</option>
            <option value="sale">Sale</option>
            <option value="return">Return</option>
            <option value="adjustment">Adjustment</option>
            <option value="damage">Damage</option>
            <option value="lost">Lost</option>
            <option value="transfer_in">Transfer In</option>
            <option value="transfer_out">Transfer Out</option>
          </select>
        </div>
      </div>

      <div className="overflow-hidden rounded-lg border border-stroke bg-white shadow dark:border-strokedark dark:bg-boxdark">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-stroke bg-gray-50 dark:border-strokedark dark:bg-boxdark-2">
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Date</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Product</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Warehouse</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Type</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Quantity</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">User</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Notes</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {isLoading && (
                <tr>
                  <td colSpan={7} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    Loading stock movements...
                  </td>
                </tr>
              )}

              {!isLoading && error && (
                <tr>
                  <td colSpan={7} className="px-6 py-12 text-center">
                    <Alert variant="error" title="Error" message="Failed to load stock movements." />
                  </td>
                </tr>
              )}

              {!isLoading && !error && movements.length === 0 && (
                <tr>
                  <td colSpan={7} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    No stock movements found.
                  </td>
                </tr>
              )}

              {!isLoading && !error && movements.map((movement) => (
                <tr key={movement.id} className="hover:bg-gray-50 dark:hover:bg-boxdark-2">
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{formatDateTime(movement.created_at)}</td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                    {movement.inventory?.product?.name ?? 'Unknown Product'}
                  </td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                    {movement.inventory?.warehouse?.name ?? `Warehouse #${movement.inventory?.warehouse_id ?? '-'}`}
                  </td>
                  <td className="px-6 py-4">
                    <Badge color={movementTypeColors[movement.type] ?? 'primary'}>{movement.type}</Badge>
                  </td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{movement.quantity}</td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                    {movement.user?.name || [movement.user?.first_name, movement.user?.last_name].filter(Boolean).join(' ') || 'System'}
                  </td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{movement.notes || '-'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {meta && meta.last_page > 1 && (
          <div className="flex items-center justify-between border-t border-stroke px-6 py-4 dark:border-strokedark">
            <p className="text-sm text-gray-600 dark:text-gray-400">
              Page {meta.current_page} of {meta.last_page}
            </p>
            <div className="flex gap-2">
              <Button
                variant="ghost"
                size="sm"
                disabled={meta.current_page === 1}
                onClick={() => setFilters((prev) => ({ ...prev, page: (prev.page ?? 1) - 1 }))}
              >
                Previous
              </Button>
              <Button
                variant="ghost"
                size="sm"
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
};

export default StockMovementsPage;
