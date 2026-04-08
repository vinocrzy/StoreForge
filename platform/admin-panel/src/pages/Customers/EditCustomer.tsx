import { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router';
import { useGetCustomerQuery, useUpdateCustomerMutation } from '../../services/customers';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';
import type { UpdateCustomerData } from '../../types/customer';

interface FormErrors {
  first_name?: string;
  last_name?: string;
  phone?: string;
  email?: string;
  password?: string;
}

const EditCustomerPage = () => {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const customerId = Number(id);

  const { data: customer, isLoading: isLoadingCustomer, error: fetchError } = useGetCustomerQuery(customerId);
  const [updateCustomer, { isLoading: isUpdating }] = useUpdateCustomerMutation();

  const [formData, setFormData] = useState<UpdateCustomerData & { password?: string }>({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    password: '',
    status: 'active',
    date_of_birth: undefined,
    gender: undefined,
    notes: '',
  });

  const [errors, setErrors] = useState<FormErrors>({});
  const [alert, setAlert] = useState<{variant: 'success' | 'error', title: string, message: string} | null>(null);

  // Populate form when customer data loads
  useEffect(() => {
    if (customer) {
      setFormData({
        first_name: customer.first_name,
        last_name: customer.last_name,
        phone: customer.phone,
        email: customer.email || '',
        password: '', // Don't pre-fill password
        status: customer.status,
        date_of_birth: customer.date_of_birth || undefined,
        gender: customer.gender || undefined,
        notes: customer.notes || '',
      });
    }
  }, [customer]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value || undefined }));
    // Clear error for this field
    if (errors[name as keyof FormErrors]) {
      setErrors(prev => ({ ...prev, [name]: undefined }));
    }
  };

  const validate = (): boolean => {
    const newErrors: FormErrors = {};

    if (!formData.first_name?.trim()) {
      newErrors.first_name = 'First name is required';
    }
    if (!formData.last_name?.trim()) {
      newErrors.last_name = 'Last name is required';
    }
    if (!formData.phone?.trim()) {
      newErrors.phone = 'Phone number is required';
    } else if (!/^\+?[\d\s\-()]+$/.test(formData.phone)) {
      newErrors.phone = 'Invalid phone number format';
    }
    if (formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      newErrors.email = 'Invalid email format';
    }
    // Only validate password if user is changing it
    if (formData.password && formData.password.length < 8) {
      newErrors.password = 'Password must be at least 8 characters';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validate()) {
      return;
    }

    try {
      const updateData: UpdateCustomerData = {
        first_name: formData.first_name,
        last_name: formData.last_name,
        phone: formData.phone,
        email: formData.email || undefined,
        status: formData.status,
        date_of_birth: formData.date_of_birth || undefined,
        gender: formData.gender || undefined,
        notes: formData.notes || undefined,
      };

      // Only include password if user entered one
      if (formData.password) {
        (updateData as any).password = formData.password;
      }

      await updateCustomer({ id: customerId, data: updateData }).unwrap();
      
      setAlert({
        variant: 'success',
        title: 'Customer Updated',
        message: 'Customer information has been successfully updated.'
      });

      // Redirect after 1.5 seconds
      setTimeout(() => {
        navigate(`/customers/${customerId}`);
      }, 1500);
    } catch (error: any) {
      setAlert({
        variant: 'error',
        title: 'Update Failed',
        message: error?.data?.message || 'Failed to update customer. Please try again.'
      });
    }
  };

  if (isLoadingCustomer) {
    return (
      <div className="p-6">
        <div className="text-center py-12">
          <p className="text-gray-600 dark:text-gray-400">Loading customer...</p>
        </div>
      </div>
    );
  }

  if (fetchError || !customer) {
    return (
      <div className="p-6">
        <Alert variant="error" title="Error" message="Failed to load customer information" />
        <Button variant="ghost" onClick={() => navigate('/customers')} className="mt-4">
          ← Back to Customers
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

      <div className="mb-6">
        <Button variant="ghost" onClick={() => navigate(`/customers/${customerId}`)} className="mb-3">
          ← Back to Customer Details
        </Button>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Edit Customer</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Update customer information for {customer.first_name} {customer.last_name}
        </p>
      </div>

      <form onSubmit={handleSubmit} className="rounded-lg bg-white shadow dark:bg-boxdark p-6">
        <div className="space-y-6">
          {/* Personal Information */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Personal Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* First Name */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  First Name <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  name="first_name"
                  value={formData.first_name}
                  onChange={handleChange}
                  className={`w-full rounded-lg border ${
                    errors.first_name ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.first_name && (
                  <p className="mt-1 text-sm text-danger">{errors.first_name}</p>
                )}
              </div>

              {/* Last Name */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Last Name <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  name="last_name"
                  value={formData.last_name}
                  onChange={handleChange}
                  className={`w-full rounded-lg border ${
                    errors.last_name ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.last_name && (
                  <p className="mt-1 text-sm text-danger">{errors.last_name}</p>
                )}
              </div>

              {/* Date of Birth */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Date of Birth
                </label>
                <input
                  type="date"
                  name="date_of_birth"
                  value={formData.date_of_birth || ''}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                />
              </div>

              {/* Gender */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Gender
                </label>
                <select
                  name="gender"
                  value={formData.gender || ''}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                >
                  <option value="">Select Gender</option>
                  <option value="male">Male</option>
                  <option value="female">Female</option>
                  <option value="other">Other</option>
                  <option value="prefer_not_to_say">Prefer not to say</option>
                </select>
              </div>
            </div>
          </div>

          {/* Contact Information */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contact Information</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Phone */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Phone Number <span className="text-danger">*</span>
                </label>
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone}
                  onChange={handleChange}
                  placeholder="+1234567890"
                  className={`w-full rounded-lg border ${
                    errors.phone ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.phone && (
                  <p className="mt-1 text-sm text-danger">{errors.phone}</p>
                )}
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  E.164 format recommended (e.g., +12025551234)
                </p>
              </div>

              {/* Email */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Email (Optional)
                </label>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  className={`w-full rounded-lg border ${
                    errors.email ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.email && (
                  <p className="mt-1 text-sm text-danger">{errors.email}</p>
                )}
              </div>
            </div>
          </div>

          {/* Account Settings */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Account Settings</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Password */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Password (Optional)
                </label>
                <input
                  type="password"
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  placeholder="Leave blank to keep current password"
                  className={`w-full rounded-lg border ${
                    errors.password ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.password && (
                  <p className="mt-1 text-sm text-danger">{errors.password}</p>
                )}
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  Leave blank to keep current password. If changing, minimum 8 characters.
                </p>
              </div>

              {/* Status */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Status
                </label>
                <select
                  name="status"
                  value={formData.status}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="banned">Banned</option>
                </select>
              </div>
            </div>
          </div>

          {/* Notes */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Additional Notes</h2>
            <textarea
              name="notes"
              value={formData.notes}
              onChange={handleChange}
              rows={4}
              className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none resize-none"
              placeholder="Optional notes about this customer..."
            />
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t border-stroke dark:border-strokedark">
            <Button
              type="button"
              variant="secondary"
              onClick={() => navigate(`/customers/${customerId}`)}
              disabled={isUpdating}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              disabled={isUpdating}
            >
              {isUpdating ? 'Updating...' : 'Update Customer'}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
};

export default EditCustomerPage;
