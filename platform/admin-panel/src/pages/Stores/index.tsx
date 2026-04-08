/**
 * Stores List Page
 * Super admin view of all stores in the platform
 */

import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useGetStoresQuery, useUpdateStoreStatusMutation } from '../../services/stores';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';
import Badge from '../../components/ui/badge/Badge';
import type { Store, StoreFilters } from '../../types/store';

const StoresPage = () => {
  const navigate = useNavigate();
  const [filters, setFilters] = useState<StoreFilters>({
    page: 1,
    per_page: 20,
    status: undefined,
  });

  const { data: storesData, isLoading, error } = useGetStoresQuery(filters);
  const [updateStoreStatus, { isLoading: isUpdatingStatus }] = useUpdateStoreStatusMutation();
  const stores = storesData?.data || [];
  const meta = storesData?.meta;

  const handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setFilters(prev => ({ ...prev, search: event.target.value, page: 1 }));
  };

  const handleStatusChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    const value = event.target.value;
    setFilters(prev => ({
      ...prev,
      status: value ? value as 'active' | 'inactive' | 'suspended' : undefined,
      page: 1
    }));
  };

  const handlePageChange = (newPage: number) => {
    setFilters(prev => ({ ...prev, page: newPage }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const getStatusBadgeColor = (status: string) => {
    switch (status) {
      case 'active': return 'success';
      case 'inactive': return 'warning';
      case 'suspended': return 'error';
      default: return 'light';
    }
  };

  const handleStatusToggle = async (store: Store) => {
    const nextStatus = store.status === 'active' ? 'inactive' : 'active';

    try {
      await updateStoreStatus({
        id: store.id,
        data: { status: nextStatus },
      }).unwrap();
    } catch {
      // Keep UI simple here; error banner will show on refetch failure.
    }
  };

  if (error) {
    return (
      <div className="p-6">
        <Alert
          variant="error"
          title="Error Loading Stores"
          message="Unable to load stores. Please try again later."
        />
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Stores</h1>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage all stores in the platform
          </p>
        </div>
        <Button variant="primary" onClick={() => navigate('/stores/new')}>
          + Add Store
        </Button>
      </div>

      {/* Filters */}
      <div className="mb-6 flex flex-col gap-4 md:flex-row">
        <input
          type="search"
          placeholder="Search stores..."
          value={filters.search || ''}
          onChange={handleSearchChange}
          className="flex-1 rounded-lg border border-stroke bg-white py-2 px-4 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
        />
        <select
          value={filters.status || ''}
          onChange={handleStatusChange}
          className="rounded-lg border border-stroke bg-white py-2 px-4 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
        >
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="suspended">Suspended</option>
        </select>
      </div>

      {/* Stores List */}
      {isLoading ? (
        <div className="text-center py-12">
          <p className="text-gray-600 dark:text-gray-400">Loading stores...</p>
        </div>
      ) : stores.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-600 dark:text-gray-400 mb-4">No stores found</p>
          <Button variant="primary" onClick={() => navigate('/stores/new')}>
            Create First Store
          </Button>
        </div>
      ) : (
        <>
          {/* Results Count */}
          {meta && (
            <div className="mb-4 text-sm text-gray-600 dark:text-gray-400">
              Showing {((meta.current_page - 1) * meta.per_page) + 1} to{' '}
              {Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total} stores
            </div>
          )}

          {/* Table */}
          <div className="overflow-hidden rounded-lg border border-stroke bg-white shadow-sm dark:border-strokedark dark:bg-boxdark">
            <table className="w-full">
              <thead className="border-b border-stroke bg-gray-50 dark:border-strokedark dark:bg-meta-4">
                <tr>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    ID
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Store
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Domain
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Status
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Created
                  </th>
                  <th className="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {stores.map((store: Store) => (
                  <tr
                    key={store.id}
                    className="border-b border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-meta-4"
                  >
                    {/* Store ID */}
                    <td className="px-6 py-4">
                      <p className="font-mono text-sm font-semibold text-gray-900 dark:text-white">#{store.id}</p>
                    </td>

                    {/* Store Name */}
                    <td className="px-6 py-4">
                      <div
                        className="cursor-pointer"
                        onClick={() => navigate(`/stores/${store.id}`)}
                      >
                        <p className="font-medium text-gray-900 dark:text-white hover:text-primary">
                          {store.name}
                        </p>
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                          /{store.slug}
                        </p>
                      </div>
                    </td>

                    {/* Domain */}
                    <td className="px-6 py-4">
                      <p className="text-sm text-gray-700 dark:text-gray-300">
                        {store.domain || '-'}
                      </p>
                    </td>

                    {/* Status */}
                    <td className="px-6 py-4">
                      <Badge color={getStatusBadgeColor(store.status)} size="sm">
                        {store.status.charAt(0).toUpperCase() + store.status.slice(1)}
                      </Badge>
                    </td>

                    {/* Created */}
                    <td className="px-6 py-4">
                      <p className="text-sm text-gray-700 dark:text-gray-300">
                        {new Date(store.created_at).toLocaleDateString()}
                      </p>
                    </td>

                    {/* Actions */}
                    <td className="px-6 py-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="secondary"
                          size="sm"
                          onClick={() => navigate(`/stores/${store.id}`)}
                        >
                          View
                        </Button>
                        <Button
                          variant={store.status === 'active' ? 'warning' : 'success'}
                          size="sm"
                          onClick={() => void handleStatusToggle(store)}
                          disabled={isUpdatingStatus}
                        >
                          {store.status === 'active' ? 'Deactivate' : 'Activate'}
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {meta && meta.last_page > 1 && (
            <div className="mt-6 flex items-center justify-between">
              <Button
                variant="secondary"
                onClick={() => handlePageChange(meta.current_page - 1)}
                disabled={meta.current_page === 1}
              >
                Previous
              </Button>
              <span className="text-sm text-gray-700 dark:text-gray-300">
                Page {meta.current_page} of {meta.last_page}
              </span>
              <Button
                variant="secondary"
                onClick={() => handlePageChange(meta.current_page + 1)}
                disabled={meta.current_page === meta.last_page}
              >
                Next
              </Button>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default StoresPage;
