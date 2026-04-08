/**
 * Store Details Page
 * View and manage individual store
 */

import { useNavigate, useParams } from 'react-router';
import { useGetStoreQuery, useUpdateStoreStatusMutation } from '../../services/stores';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';
import Badge from '../../components/ui/badge/Badge';

const StoreDetailsPage = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const storeId = Number(id);

  const { data, isLoading, error } = useGetStoreQuery(storeId);
  const [updateStoreStatus, { isLoading: isUpdatingStatus }] = useUpdateStoreStatusMutation();

  const store = data?.data;
  const stats = data?.meta;

  if (isLoading) {
    return (
      <div className="p-6">
        <div className="text-center py-12">
          <p className="text-gray-600 dark:text-gray-400">Loading store...</p>
        </div>
      </div>
    );
  }

  if (error || !store) {
    return (
      <div className="p-6">
        <Alert variant="error" title="Error" message="Failed to load store information" />
        <Button variant="ghost" onClick={() => navigate('/stores')} className="mt-4">
          ← Back to Stores
        </Button>
      </div>
    );
  }

  const handleStatusChange = async (status: 'active' | 'inactive' | 'suspended') => {
    await updateStoreStatus({ id: storeId, data: { status } }).unwrap();
  };

  const getStatusBadgeColor = (status: string) => {
    switch (status) {
      case 'active': return 'success';
      case 'inactive': return 'warning';
      case 'suspended': return 'error';
      default: return 'light';
    }
  };

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6">
        <Button variant="ghost" onClick={() => navigate('/stores')} className="mb-3">
          ← Back to Stores
        </Button>
        <div className="flex items-start justify-between">
          <div>
            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{store.name}</h1>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">/{store.slug}</p>
            <p className="mt-1 text-sm font-mono text-gray-700 dark:text-gray-300">Store ID: {store.id}</p>
          </div>
          <div className="flex gap-3">
            <Button variant="success" onClick={() => void handleStatusChange('active')} disabled={isUpdatingStatus || store.status === 'active'}>
              Activate
            </Button>
            <Button variant="warning" onClick={() => void handleStatusChange('inactive')} disabled={isUpdatingStatus || store.status === 'inactive'}>
              Deactivate
            </Button>
            <Button variant="danger" onClick={() => void handleStatusChange('suspended')} disabled={isUpdatingStatus || store.status === 'suspended'}>
              Suspend
            </Button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Store Information */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Store Information</h2>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Store ID
                </label>
                <p className="font-mono text-gray-900 dark:text-white">{store.id}</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Store Name
                </label>
                <p className="text-gray-900 dark:text-white">{store.name}</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Slug
                </label>
                <p className="text-gray-900 dark:text-white">/{store.slug}</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Domain
                </label>
                <p className="text-gray-900 dark:text-white">{store.domain || 'Not configured'}</p>
              </div>
            </div>
          </div>

          {/* Statistics */}
          {stats && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-6">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Statistics</h2>
              </div>
              <div className="p-6 grid grid-cols-2 gap-6">
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Total Products</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.products_count}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Total Orders</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.orders_count}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Total Customers</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.customers_count}</p>
                </div>
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Total Products</p>
                  <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.products_count}</p>
                </div>
              </div>
            </div>
          )}

          {/* Settings */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-6">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Settings</h2>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Currency
                </label>
                <p className="text-gray-900 dark:text-white">{store.currency ?? '-'}</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Timezone
                </label>
                <p className="text-gray-900 dark:text-white">{store.timezone ?? '-'}</p>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Language
                </label>
                <p className="text-gray-900 dark:text-white">{store.language ?? '-'}</p>
              </div>
            </div>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Status Card */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark p-6">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Status</h3>
            <Badge color={getStatusBadgeColor(store.status)} size="md">
              {store.status.charAt(0).toUpperCase() + store.status.slice(1)}
            </Badge>
          </div>

          {/* Dates */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark p-6">
            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Activity</h3>
            <div className="space-y-3 text-sm">
              <div>
                <p className="text-gray-600 dark:text-gray-400">Created</p>
                <p className="text-gray-900 dark:text-white font-medium">
                  {new Date(store.created_at).toLocaleDateString()}
                </p>
              </div>
              <div>
                <p className="text-gray-600 dark:text-gray-400">Last Updated</p>
                <p className="text-gray-900 dark:text-white font-medium">
                  {new Date(store.updated_at).toLocaleDateString()}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default StoreDetailsPage;
