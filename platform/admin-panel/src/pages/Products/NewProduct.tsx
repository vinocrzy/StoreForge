import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useCreateProductMutation, useGetCategoriesQuery } from '../../services/products';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';
import ImageUpload, { type ImageFile } from '../../components/ui/image-upload/ImageUpload';
import type { CreateProductData } from '../../types/product';
import { getCurrencySymbol, getStoreCurrency } from '../../utils/currency';

interface FormErrors {
  name?: string;
  sku?: string;
  price?: string;
  stock_quantity?: string;
  description?: string;
}

const NewProductPage = () => {
  const navigate = useNavigate();
  const [createProduct, { isLoading }] = useCreateProductMutation();
  const { data: categoriesData } = useGetCategoriesQuery();
  const currencySymbol = getCurrencySymbol(getStoreCurrency());

  const [formData, setFormData] = useState<CreateProductData>({
    name: '',
    sku: '',
    description: '',
    short_description: '',
    price: 0,
    compare_price: undefined,
    cost_price: undefined,
    stock_quantity: 0,
    low_stock_threshold: 10,
    weight: undefined,
    is_featured: false,
    is_taxable: true,
    status: 'draft',
    category_ids: [],
  });

  const [errors, setErrors] = useState<FormErrors>({});
  const [alert, setAlert] = useState<{type: 'success' | 'error', message: string} | null>(null);
  const [images, setImages] = useState<ImageFile[]>([]);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value, type } = e.target;
    
    if (type === 'checkbox') {
      const checked = (e.target as HTMLInputElement).checked;
      setFormData(prev => ({ ...prev, [name]: checked }));
    } else if (type === 'number') {
      setFormData(prev => ({ ...prev, [name]: value === '' ? undefined : parseFloat(value) }));
    } else {
      setFormData(prev => ({ ...prev, [name]: value }));
    }

    // Clear error for this field
    if (errors[name as keyof FormErrors]) {
      setErrors(prev => ({ ...prev, [name]: undefined }));
    }
  };

  const handleCategoryChange = (categoryId: number, checked: boolean) => {
    setFormData(prev => ({
      ...prev,
      category_ids: checked
        ? [...(prev.category_ids || []), categoryId]
        : (prev.category_ids || []).filter(id => id !== categoryId),
    }));
  };

  const validate = (): boolean => {
    const newErrors: FormErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = 'Product name is required';
    }

    if (!formData.sku.trim()) {
      newErrors.sku = 'SKU is required';
    }

    if (formData.price <= 0) {
      newErrors.price = 'Price must be greater than 0';
    }

    if (formData.stock_quantity < 0) {
      newErrors.stock_quantity = 'Stock quantity cannot be negative';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!validate()) {
      setAlert({ type: 'error', message: 'Please fix the errors below' });
      window.scrollTo({ top: 0, behavior: 'smooth' });
      return;
    }

    try {
      await createProduct(formData).unwrap();
      setAlert({ type: 'success', message: 'Product created successfully!' });
      setTimeout(() => {
        navigate('/products');
      }, 1500);
    } catch (error: any) {
      console.error('Failed to create product:', error);
      setAlert({ 
        type: 'error', 
        message: error?.data?.message || 'Failed to create product. Please try again.' 
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  };

  const categories = categoriesData?.data || [];

  return (
    <div className="p-6">
      {/* Page Header */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Add New Product
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Create a new product in your catalog
        </p>
      </div>

      {/* Alert */}
      {alert && (
        <div className="mb-6">
          <Alert 
            variant={alert.type} 
            title={alert.type === 'success' ? 'Success' : 'Error'} 
            message={alert.message}
          />
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Info Card */}
        <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Basic Information
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Product Name */}
            <div className="md:col-span-2">
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Product Name <span className="text-danger">*</span>
              </label>
              <input
                type="text"
                name="name"
                value={formData.name}
                onChange={handleChange}
                className={`w-full rounded-lg border ${
                  errors.name ? 'border-danger' : 'border-stroke dark:border-strokedark'
                } bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none`}
                placeholder="Enter product name"
              />
              {errors.name && (
                <p className="mt-1 text-sm text-danger">{errors.name}</p>
              )}
            </div>

            {/* SKU */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                SKU <span className="text-danger">*</span>
              </label>
              <input
                type="text"
                name="sku"
                value={formData.sku}
                onChange={handleChange}
                className={`w-full rounded-lg border ${
                  errors.sku ? 'border-danger' : 'border-stroke dark:border-strokedark'
                } bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none`}
                placeholder="PROD-001"
              />
              {errors.sku && (
                <p className="mt-1 text-sm text-danger">{errors.sku}</p>
              )}
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
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
              >
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="archived">Archived</option>
              </select>
            </div>

            {/* Short Description */}
            <div className="md:col-span-2">
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Short Description
              </label>
              <textarea
                name="short_description"
                value={formData.short_description}
                onChange={handleChange}
                rows={2}
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                placeholder="Brief description for product listings"
              />
            </div>

            {/* Description */}
            <div className="md:col-span-2">
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Description
              </label>
              <textarea
                name="description"
                value={formData.description}
                onChange={handleChange}
                rows={4}
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                placeholder="Detailed product description"
              />
            </div>
          </div>
        </div>

        {/* Pricing Card */}
        <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Pricing
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Price */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Price <span className="text-danger">*</span>
              </label>
              <div className="relative">
                <span className="absolute left-4 top-3.5 text-gray-500">{currencySymbol}</span>
                <input
                  type="number"
                  name="price"
                  value={formData.price}
                  onChange={handleChange}
                  step="0.01"
                  min="0"
                  className={`w-full rounded-lg border ${
                    errors.price ? 'border-danger' : 'border-stroke dark:border-strokedark'
                  } bg-white dark:bg-boxdark py-3 pl-8 pr-4.5 text-dark dark:text-white focus:border-primary focus:outline-none`}
                  placeholder="0.00"
                />
              </div>
              {errors.price && (
                <p className="mt-1 text-sm text-danger">{errors.price}</p>
              )}
            </div>

            {/* Compare Price */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Compare at Price
              </label>
              <div className="relative">
                <span className="absolute left-4 top-3.5 text-gray-500">{currencySymbol}</span>
                <input
                  type="number"
                  name="compare_price"
                  value={formData.compare_price || ''}
                  onChange={handleChange}
                  step="0.01"
                  min="0"
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 pl-8 pr-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                  placeholder="0.00"
                />
              </div>
              <p className="mt-1 text-xs text-gray-500">Original price for comparison</p>
            </div>

            {/* Cost Price */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Cost Price
              </label>
              <div className="relative">
                <span className="absolute left-4 top-3.5 text-gray-500">{currencySymbol}</span>
                <input
                  type="number"
                  name="cost_price"
                  value={formData.cost_price || ''}
                  onChange={handleChange}
                  step="0.01"
                  min="0"
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 pl-8 pr-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                  placeholder="0.00"
                />
              </div>
              <p className="mt-1 text-xs text-gray-500">Your cost for this product</p>
            </div>
          </div>

          {/* Tax Checkbox */}
          <div className="mt-4">
            <label className="flex items-center gap-2 cursor-pointer">
              <input
                type="checkbox"
                name="is_taxable"
                checked={formData.is_taxable}
                onChange={handleChange}
                className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
              />
              <span className="text-sm text-gray-700 dark:text-gray-300">
                Charge tax on this product
              </span>
            </label>
          </div>
        </div>

        {/* Inventory Card */}
        <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Inventory
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Stock Quantity */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Stock Quantity <span className="text-danger">*</span>
              </label>
              <input
                type="number"
                name="stock_quantity"
                value={formData.stock_quantity}
                onChange={handleChange}
                min="0"
                className={`w-full rounded-lg border ${
                  errors.stock_quantity ? 'border-danger' : 'border-stroke dark:border-strokedark'
                } bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none`}
                placeholder="0"
              />
              {errors.stock_quantity && (
                <p className="mt-1 text-sm text-danger">{errors.stock_quantity}</p>
              )}
            </div>

            {/* Low Stock Threshold */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Low Stock Threshold
              </label>
              <input
                type="number"
                name="low_stock_threshold"
                value={formData.low_stock_threshold}
                onChange={handleChange}
                min="0"
                className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                placeholder="10"
              />
              <p className="mt-1 text-xs text-gray-500">Alert when stock falls below this number</p>
            </div>
          </div>
        </div>

        {/* Categories Card */}
        <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Categories
          </h2>

          <div className="space-y-2 max-h-64 overflow-y-auto">
            {categories.length === 0 ? (
              <p className="text-gray-500 dark:text-gray-400">No categories available</p>
            ) : (
              categories.map((category) => (
                <label key={category.id} className="flex items-center gap-2 cursor-pointer p-2 hover:bg-gray-50 dark:hover:bg-boxdark-2 rounded">
                  <input
                    type="checkbox"
                    checked={formData.category_ids?.includes(category.id)}
                    onChange={(e) => handleCategoryChange(category.id, e.target.checked)}
                    className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
                  />
                  <span className="text-sm text-gray-700 dark:text-gray-300">
                    {category.name}
                  </span>
                </label>
              ))
            )}
          </div>
        </div>

        {/* Images Card */}
        <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Product Images
          </h2>
          <ImageUpload 
            images={images} 
            onImagesChange={setImages}
            maxImages={10}
            maxSizeMB={5}
          />
        </div>

        {/* Additional Settings Card */}
        <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">
            Additional Settings
          </h2>

          <div className="space-y-4">
            {/* Weight */}
            <div>
              <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                Weight (kg)
              </label>
              <input
                type="number"
                name="weight"
                value={formData.weight || ''}
                onChange={handleChange}
                step="0.01"
                min="0"
                className="w-full max-w-xs rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                placeholder="0.00"
              />
            </div>

            {/* Featured Checkbox */}
            <div>
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  name="is_featured"
                  checked={formData.is_featured}
                  onChange={handleChange}
                  className="w-5 h-5 rounded border-gray-300 text-primary focus:ring-primary"
                />
                <span className="text-sm text-gray-700 dark:text-gray-300">
                  Mark as featured product
                </span>
              </label>
            </div>
          </div>
        </div>

        {/* Form Actions */}
        <div className="flex gap-3 justify-end bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <Button
            type="button"
            variant="ghost"
            onClick={() => navigate('/products')}
            disabled={isLoading}
          >
            Cancel
          </Button>
          <Button
            type="submit"
            variant="primary"
            disabled={isLoading}
          >
            {isLoading ? 'Creating...' : 'Create Product'}
          </Button>
        </div>
      </form>
    </div>
  );
};

export default NewProductPage;
