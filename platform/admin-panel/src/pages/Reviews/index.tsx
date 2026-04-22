import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useGetReviewsQuery, useUpdateReviewMutation, useDeleteReviewMutation } from '../../services/reviews';
import type { Review } from '../../services/reviews';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import { Modal } from '../../components/ui/modal';

const statusBadgeColor = (status: Review['status']): 'success' | 'error' | 'warning' => {
  switch (status) {
    case 'approved': return 'success';
    case 'rejected': return 'error';
    default: return 'warning';
  }
};

const StarRating = ({ rating }: { rating: number }) => (
  <div className="flex gap-0.5">
    {[1, 2, 3, 4, 5].map((star) => (
      <svg
        key={star}
        className={`h-4 w-4 ${star <= rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'}`}
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    ))}
  </div>
);

const ReviewsPage = () => {
  const navigate = useNavigate();
  const [page, setPage] = useState(1);
  const [statusFilter, setStatusFilter] = useState('');
  const [deleteTarget, setDeleteTarget] = useState<Review | null>(null);
  const [rejectTarget, setRejectTarget] = useState<Review | null>(null);
  const [rejectionReason, setRejectionReason] = useState('');

  const { data, isLoading, error } = useGetReviewsQuery({ page, status: statusFilter || undefined });
  const [updateReview] = useUpdateReviewMutation();
  const [deleteReview, { isLoading: isDeleting }] = useDeleteReviewMutation();
  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);

  const showAlert = (variant: 'success' | 'error', title: string, message: string) => {
    setAlert({ variant, title, message });
    setTimeout(() => setAlert(null), 4000);
  };

  const handleApprove = async (id: number) => {
    try {
      await updateReview({ id, status: 'approved' }).unwrap();
      showAlert('success', 'Approved', 'Review has been approved.');
    } catch {
      showAlert('error', 'Error', 'Failed to approve review.');
    }
  };

  const handleReject = async () => {
    if (!rejectTarget) return;
    try {
      await updateReview({ id: rejectTarget.id, status: 'rejected', rejection_reason: rejectionReason || null }).unwrap();
      showAlert('success', 'Rejected', 'Review has been rejected.');
      setRejectTarget(null);
      setRejectionReason('');
    } catch {
      showAlert('error', 'Error', 'Failed to reject review.');
    }
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    try {
      await deleteReview(deleteTarget.id).unwrap();
      showAlert('success', 'Deleted', 'Review has been deleted.');
      setDeleteTarget(null);
    } catch {
      showAlert('error', 'Error', 'Failed to delete review.');
    }
  };

  const statusTabs: { label: string; value: string }[] = [
    { label: 'All', value: '' },
    { label: 'Pending', value: 'pending' },
    { label: 'Approved', value: 'approved' },
    { label: 'Rejected', value: 'rejected' },
  ];

  if (isLoading) return <div className="p-6 text-center text-gray-500 dark:text-gray-400">Loading reviews...</div>;

  if (error) {
    return (
      <div className="p-6">
        <Alert variant="error" title="Error" message="Failed to load reviews." />
      </div>
    );
  }

  const reviews = data?.data ?? [];
  const meta = data?.meta;

  return (
    <div className="p-6">
      {alert && (
        <div className="mb-4">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}

      {/* Header */}
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Product Reviews</h1>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">Moderate customer reviews</p>
      </div>

      {/* Status Tabs */}
      <div className="mb-4 flex gap-1 border-b border-stroke dark:border-strokedark">
        {statusTabs.map((tab) => (
          <button
            key={tab.value}
            onClick={() => { setStatusFilter(tab.value); setPage(1); }}
            className={`px-4 py-2.5 text-sm font-medium transition-colors ${
              statusFilter === tab.value
                ? 'border-b-2 border-primary text-primary'
                : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Table */}
      <div className="rounded-xl border border-stroke bg-white shadow-sm dark:border-strokedark dark:bg-boxdark overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-stroke dark:border-strokedark bg-gray-50 dark:bg-gray-800">
              <th className="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Product</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Customer</th>
              <th className="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Rating</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Title / Preview</th>
              <th className="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">Status</th>
              <th className="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-300">Date</th>
              <th className="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-300">Actions</th>
            </tr>
          </thead>
          <tbody>
            {reviews.length === 0 ? (
              <tr>
                <td colSpan={7} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  No reviews found.
                </td>
              </tr>
            ) : (
              reviews.map((review) => (
                <tr key={review.id} className="border-b border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-gray-800/50">
                  <td className="px-4 py-3">
                    <p className="font-medium text-gray-900 dark:text-white truncate max-w-[200px]">
                      {review.product.name}
                    </p>
                  </td>
                  <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                    {review.customer.first_name} {review.customer.last_name}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex justify-center">
                      <StarRating rating={review.rating} />
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <p className="font-medium text-gray-900 dark:text-white text-xs">
                      {review.title || '—'}
                    </p>
                    <p className="text-gray-500 dark:text-gray-400 text-xs truncate max-w-[250px]">
                      {review.body}
                    </p>
                  </td>
                  <td className="px-4 py-3 text-center">
                    <Badge color={statusBadgeColor(review.status)} size="sm">
                      {review.status.charAt(0).toUpperCase() + review.status.slice(1)}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-gray-600 dark:text-gray-400 text-xs">
                    {new Date(review.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3 text-right">
                    <div className="flex justify-end gap-1">
                      <Button variant="ghost" size="sm" onClick={() => navigate(`/reviews/${review.id}`)}>
                        View
                      </Button>
                      {review.status !== 'approved' && (
                        <Button variant="success" size="sm" onClick={() => handleApprove(review.id)}>
                          Approve
                        </Button>
                      )}
                      {review.status !== 'rejected' && (
                        <Button variant="danger" size="sm" onClick={() => { setRejectTarget(review); setRejectionReason(''); }}>
                          Reject
                        </Button>
                      )}
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

      {/* Reject Modal */}
      <Modal isOpen={!!rejectTarget} onClose={() => setRejectTarget(null)} className="max-w-md p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">Reject Review</h3>
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Provide an optional reason for rejecting this review.
        </p>
        <textarea
          className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 text-sm text-dark placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white min-h-[100px]"
          placeholder="Reason for rejection (optional)..."
          value={rejectionReason}
          onChange={(e) => setRejectionReason(e.target.value)}
        />
        <div className="flex justify-end gap-3 mt-4">
          <Button variant="outline" onClick={() => setRejectTarget(null)}>Cancel</Button>
          <Button variant="danger" onClick={handleReject}>Reject Review</Button>
        </div>
      </Modal>

      {/* Delete Modal */}
      <Modal isOpen={!!deleteTarget} onClose={() => setDeleteTarget(null)} className="max-w-md p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">Delete Review</h3>
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
          Are you sure you want to delete this review? This action cannot be undone.
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

export default ReviewsPage;
