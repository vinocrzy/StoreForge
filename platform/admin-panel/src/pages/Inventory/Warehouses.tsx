import { useState } from 'react';
import Alert from '../../components/ui/alert/Alert';
import Badge from '../../components/ui/badge/Badge';
import Button from '../../components/ui/button/Button';
import {
  useCreateWarehouseMutation,
  useDeleteWarehouseMutation,
  useGetWarehousesQuery,
  useSetDefaultWarehouseMutation,
  useUpdateWarehouseMutation,
} from '../../services/inventory';
import type { CreateWarehousePayload, Warehouse, WarehouseFilters } from '../../types/inventory';

type WarehouseFormState = CreateWarehousePayload;

const emptyForm: WarehouseFormState = {
  name: '',
  code: '',
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: 'US',
  is_active: true,
};

const WarehousesPage = () => {
  const [filters, setFilters] = useState<WarehouseFilters>({
    page: 1,
    per_page: 20,
    sort_by: 'name',
    sort_order: 'asc',
  });
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingWarehouse, setEditingWarehouse] = useState<Warehouse | null>(null);
  const [form, setForm] = useState<WarehouseFormState>(emptyForm);
  const [alert, setAlert] = useState<{ type: 'success' | 'error'; message: string } | null>(null);

  const { data, isLoading, error } = useGetWarehousesQuery(filters);
  const [createWarehouse, { isLoading: isCreating }] = useCreateWarehouseMutation();
  const [updateWarehouse, { isLoading: isUpdating }] = useUpdateWarehouseMutation();
  const [deleteWarehouse, { isLoading: isDeleting }] = useDeleteWarehouseMutation();
  const [setDefaultWarehouse, { isLoading: isSettingDefault }] = useSetDefaultWarehouseMutation();

  const warehouses = data?.data ?? [];
  const meta = data?.meta;

  const openCreateModal = () => {
    setEditingWarehouse(null);
    setForm(emptyForm);
    setIsModalOpen(true);
  };

  const openEditModal = (warehouse: Warehouse) => {
    setEditingWarehouse(warehouse);
    setForm({
      name: warehouse.name,
      code: warehouse.code,
      address: warehouse.address ?? '',
      city: warehouse.city ?? '',
      state: warehouse.state ?? '',
      postal_code: warehouse.postal_code ?? '',
      country: warehouse.country ?? 'US',
      is_active: warehouse.is_active,
    });
    setIsModalOpen(true);
  };

  const handleSaveWarehouse = async () => {
    if (!form.name.trim() || !form.code.trim()) {
      setAlert({ type: 'error', message: 'Warehouse name and code are required.' });
      return;
    }

    try {
      if (editingWarehouse) {
        await updateWarehouse({ id: editingWarehouse.id, ...form }).unwrap();
        setAlert({ type: 'success', message: 'Warehouse updated successfully.' });
      } else {
        await createWarehouse(form).unwrap();
        setAlert({ type: 'success', message: 'Warehouse created successfully.' });
      }

      setIsModalOpen(false);
    } catch (saveError: any) {
      setAlert({ type: 'error', message: saveError?.data?.message || 'Failed to save warehouse.' });
    }
  };

  const handleToggleActive = async (warehouse: Warehouse) => {
    try {
      await updateWarehouse({ id: warehouse.id, is_active: !warehouse.is_active }).unwrap();
      setAlert({ type: 'success', message: 'Warehouse status updated.' });
    } catch (updateError: any) {
      setAlert({ type: 'error', message: updateError?.data?.message || 'Failed to update warehouse status.' });
    }
  };

  const handleDelete = async (warehouse: Warehouse) => {
    const confirmed = window.confirm(`Delete warehouse "${warehouse.name}"? This cannot be undone.`);
    if (!confirmed) {
      return;
    }

    try {
      await deleteWarehouse(warehouse.id).unwrap();
      setAlert({ type: 'success', message: 'Warehouse deleted successfully.' });
    } catch (deleteError: any) {
      setAlert({ type: 'error', message: deleteError?.data?.message || 'Failed to delete warehouse.' });
    }
  };

  const handleSetDefault = async (warehouse: Warehouse) => {
    try {
      await setDefaultWarehouse(warehouse.id).unwrap();
      setAlert({ type: 'success', message: `${warehouse.name} is now the default warehouse.` });
    } catch (defaultError: any) {
      setAlert({ type: 'error', message: defaultError?.data?.message || 'Failed to set default warehouse.' });
    }
  };

  return (
    <div className="p-6">
      <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Warehouses</h1>
          <p className="mt-2 text-gray-600 dark:text-gray-400">
            Manage storage locations and inventory distribution ({meta?.total ?? 0} warehouses)
          </p>
        </div>
        <Button variant="primary" onClick={openCreateModal}>+ Add Warehouse</Button>
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
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant={filters.is_active === undefined ? 'primary' : 'ghost'}
            onClick={() => setFilters((prev) => ({ ...prev, page: 1, is_active: undefined }))}
          >
            All
          </Button>
          <Button
            size="sm"
            variant={filters.is_active === true ? 'primary' : 'ghost'}
            onClick={() => setFilters((prev) => ({ ...prev, page: 1, is_active: true }))}
          >
            Active
          </Button>
          <Button
            size="sm"
            variant={filters.is_active === false ? 'primary' : 'ghost'}
            onClick={() => setFilters((prev) => ({ ...prev, page: 1, is_active: false }))}
          >
            Inactive
          </Button>
        </div>
      </div>

      <div className="overflow-hidden rounded-lg border border-stroke bg-white shadow dark:border-strokedark dark:bg-boxdark">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-stroke bg-gray-50 dark:border-strokedark dark:bg-boxdark-2">
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Warehouse</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Code</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Location</th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">Status</th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {isLoading && (
                <tr>
                  <td colSpan={5} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">Loading warehouses...</td>
                </tr>
              )}

              {!isLoading && error && (
                <tr>
                  <td colSpan={5} className="px-6 py-12 text-center text-danger">Failed to load warehouses.</td>
                </tr>
              )}

              {!isLoading && !error && warehouses.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    No warehouses found.
                  </td>
                </tr>
              )}

              {!isLoading && !error && warehouses.map((warehouse) => (
                <tr key={warehouse.id} className="hover:bg-gray-50 dark:hover:bg-boxdark-2">
                  <td className="px-6 py-4 text-gray-900 dark:text-white">
                    <div className="flex items-center gap-2">
                      <p className="font-medium">{warehouse.name}</p>
                      {warehouse.is_default && <Badge color="info">Default</Badge>}
                    </div>
                    <p className="text-xs text-gray-500 dark:text-gray-400">{warehouse.address || 'No address provided'}</p>
                  </td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{warehouse.code}</td>
                  <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                    {[warehouse.city, warehouse.state, warehouse.country].filter(Boolean).join(', ') || '-'}
                  </td>
                  <td className="px-6 py-4">
                    {warehouse.is_active ? <Badge color="success">Active</Badge> : <Badge color="warning">Inactive</Badge>}
                  </td>
                  <td className="px-6 py-4 text-right">
                    <div className="flex justify-end gap-2">
                      <button
                        onClick={() => openEditModal(warehouse)}
                        className="text-sm font-medium text-primary hover:text-primary/80"
                      >
                        Edit
                      </button>
                      <button
                        onClick={() => handleSetDefault(warehouse)}
                        disabled={warehouse.is_default || isSettingDefault}
                        className="text-sm font-medium text-indigo-600 hover:text-indigo-500 disabled:opacity-50"
                      >
                        Set Default
                      </button>
                      <button
                        onClick={() => handleToggleActive(warehouse)}
                        className="text-sm font-medium text-blue-600 hover:text-blue-500"
                      >
                        {warehouse.is_active ? 'Disable' : 'Enable'}
                      </button>
                      <button
                        onClick={() => handleDelete(warehouse)}
                        disabled={isDeleting}
                        className="text-sm font-medium text-danger hover:text-danger/80 disabled:opacity-50"
                      >
                        Delete
                      </button>
                    </div>
                  </td>
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

      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-2xl rounded-lg border border-stroke bg-white p-6 shadow-xl dark:border-strokedark dark:bg-boxdark">
            <h2 className="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
              {editingWarehouse ? 'Edit Warehouse' : 'Add Warehouse'}
            </h2>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Warehouse Name</label>
                <input
                  type="text"
                  value={form.name}
                  onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Code</label>
                <input
                  type="text"
                  value={form.code}
                  onChange={(e) => setForm((prev) => ({ ...prev, code: e.target.value }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>

              <div className="md:col-span-2">
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Address</label>
                <input
                  type="text"
                  value={form.address ?? ''}
                  onChange={(e) => setForm((prev) => ({ ...prev, address: e.target.value }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">City</label>
                <input
                  type="text"
                  value={form.city ?? ''}
                  onChange={(e) => setForm((prev) => ({ ...prev, city: e.target.value }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">State</label>
                <input
                  type="text"
                  value={form.state ?? ''}
                  onChange={(e) => setForm((prev) => ({ ...prev, state: e.target.value }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Postal Code</label>
                <input
                  type="text"
                  value={form.postal_code ?? ''}
                  onChange={(e) => setForm((prev) => ({ ...prev, postal_code: e.target.value }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Country (2 letters)</label>
                <input
                  type="text"
                  maxLength={2}
                  value={form.country ?? ''}
                  onChange={(e) => setForm((prev) => ({ ...prev, country: e.target.value.toUpperCase() }))}
                  className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 dark:border-strokedark dark:bg-boxdark"
                />
              </div>
            </div>

            <div className="mt-4 flex items-center gap-3">
              <input
                id="warehouse-active"
                type="checkbox"
                checked={form.is_active ?? true}
                onChange={(e) => setForm((prev) => ({ ...prev, is_active: e.target.checked }))}
                className="h-4 w-4"
              />
              <label htmlFor="warehouse-active" className="text-sm text-gray-700 dark:text-gray-300">
                Warehouse is active
              </label>
            </div>

            <div className="mt-6 flex justify-end gap-3">
              <Button variant="ghost" onClick={() => setIsModalOpen(false)}>
                Cancel
              </Button>
              <Button variant="primary" onClick={handleSaveWarehouse} disabled={isCreating || isUpdating}>
                {isCreating || isUpdating ? 'Saving...' : 'Save Warehouse'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default WarehousesPage;
