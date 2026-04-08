import { useState } from 'react';
import { useParams, useNavigate } from 'react-router';
import { useGetCustomerQuery, useUpdateCustomerStatusMutation, useVerifyEmailMutation, useVerifyPhoneMutation } from '../../services/customers';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import type { CustomerStatus } from '../../types/customer';

const CustomerDetailsPage = () => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const customerId = parseInt(id!, 10);

  const { data: customer, isLoading, error } = useGetCustomerQuery(customerId);
  const [updateStatus] = useUpdateCustomerStatusMutation();
  const [verifyEmail] = useVerifyEmailMutation();
  const [verifyPhone] = useVerifyPhoneMutation();

  const [alert, setAlert] = useState<{variant: 'success' | 'error', title: string, message: string} | null>(null);

  const getStatusBadgeColor = (status: CustomerStatus): 'success' | 'error' | 'warning' => {
    switch (status) {
      case 'active':
        return 'success';
      case 'banned':
        return 'error';
      default:
        return 'warning';
    }
  };

  const handleStatusChange = async (newStatus: CustomerStatus) => {
    try {
      await updateStatus({ id: customerId, data: { status: newStatus } }).unwrap();
      setAlert({
        variant: 'success',
        title: 'Status Updated',
        message: `Customer status changed to ${newStatus}.`
      });
    } catch (err) {
      setAlert({
        variant: 'error',
        title: 'Update Failed',
        message: 'Failed to update customer status.'
      });
    }
  };

 const handleVerifyEmail = async () => {
    try {
      await verifyEmail(customerId).unwrap();
      setAlert({
        variant: 'success',
        title: 'Email Verified',
        message: 'Customer email has been marked as verified.'
      });
    } catch (err) {
      setAlert({
        variant: 'error',
        title: 'Verification Failed',
        message: 'Failed to verify email.'
      });
    }
  };

  const handleVerifyPhone = async () => {
    try {
      await verifyPhone(customerId).unwrap();
      setAlert({
        variant: 'success',
        title: 'Phone Verified',
        message: 'Customer phone has been marked as verified.'
      });
    } catch (err) {
      setAlert({
        variant: 'error',
        title: 'Verification Failed',
        message: 'Failed to verify phone.'
      });
    }
  };

  if (isLoading) {
    return (
      <div className="p-6">
        <div className="text-center text-gray-500 dark:text-gray-400">
          Loading customer details...
        </div>
      </div>
    );
  }

  if (error || !customer) {
    return (
      <div className="p-6">
        <Alert 
          variant="error" 
          title="Error Loading Customer"
          message="Unable to load customer details. Please try again later." 
        />
        <Button variant="secondary" onClick={() => navigate('/customers')} className="mt-4">
          Back to Customers
        </Button>
      </div>
    );
  }

  return (
    <div className="p-6">
      {alert && (
        <div className="mb-4">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}

      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div>
          <Button variant="ghost" onClick={() => navigate('/customers')} className="mb-3">
            ← Back to Customers
          </Button>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            {customer.first_name} {customer.last_name}
          </h1>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Customer since {new Date(customer.created_at).toLocaleDateString('en-US', {
              month: 'long',
              day: 'numeric',
              year: 'numeric'
            })}
          </p>
        </div>
        <div className="flex gap-2">
          <Button variant="primary" onClick={() => navigate(`/customers/${customerId}/edit`)}>
            Edit Customer
          </Button>
          {customer.status === 'active' && (
            <Button variant="warning" onClick={() => handleStatusChange('inactive')}>
              Deactivate
            </Button>
          )}
          {customer.status === 'inactive' && (
            <Button variant="success" onClick={() => handleStatusChange('active')}>
              Activate
            </Button>
          )}
          {customer.status !== 'banned' && (
            <Button variant="danger" onClick={() => handleStatusChange('banned')}>
              Ban Customer
            </Button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {/* Left Column - Customer Info */}
        <div className="lg:col-span-2 space-y-6">
          {/* Contact Information */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Contact Information</h2>
            </div>
            <div className="p-4 space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Phone</p>
                  <div className="flex items-center gap-2 mt-1">
                    <p className="text-gray-900 dark:text-white">{customer.phone}</p>
                    {customer.phone_verified_at ? (
                      <Badge color="success" size="sm">Verified</Badge>
                    ) : (
                      <Button variant="ghost" size="sm" onClick={handleVerifyPhone}>
                        Verify
                      </Button>
                    )}
                  </div>
                </div>
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Email</p>
                  <div className="flex items-center gap-2 mt-1">
                    <p className="text-gray-900 dark:text-white">{customer.email || 'Not provided'}</p>
                    {customer.email && !customer.email_verified_at && (
                      <Button variant="ghost" size="sm" onClick={handleVerifyEmail}>
                        Verify
                      </Button>
                    )}
                    {customer.email_verified_at && (
                      <Badge color="success" size="sm">Verified</Badge>
                    )}
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Personal Information */}
          {(customer.date_of_birth || customer.gender) && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Personal Information</h2>
              </div>
              <div className="p-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {customer.date_of_birth && (
                    <div>
                      <p className="text-sm text-gray-600 dark:text-gray-400">Date of Birth</p>
                      <p className="text-gray-900 dark:text-white">
                        {new Date(customer.date_of_birth).toLocaleDateString()}
                      </p>
                    </div>
                  )}
                  {customer.gender && (
                    <div>
                      <p className="text-sm text-gray-600 dark:text-gray-400">Gender</p>
                      <p className="text-gray-900 dark:text-white capitalize">
                        {customer.gender.replace('_', ' ')}
                      </p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* Addresses */}
          {customer.addresses && customer.addresses.length > 0 && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Addresses</h2>
              </div>
              <div className="p-4 space-y-4">
                {customer.addresses.map((address: any) => (
                  <div key={address.id} className="rounded border border-stroke dark:border-strokedark p-4">
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        {address.label && (
                          <span className="font-medium text-gray-900 dark:text-white">{address.label}</span>
                        )}
                        <Badge color="info" size="sm">{address.type}</Badge>
                        {address.is_default && <Badge color="primary" size="sm">Default</Badge>}
                      </div>
                    </div>
                    <div className="text-gray-700 dark:text-gray-300 space-y-1">
                      <p>{address.first_name} {address.last_name}</p>
                      {address.company && <p>{address.company}</p>}
                      <p>{address.address_line1}</p>
                      {address.address_line2 && <p>{address.address_line2}</p>}
                      <p>{address.city}, {address.state_province} {address.postal_code}</p>
                      <p>{address.country}</p>
                      <p className="text-sm text-gray-600 dark:text-gray-400">Phone: {address.phone}</p>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Notes */}
          {customer.notes && (
            <div className="rounded-lg bg-white shadow dark:bg-boxdark">
              <div className="border-b border-stroke dark:border-strokedark p-4">
                <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Notes</h2>
              </div>
              <div className="p-4">
                <p className="text-gray-700 dark:text-gray-300">{customer.notes}</p>
              </div>
            </div>
          )}
        </div>

        {/* Right Column - Status & Activity */}
        <div className="space-y-6">
          {/* Status Card */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Status</h2>
            </div>
            <div className="p-4">
              <Badge color={getStatusBadgeColor(customer.status)}>
                {customer.status.charAt(0).toUpperCase() + customer.status.slice(1)}
              </Badge>
            </div>
          </div>

          {/* Activity */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Activity</h2>
            </div>
            <div className="p-4 space-y-3">
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Customer Since</p>
                <p className="text-gray-900 dark:text-white">
                  {new Date(customer.created_at).toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                  })}
                </p>
              </div>
              {customer.last_login_at && (
                <div>
                  <p className="text-sm text-gray-600 dark:text-gray-400">Last Login</p>
                  <p className="text-gray-900 dark:text-white">
                    {new Date(customer.last_login_at).toLocaleDateString('en-US', {
                      month: 'long',
                      day: 'numeric',
                      year: 'numeric',
                      hour: '2-digit',
                      minute: '2-digit'
                    })}
                  </p>
                </div>
              )}
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400">Last Updated</p>
                <p className="text-gray-900 dark:text-white">
                  {new Date(customer.updated_at).toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                  })}
                </p>
              </div>
            </div>
          </div>

          {/* Verification Status */}
          <div className="rounded-lg bg-white shadow dark:bg-boxdark">
            <div className="border-b border-stroke dark:border-strokedark p-4">
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Verification</h2>
            </div>
            <div className="p-4 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-sm text-gray-700 dark:text-gray-300">Phone</span>
                {customer.phone_verified_at ? (
                  <Badge color="success" size="sm">Verified ✓</Badge>
                ) : (
                  <Badge color="warning" size="sm">Not Verified</Badge>
                )}
              </div>
              {customer.email && (
                <div className="flex items-center justify-between">
                  <span className="text-sm text-gray-700 dark:text-gray-300">Email</span>
                  {customer.email_verified_at ? (
                    <Badge color="success" size="sm">Verified ✓</Badge>
                  ) : (
                    <Badge color="warning" size="sm">Not Verified</Badge>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default CustomerDetailsPage;
