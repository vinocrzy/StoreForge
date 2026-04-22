import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useCreateCouponMutation } from '../../services/coupons';
import type { CreateCouponData } from '../../services/coupons';
import Alert from '../../components/ui/alert/Alert';
import CouponForm from './CouponForm';

const NewCouponPage = () => {
  const navigate = useNavigate();
  const [createCoupon, { isLoading }] = useCreateCouponMutation();
  const [alert, setAlert] = useState<{ variant: 'success' | 'error'; title: string; message: string } | null>(null);

  const handleSubmit = async (data: CreateCouponData) => {
    try {
      await createCoupon(data).unwrap();
      navigate('/coupons');
    } catch {
      setAlert({ variant: 'error', title: 'Error', message: 'Failed to create coupon. Please try again.' });
      setTimeout(() => setAlert(null), 4000);
    }
  };

  return (
    <>
      {alert && (
        <div className="p-6 pb-0">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}
      <CouponForm title="Create Coupon" onSubmit={handleSubmit} isSubmitting={isLoading} />
    </>
  );
};

export default NewCouponPage;
