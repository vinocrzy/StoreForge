import { useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { useGetCouponQuery, useUpdateCouponMutation } from '../../services/coupons';
import type { CreateCouponData } from '../../services/coupons';
import Alert from '../../components/ui/alert/Alert';
import CouponForm from './CouponForm';

const EditCouponPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const couponId = parseInt(id!, 10);

  const { data: couponData, isLoading: isLoadingCoupon } = useGetCouponQuery(couponId);
  const [updateCoupon, { isLoading: isUpdating }] = useUpdateCouponMutation();
  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);

  const handleSubmit = async (data: CreateCouponData) => {
    try {
      await updateCoupon({ id: couponId, ...data }).unwrap();
      navigate('/coupons');
    } catch {
      setAlert({ variant: 'error', title: 'Error', message: 'Failed to update coupon. Please try again.' });
      setTimeout(() => setAlert(null), 4000);
    }
  };

  if (isLoadingCoupon) {
    return <div className="p-6 text-center text-gray-500 dark:text-gray-400">Loading coupon...</div>;
  }

  if (!couponData?.data) {
    return (
      <div className="p-6">
        <Alert variant="error" title="Not Found" message="Coupon not found." />
      </div>
    );
  }

  const coupon = couponData.data;

  return (
    <>
      {alert && (
        <div className="p-6 pb-0">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}
      <CouponForm
        title="Edit Coupon"
        initialData={{
          code: coupon.code,
          type: coupon.type,
          value: coupon.value,
          status: coupon.status,
          usage_limit: coupon.usage_limit,
          usage_limit_per_customer: coupon.usage_limit_per_customer,
          minimum_purchase_amount: coupon.minimum_purchase_amount,
          maximum_discount_amount: coupon.maximum_discount_amount,
          starts_at: coupon.starts_at,
          expires_at: coupon.expires_at,
        }}
        onSubmit={handleSubmit}
        isSubmitting={isUpdating}
      />
    </>
  );
};

export default EditCouponPage;
