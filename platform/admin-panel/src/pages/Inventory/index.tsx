import { useMemo, useState } from 'react';
import Alert from '../../components/ui/alert/Alert';
import Badge from '../../components/ui/badge/Badge';
import Button from '../../components/ui/button/Button';
import { useGetProductsQuery } from '../../services/products';
import {
  useAdjustInventoryMutation,
  useExportInventoryCsvMutation,
  useGetInventoryQuery,
  useGetWarehousesQuery,
} from '../../services/inventory';
import type { AdjustInventoryPayload, InventoryFilters } from '../../types/inventory';

const InventoryPage = () => {
  const [filters, setFilters] = useState<InventoryFilters>({
    page: 1,
    per_page: 20,
    sort_by: 'created_at',
    sort_order: 'desc',
  });
  const [stockStatus, setStockStatus] = useState<'all' | 'low' | 'out'>('all');
  const [isAdjustOpen, setIsAdjustOpen] = useState(false);
  const [adjustForm, setAdjustForm] = useState<AdjustInventoryPayload>({
    product_id: 0,
    warehouse_id: 0,
    quantity: 1,
    type: 'adjustment',
    notes: '',
  });
  const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const queryFilters = useMemo(() => {
    return {
      ...filters,
      low_stock: stockStatus === 'low' ? true : undefined,
      out_of_stock: stockStatus === 'out' ? true : undefined,
    };
  }, [filters, stockStatus]);

  const { data: inventoryData, isLoading, error } = useGetInventoryQuery(queryFilters);
  const { data: warehousesData } = useGetWarehousesQuery({ per_page: 100, sort_by: 'name', sort_order: 'asc' });
  const { data: productsData } = useGetProductsQuery({ per_page: 100, page: 1 });
  const [adjustInventory, { isLoading: isAdjusting }] = useAdjustInventoryMutation();
  const [exportInventoryCsv, { isLoading: isExporting }] = useExportInventoryCsvMutation();

  const handleExportCsv = async () => {
    try {
      const { product_id, warehouse_id } = filters;
      const exportFilters: { product_id?: number; warehouse_id?: number; low_stock?: boolean; out_of_stock?: boolean } = {
        product_id,
        warehouse_id,
        low_stock: stockStatus === 'low' ? true : undefined,
        out_of_stock: stockStatus === 'out' ? true : undefined,
      };
      const blob = await exportInventoryCsv(exportFilters).unwrap();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `inventory_export_${new Date().toISOString().slice(0, 10)}.csv`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
      setAlert({ type: 'success', message: 'Inventory exported successfully!' });
    } catch {
      setAlert({ type: 'error', message: 'Failed to export inventory. Please try again.' });
    }
  };

  const inventory = inventoryData?.data ?? [];
  const warehouses = warehousesData?.data ?? [];
  const products = productsData?.data ?? [];
  const meta = inventoryData?.meta;

  const getStockBadge = (available: number, threshold: number) => {
    if (available <= 0) {
      return <Badge color="error">Out of Stock</Badge>;
    }
    if (available <= threshold) {
      return <Badge color="warning">Low Stock</Badge>;
    }
    return <Badge color="success">In Stock</Badge>;
  };

  const handleFilterWarehouse = (warehouseId: string) => {
    setFilters((prev) => ({
      ...prev,
      page: 1,
      warehouse_id: warehouseId ? Number(warehouseId) : undefined,
    }));
  };

  const openAdjustModal = () => {
    setAdjustForm({
      product_id: products[0]?.id ?? 0,
      warehouse_id: warehouses[0]?.id ?? 0,
      quantity: 1,
      type: 'adjustment',
      notes: '',
    });
    setIsAdjustOpen(true);
  };

  const handleAdjustStock = async () => {
    if (!adjustForm.product_id || !adjustForm.warehouse_id || adjustForm.quantity < 1) {
      setAlert({ type: 'error', message: 'Please select product, warehouse, and a valid quantity.' });
      return;
    }

    try {
      await adjustInventory(adjustForm).unwrap();
      setAlert({ type: 'success', message: 'Stock adjustment saved successfully.' });
      setIsAdjustOpen(false);
    } catch (adjustError: any) {
      setAlert({ type: 'error', message: adjustError?.data?.message || 'Failed to adjust stock.' });
    }
  };

  return (
    <div className="p-6">
      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Stock Levels</h1>
          <p className="mt-2 text-gray-600 dark:text-gray-400">
            Monitor inventory across all warehouses ({meta?.total ?? 0} records)
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={handleExportCsv}
            disabled={isExporting}
          >
            {isExporting ? 'Exporting...' : 'Export CSV'}
          </Button>
          <Button variant="primary" onClick={openAdjustModal}>
            + Adjust Stock
          </Button>
        </div>
      </div>

      {alert && (
        <div className="mb-6">
          <Alert
            variant={alert.type}
            title={alert.type === 'success' ? 'Success' : 'Error'}
            message={alert.message}
          />
        </div>
      )}

      <div className="mb-6 rounded-lg border border-stroke bg-white p-4 shadow dark:border-strokedark dark:bg-boxdark">
        <div className="flex flex-col gap-3 lg:flex-row">
          <select
            value={filters.warehouse_id ?? ''}
            onChange={(e) => handleFilterWarehouse(e.target.value)}
            className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark lg:w-72"
          >
            <option value="">All Warehouses</option>
            {warehouses.map((warehouse) => (
              <option key={warehouse.id} value={warehouse.id}>
                {warehouse.name}
              </option>
            ))}
          </select>

          <div className="flex gap-2">
            <Button size="sm" variant={stockStatus === 'all' ? 'primary' : 'ghost'} onClick={() => setStockStatus('all')}>
              All
            </Button>
            <Button size="sm" variant={stockStatus === 'low' ? 'warning' : 'ghost'} onClick={() => setStockStatus('low')}>
              Low Stock
            </Button>
            <Button size="sm" variant={stockStatus === 'out' ? 'danger' : 'ghost'} onClick={() => setStockStatus('out')}>
              Out of Stock
            </Button>
          </div>
        </div>
      </div>

      <div className="overflow-hidden rounded-lg border border-stroke bg-white shadow dark:border-strokedark dark:bg-boxdark">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-stroke bg-gray-50 dark:border-strokedark dark:bg-boxdark-2">
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Product</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Warehouse</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Quantity</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Reserved</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Available</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Status</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {isLoading && (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    Loading inventory...
                  </td>
                </tr>
              )}

              {!isLoading && error && (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-danger">
                    Failed to load inventory data.
                  </td>
                </tr>
              )}

              {!isLoading && !error && inventory.length === 0 && (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    No inventory records found for selected filters.
                  </td>
                </tr>
              )}

              {!isLoading && !error && inventory.map((record) => (
                <tr key={record.id} className="hover:bg-gray-50 dark:hover:bg-boxdark-2">
                  <td className="px-6 py-4 text-gray-900 dark:text-white">
                    <p className="font-medium">{record.product?.name ?? `Product #${record.product_id}`}</p>
                    <p className="text-xs text-gray-500 dark:text-gray-400">{record.product?.sku ?? '-'}</p>
                  </td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                    {record.warehouse?.name ?? `Warehouse #${record.warehouse_id}`}
                  </td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{record.quantity}</td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{record.reserved_quantity}</td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{record.available_quantity}</td>
                  <td className="px-6 py-4">{getStockBadge(record.available_quantity, record.low_stock_threshold)}</td>
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

      {isAdjustOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-xl rounded-lg border border-stroke bg-white p-6 shadow-xl dark:border-strokedark dark:bg-boxdark">
            <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">Adjust Stock</h2>
            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Product</label>
                <select
                  value={adjustForm.product_id}
                  onChange={(e) => setAdjustForm((prev) => ({ ...prev, product_id: Number(e.target.value) }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                >
                  <option value={0}>Select product</option>
                  {products.map((product) => (
                    <option key={product.id} value={product.id}>
                      {product.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Warehouse</label>
                <select
                  value={adjustForm.warehouse_id}
                  onChange={(e) => setAdjustForm((prev) => ({ ...prev, warehouse_id: Number(e.target.value) }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                >
                  <option value={0}>Select warehouse</option>
                  {warehouses.map((warehouse) => (
                    <option key={warehouse.id} value={warehouse.id}>
                      {warehouse.name}
                    </option>
                  ))}
                </select>
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Movement Type</label>
                <select
                  value={adjustForm.type}
                  onChange={(e) =>
                    setAdjustForm((prev) => ({
                      ...prev,
                      type: e.target.value as AdjustInventoryPayload['type'],
                    }))
                  }
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                >
                  <option value="purchase">Purchase</option>
                  <option value="sale">Sale</option>
                  <option value="return">Return</option>
                  <option value="adjustment">Adjustment</option>
                  <option value="damage">Damage</option>
                  <option value="lost">Lost</option>
                </select>
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                <input
                  type="number"
                  min={1}
                  value={adjustForm.quantity}
                  onChange={(e) => setAdjustForm((prev) => ({ ...prev, quantity: Number(e.target.value) || 1 }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>
            </div>

            <div className="mt-4">
              <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
              <textarea
                rows={3}
                value={adjustForm.notes ?? ''}
                onChange={(e) => setAdjustForm((prev) => ({ ...prev, notes: e.target.value }))}
                className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                placeholder="Optional reason for this adjustment"
              />
            </div>

            <div className="mt-6 flex justify-end gap-3">
              <Button variant="ghost" onClick={() => setIsAdjustOpen(false)}>
                Cancel
              </Button>
              <Button variant="primary" onClick={handleAdjustStock} disabled={isAdjusting}>
                {isAdjusting ? 'Saving...' : 'Save Adjustment'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default InventoryPage;
