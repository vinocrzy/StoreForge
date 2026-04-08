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
}

const NewStorePage = () => {
  const navigate = useNavigate();
  const [createStore, { isLoading }] = useCreateStoreMutation();

  const [formData, setFormData] = useState<CreateStoreData>({
    name: '',
    slug: '',
    domain: '',
    settings: {
      currency: 'USD',
      timezone: 'UTC',
      language: 'en',
    },
  });

  const [errors, setErrors] = useState<FormErrors>({});
  const [alert, setAlert] = useState<{variant: 'success' | 'error', title: string, message: string} | null>(null);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    
    if (name.startsWith('settings.')) {
      const settingKey = name.split('.')[1];
      setFormData(prev => ({
        ...prev,
        settings: {
          ...prev.settings!,
          [settingKey]: value,
        },
      }));
    } else {
      setFormData(prev => ({ ...prev, [name]: value }));
    }

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
                  name="settings.currency"
                  value={formData.settings?.currency}
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
                  name="settings.timezone"
                  value={formData.settings?.timezone}
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
                  name="settings.language"
                  value={formData.settings?.language}
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
