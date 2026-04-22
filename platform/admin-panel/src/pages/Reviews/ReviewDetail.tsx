import { useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { useGetReviewQuery, useUpdateReviewMutation, useDeleteReviewMutation } from '../../services/reviews';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import { Modal } from '../../components/ui/modal';

const StarRating = ({ rating }: { rating: number }) => (
  <div className="flex gap-0.5">
    {[1, 2, 3, 4, 5].map((star) => (
      <svg
        key={star}
        className={`h-5 w-5 ${star <= rating ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600'}`}
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
      </svg>
    ))}
  </div>
);

const statusBadgeColor = (status: string): 'success' | 'error' | 'warning' => {
  switch (status) {
    case 'approved': return 'success';
    case 'rejected': return 'error';
    default: return 'warning';
  }
};

const ReviewDetailPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const reviewId = parseInt(id!, 10);

  const { data: reviewData, isLoading } = useGetReviewQuery(reviewId);
  const [updateReview, { isLoading: isUpdating }] = useUpdateReviewMutation();
  const [deleteReview] = useDeleteReviewMutation();

  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [showResponseModal, setShowResponseModal] = useState(false);
  const [rejectionReason, setRejectionReason] = useState('');
  const [adminResponse, setAdminResponse] = useState('');

  const showAlertMsg = (variant: 'success' | 'error', title: string, message: string) => {
    setAlert({ variant, title, message });
    setTimeout(() => setAlert(null), 4000);
  };

  const handleApprove = async () => {
    try {
      await updateReview({ id: reviewId, status: 'approved' }).unwrap();
      showAlertMsg('success', 'Approved', 'Review has been approved.');
    } catch {
      showAlertMsg('error', 'Error', 'Failed to approve review.');
    }
  };

  const handleReject = async () => {
    try {
      await updateReview({ id: reviewId, status: 'rejected', rejection_reason: rejectionReason || null }).unwrap();
      showAlertMsg('success', 'Rejected', 'Review has been rejected.');
      setShowRejectModal(false);
      setRejectionReason('');
    } catch {
      showAlertMsg('error', 'Error', 'Failed to reject review.');
    }
  };

  const handleRespond = async () => {
    try {
      await updateReview({ id: reviewId, admin_response: adminResponse }).unwrap();
      showAlertMsg('success', 'Response Saved', 'Admin response has been posted.');
      setShowResponseModal(false);
    } catch {
      showAlertMsg('error', 'Error', 'Failed to save response.');
    }
  };

  const handleDelete = async () => {
    if (!confirm('Are you sure you want to delete this review?')) return;
    try {
      await deleteReview(reviewId).unwrap();
      navigate('/reviews');
    } catch {
      showAlertMsg('error', 'Error', 'Failed to delete review.');
    }
  };

  if (isLoading) {
    return <div className="p-6 text-center text-gray-500 dark:text-gray-400">Loading review...</div>;
  }

  if (!reviewData?.data) {
    return (
      <div className="p-6">
        <Alert variant="error" title="Not Found" message="Review not found." />
      </div>
    );
  }

  const review = reviewData.data;

  return (
    <div className="p-6">
      {alert && (
        <div className="mb-4">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}

      {/* Header */}
      <div className="mb-6">
        <Button variant="ghost" onClick={() => navigate('/reviews')} className="mb-3">
          ← Back to Reviews
        </Button>
        <div className="flex items-center justify-between">
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Review #{review.id}</h1>
          <div className="flex gap-2">
            {review.status !== 'approved' && (
              <Button variant="success" onClick={handleApprove} disabled={isUpdating}>
                Approve
              </Button>
            )}
            {review.status !== 'rejected' && (
              <Button variant="danger" onClick={() => { setShowRejectModal(true); setRejectionReason(''); }}>
                Reject
              </Button>
            )}
            <Button variant="primary" onClick={() => { setShowResponseModal(true); setAdminResponse(review.admin_response ?? ''); }}>
              Respond
            </Button>
            <Button variant="danger" size="sm" onClick={handleDelete}>
              Delete
            </Button>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Review Content */}
        <div className="lg:col-span-2 space-y-6">
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Review Content</h2>
            </div>
            <div className="p-4 space-y-4">
              <div className="flex items-center gap-3">
                <StarRating rating={review.rating} />
                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">{review.rating}/5</span>
                <Badge color={statusBadgeColor(review.status)}>
                  {review.status.charAt(0).toUpperCase() + review.status.slice(1)}
                </Badge>
                {review.is_verified_purchase && (
                  <Badge color="info" size="sm">Verified Purchase</Badge>
                )}
              </div>

              {review.title && (
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">{review.title}</h3>
              )}

              <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{review.body}</p>

              <p className="text-xs text-gray-500 dark:text-gray-400">
                Submitted on {new Date(review.created_at).toLocaleDateString('en-US', {
                  month: 'long',
                  day: 'numeric',
                  year: 'numeric',
                  hour: '2-digit',
                  minute: '2-digit',
                })}
              </p>
            </div>
          </div>

          {/* Admin Response */}
          {review.admin_response && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Admin Response</h2>
              </div>
              <div className="p-4">
                <p className="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{review.admin_response}</p>
                {review.admin_responded_at && (
                  <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Responded on {new Date(review.admin_responded_at).toLocaleDateString()}
                  </p>
                )}
              </div>
            </div>
          )}

          {/* Rejection Reason */}
          {review.rejection_reason && (
            <div className="rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20 p-4">
              <h3 className="text-sm font-semibold text-red-700 dark:text-red-400 mb-1">Rejection Reason</h3>
              <p className="text-sm text-red-600 dark:text-red-300">{review.rejection_reason}</p>
            </div>
          )}
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Product Info */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Product</h2>
            </div>
            <div className="p-4">
              <p className="font-medium text-gray-900 dark:text-white">{review.product.name}</p>
              <p className="text-xs text-gray-500 dark:text-gray-400">Slug: {review.product.slug}</p>
            </div>
          </div>

          {/* Customer Info */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Customer</h2>
            </div>
            <div className="p-4 space-y-2">
              <p className="font-medium text-gray-900 dark:text-white">
                {review.customer.first_name} {review.customer.last_name}
              </p>
              {review.customer.email && (
                <p className="text-sm text-gray-600 dark:text-gray-400">{review.customer.email}</p>
              )}
            </div>
          </div>

          {/* Order Info */}
          {review.order && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Order</h2>
              </div>
              <div className="p-4 space-y-2">
                <p className="font-medium text-gray-900 dark:text-white">{review.order.order_number}</p>
                <Badge color={review.order.status === 'delivered' ? 'success' : 'info'} size="sm">
                  {review.order.status}
                </Badge>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Reject Modal */}
      <Modal isOpen={showRejectModal} onClose={() => setShowRejectModal(false)} className="max-w-md p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">Reject Review</h3>
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Provide an optional reason for rejecting this review.
        </p>
        <textarea
          className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 text-sm text-dark placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white min-h-[100px]"
          placeholder="Reason for rejection..."
          value={rejectionReason}
          onChange={(e) => setRejectionReason(e.target.value)}
        />
        <div className="flex justify-end gap-3 mt-4">
          <Button variant="outline" onClick={() => setShowRejectModal(false)}>Cancel</Button>
          <Button variant="danger" onClick={handleReject} disabled={isUpdating}>
            {isUpdating ? 'Rejecting...' : 'Reject Review'}
          </Button>
        </div>
      </Modal>

      {/* Respond Modal */}
      <Modal isOpen={showResponseModal} onClose={() => setShowResponseModal(false)} className="max-w-md p-6">
        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">Admin Response</h3>
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
          Write a public response to this review.
        </p>
        <textarea
          className="w-full rounded-lg border border-stroke bg-white px-4 py-2.5 text-sm text-dark placeholder:text-gray-400 focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white min-h-[120px]"
          placeholder="Thank you for your feedback..."
          value={adminResponse}
          onChange={(e) => setAdminResponse(e.target.value)}
        />
        <div className="flex justify-end gap-3 mt-4">
          <Button variant="outline" onClick={() => setShowResponseModal(false)}>Cancel</Button>
          <Button variant="primary" onClick={handleRespond} disabled={isUpdating || !adminResponse.trim()}>
            {isUpdating ? 'Saving...' : 'Post Response'}
          </Button>
        </div>
      </Modal>
    </div>
  );
};

export default ReviewDetailPage;
