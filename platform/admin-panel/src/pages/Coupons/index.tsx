import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useGetCouponsQuery, useDeleteCouponMutation } from '../../services/coupons';
import type { Coupon } from '../../services/coupons';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import { Modal } from '../../components/ui/modal';

const statusBadgeColor = (status: Coupon['status']): 'success' | 'error' | 'warning' => {
  switch (status) {
    case 'active': return 'success';
    case 'expired': return 'error';
    default: return 'warning';
  }
};

const CouponsPage = () => {
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [search, setSearch] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [deleteTarget, setDeleteTarget] = useState<Coupon | null>(null);

  const { data, isLoading, error } = useGetCouponsQuery({ page, status: statusFilter || undefined, search: search || undefined });
  const [deleteCoupon, { isLoading: isDeleting }] = useDeleteCouponMutation();
  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);

  const handleDelete = async () => {
    if (!deleteTarget) return;
    try {
      await deleteCoupon(deleteTarget.id).unwrap();
      setAlert({ variant: 'success', title: 'Deleted', message: `Coupon "${deleteTarget.code}" deleted.` });
      setDeleteTarget(null);
    } catch {
      setAlert({ variant: 'error', title: 'Error', message: 'Failed to delete coupon.' });
    }
    setTimeout(() => setAlert(null), 4000);
  };

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    setSearch(searchInput);
    setPage(1);
  };

  if (isLoading) return <div className="p-6 text-center text-gray-500 dark:text-gray-400">Loading coupons...</div>;

  if (error) {
    return (
      <div className="p-6">
        <Alert variant="error" title="Error" message="Failed to load coupons." />
      </div>
    );
  }

  const coupons = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div className="p-6">
      {alert && (
        <div className="mb-4">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}

      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Coupons</h1>
        <Button variant="primary" onClick={() => navigate('/coupons/new')}>
          Create Coupon
        </Button>
      </div>

      {/* Filters */}
      <div className="mb-4 flex flex-wrap gap-3 items-end">
        <div>
          <label className="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Status</label>
          <select
            className="rounded-lg border border-stroke bg-white px-3 py-2 text-sm dark:border-strokedark dark:bg-boxdark dark:text-white"
            value={statusFilter}
            onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          >
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="expired">Expired</option>
          </select>
        </div>
        <form onSubmit={handleSearch} className="flex gap-2">
          <input
            type="text"
            className="rounded-lg border border-stroke bg-white px-3 py-2 text-sm placeholder:text-gray-400 dark:border-strokedark dark:bg-boxdark dark:text-white"
            placeholder="Search by code..."
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
          />
          <Button variant="outline" size="sm" type="submit">Search</Button>
        </form>
      </div>

      {/* Table */}
      <div className="rounded-xl border border-stroke bg-white shadow-sm dark:border-strokedark dark:bg-boxdark overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-stroke dark:border-strokedark bg-gray-50 dark:bg-gray-800">
              <th className="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Code</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Type</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Value</th>
              <th className="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Status</th>
              <th className="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Used / Limit</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Min Purchase</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Dates</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Actions</th>
            </tr>
          </thead>
          <tbody>
            {coupons.length === 0 ? (
              <tr>
                <td colSpan={8} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  No coupons found.
                </td>
              </tr>
            ) : (
              coupons.map((coupon) => (
                <tr key={coupon.id} className="border-b border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-gray-800/50">
                  <td className="px-4 py-3 font-mono font-medium text-gray-900 dark:text-white">
                    {coupon.code}
                  </td>
                  <td className="px-4 py-3 text-gray-700 dark:text-gray-300 capitalize">{coupon.type}</td>
                  <td className="px-4 py-3 text-right text-gray-900 dark:text-white">
                    {coupon.type === 'percentage' ? `${coupon.value}%` : `$${coupon.value.toFixed(2)}`}
                  </td>
                  <td className="px-4 py-3 text-center">
                    <Badge color={statusBadgeColor(coupon.status)} size="sm">
                      {coupon.status.charAt(0).toUpperCase() + coupon.status.slice(1)}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                    {coupon.used_count} / {coupon.usage_limit ?? '∞'}
                  </td>
                  <td className="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                    {coupon.minimum_purchase_amount != null ? `$${Number(coupon.minimum_purchase_amount).toFixed(2)}` : '—'}
                  </td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                    {coupon.starts_at ? new Date(coupon.starts_at).toLocaleDateString() : '—'}
                    {' → '}
                    {coupon.expires_at ? new Date(coupon.expires_at).toLocaleDateString() : '—'}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex justify-end gap-2">
                      <Button variant="ghost" size="sm" onClick={() => navigate(`/coupons/${coupon.id}/edit`)}>
                        Edit
                      </Button>
                      <Button variant="danger" size="sm" onClick={() => setDeleteTarget(coupon)}>
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="mt-4 flex items-center justify-between">
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Page {meta.current_page} of {meta.last_page} ({meta.total} total)
          </p>
          <div className="flex gap-2">
            <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={() => setPage(page - 1)}>
              Previous
            </Button>
            <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => setPage(page + 1)}>
              Next
            </Button>
          </div>
        </div>
      )}

      {/* Delete Confirmation Modal */}
      <Modal isOpen={!!deleteTarget} onClose={() => setDeleteTarget(null)} className="max-w-md p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete Coupon</h3>
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
          Are you sure you want to delete coupon <strong className="font-mono">{deleteTarget?.code}</strong>? This action cannot be undone.
        </p>
        <div className="flex justify-end gap-3">
          <Button variant="outline" onClick={() => setDeleteTarget(null)}>Cancel</Button>
          <Button variant="danger" onClick={handleDelete} disabled={isDeleting}>
            {isDeleting ? 'Deleting...' : 'Delete'}
          </Button>
        </div>
      </Modal>
    </div>
  );
};

export default CouponsPage;
