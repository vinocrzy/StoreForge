/**
 * New Store Page
 * Create new store in the platform
 */

import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useCreateStoreMutation } from '../../services/stores';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';
import type {CreateStoreData } from '../../types/store';

interface FormErrors {
  name?: string;
  slug?: string;
  domain?: string;
  admin_name?: string;
  admin_phone?: string;
  admin_password?: string;
}

const NewStorePage = () => {
  const navigate = useNavigate();
  const [createStore, { isLoading }] = useCreateStoreMutation();

  const [formData, setFormData] = useState<CreateStoreData>({
    name: '',
    slug: '',
    domain: '',
    status: 'active',
    currency: 'USD',
    timezone: 'UTC',
    language: 'en',
    admin_name: '',
    admin_phone: '',
    admin_email: '',
    admin_password: '',
  });

  const [errors, setErrors] = useState<FormErrors>({});
  const [alert, setAlert] = useState<{variant: 'success' | 'error', title: string, message: string} | null>(null);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    
    setFormData(prev => ({ ...prev, [name]: value }));

    if (errors[name as keyof FormErrors]) {
      setErrors(prev => ({ ...prev, [name]: undefined }));
    }
  };

  const generateSlug = (name: string) => {
    return name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
  };

  const handleNameBlur = () => {
    if (formData.name && !formData.slug) {
      setFormData(prev => ({ ...prev, slug: generateSlug(prev.name) }));
    }
  };

  const validate = (): boolean => {
    const newErrors: FormErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Store name is required';
    }
    if (!formData.slug.trim()) {
      newErrors.slug = 'Slug is required';
    } else if (!/^[a-z0-9-]+$/.test(formData.slug)) {
      newErrors.slug = 'Slug can only contain lowercase letters, numbers, and hyphens';
    }
    if (!formData.admin_name.trim()) {
      newErrors.admin_name = 'Store admin name is required';
    }
    if (!formData.admin_phone.trim()) {
      newErrors.admin_phone = 'Store admin phone is required';
    } else if (!/^\+[1-9]\d{1,14}$/.test(formData.admin_phone)) {
      newErrors.admin_phone = 'Store admin phone must be in E.164 format (e.g., +12025551234)';
    }
    if (!formData.admin_password.trim()) {
      newErrors.admin_password = 'Store admin password is required';
    } else if (formData.admin_password.length < 8) {
      newErrors.admin_password = 'Store admin password must be at least 8 characters';
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
      await createStore(formData).unwrap();
      
      setAlert({
        variant: 'success',
        title: 'Store Created',
        message: 'Store has been successfully created.'
      });

      setTimeout(() => {
        navigate('/stores');
      }, 1500);
    } catch (error: any) {
      setAlert({
        variant: 'error',
        title: 'Creation Failed',
        message: error?.data?.message || 'Failed to create store. Please try again.'
      });
    }
  };

  return (
    <div className="p-6">
      {alert && (
        <div className="mb-4">
          <Alert variant={alert.variant} title={alert.title} message={alert.message} />
        </div>
      )}

      <div className="mb-6">
        <Button variant="ghost" onClick={() => navigate('/stores')} className="mb-3">
          ← Back to Stores
        </Button>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Add New Store</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Create a new store in the platform
        </p>
      </div>

      <form onSubmit={handleSubmit} className="max-w-2xl rounded-lg bg-white shadow dark:bg-boxdark p-6">
        <div className="space-y-6">
          {/* Store Information */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Store Information</h2>
            <div className="space-y-4">
              {/* Store Name */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Store Name <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  onBlur={handleNameBlur}
                  className={`w-full rounded-lg border ${
                    errors.name ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.name && (
                  <p className="mt-1 text-sm text-danger">{errors.name}</p>
                )}
              </div>

              {/* Slug */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Slug <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  name="slug"
                  value={formData.slug}
                  onChange={handleChange}
                  className={`w-full rounded-lg border ${
                    errors.slug ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.slug && (
                  <p className="mt-1 text-sm text-danger">{errors.slug}</p>
                )}
                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                  URL-friendly identifier (lowercase, numbers, hyphens only)
                </p>
              </div>

              {/* Domain */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Domain (Optional)
                </label>
                <input
                  type="text"
                  name="domain"
                  value={formData.domain}
                  onChange={handleChange}
                  placeholder="example.com"
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                />
              </div>
            </div>
          </div>

          {/* Settings */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Settings</h2>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Currency
                </label>
                <select
                  name="currency"
                  value={formData.currency}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                >
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                  <option value="GBP">GBP</option>
                  <option value="INR">INR</option>
                </select>
              </div>
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Timezone
                </label>
                <select
                  name="timezone"
                  value={formData.timezone}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                >
                  <option value="UTC">UTC</option>
                  <option value="America/New_York">America/New_York</option>
                  <option value="Europe/London">Europe/London</option>
                  <option value="Asia/Kolkata">Asia/Kolkata</option>
                </select>
              </div>
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Language
                </label>
                <select
                  name="language"
                  value={formData.language}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                >
                  <option value="en">English</option>
                  <option value="es">Spanish</option>
                  <option value="fr">French</option>
                  <option value="de">German</option>
                </select>
              </div>
            </div>
          </div>

          {/* Store Admin Account */}
          <div>
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Store Admin Account</h2>
            <div className="space-y-4">
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Store Admin Name <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  name="admin_name"
                  value={formData.admin_name}
                  onChange={handleChange}
                  className={`w-full rounded-lg border ${
                    errors.admin_name ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.admin_name && <p className="mt-1 text-sm text-danger">{errors.admin_name}</p>}
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Store Admin Phone <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  name="admin_phone"
                  value={formData.admin_phone}
                  onChange={handleChange}
                  placeholder="+12025551234"
                  className={`w-full rounded-lg border ${
                    errors.admin_phone ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.admin_phone && <p className="mt-1 text-sm text-danger">{errors.admin_phone}</p>}
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Store Admin Email (Optional)
                </label>
                <input
                  type="email"
                  name="admin_email"
                  value={formData.admin_email || ''}
                  onChange={handleChange}
                  placeholder="admin@store.com"
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Store Admin Password <span className="text-danger">*</span>
                </label>
                <input
                  type="password"
                  name="admin_password"
                  value={formData.admin_password}
                  onChange={handleChange}
                  className={`w-full rounded-lg border ${
                    errors.admin_password ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 px-4 text-dark dark:text-white focus:border-primary focus:outline-none`}
                />
                {errors.admin_password && <p className="mt-1 text-sm text-danger">{errors.admin_password}</p>}
              </div>
            </div>
          </div>

          {/* Actions */}
          <div className="flex gap-3 pt-4 border-t border-stroke dark:border-strokedark">
            <Button
              type="button"
              variant="secondary"
              onClick={() => navigate('/stores')}
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="primary"
              disabled={isLoading}
            >
              {isLoading ? 'Creating...' : 'Create Store'}
            </Button>
          </div>
        </div>
      </form>
    </div>
  );
};

export default NewStorePage;
