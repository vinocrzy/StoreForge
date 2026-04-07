import { useState } from 'react';
import { 
  useGetCategoriesQuery, 
  useCreateCategoryMutation, 
  useUpdateCategoryMutation, 
  useDeleteCategoryMutation 
} from '../../services/products';
import Button from '../../components/ui/button/Button';
import Alert from '../../components/ui/alert/Alert';
import Badge from '../../components/ui/badge/Badge';
import type { Category } from '../../types/product';

interface CategoryFormData {
  name: string;
  slug: string;
  description?: string;
  parent_id?: number;
}

const CategoriesPage = () => {
  const { data: categoriesData, isLoading, error } = useGetCategoriesQuery();
  const [createCategory, { isLoading: isCreating }] = useCreateCategoryMutation();
  const [updateCategory, { isLoading: isUpdating }] = useUpdateCategoryMutation();
  const [deleteCategory] = useDeleteCategoryMutation();

  const [showForm, setShowForm] = useState(false);
  const [editingCategory, setEditingCategory] = useState<Category | null>(null);
  const [deleteConfirm, setDeleteConfirm] = useState<Category | null>(null);
  const [alert, setAlert] = useState<{type: 'success' | 'error', message: string} | null>(null);

  const [formData, setFormData] = useState<CategoryFormData>({
    name: '',
    slug: '',
    description: '',
    parent_id: undefined,
  });

  const categories = categoriesData?.data || [];

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: name === 'parent_id' ? (value ? parseInt(value) : undefined) : value,
    }));

    // Auto-generate slug from name
    if (name === 'name') {
      const slug = value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      setFormData(prev => ({ ...prev, slug }));
    }
  };

  const resetForm = () => {
    setFormData({
      name: '',
      slug: '',
      description: '',
      parent_id: undefined,
    });
    setEditingCategory(null);
    setShowForm(false);
  };

  const handleEdit = (category: Category) => {
    setEditingCategory(category);
    setFormData({
      name: category.name,
      slug: category.slug,
      description: category.description || '',
      parent_id: category.parent_id ?? undefined,
    });
    setShowForm(true);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!formData.name.trim()) {
      setAlert({ type: 'error', message: 'Category name is required' });
      return;
    }

    try {
      if (editingCategory) {
        await updateCategory({ id: editingCategory.id, data: formData }).unwrap();
        setAlert({ type: 'success', message: 'Category updated successfully!' });
      } else {
        await createCategory(formData).unwrap();
        setAlert({ type: 'success', message: 'Category created successfully!' });
      }
      resetForm();
      setTimeout(() => setAlert(null), 3000);
    } catch (error: any) {
      console.error('Failed to save category:', error);
      setAlert({ 
        type: 'error', 
        message: error?.data?.message || 'Failed to save category' 
      });
    }
  };

  const handleDelete = async (category: Category) => {
    try {
      await deleteCategory(category.id).unwrap();
      setAlert({ type: 'success', message: 'Category deleted successfully!' });
      setDeleteConfirm(null);
      setTimeout(() => setAlert(null), 3000);
    } catch (error: any) {
      console.error('Failed to delete category:', error);
      setAlert({ 
        type: 'error', 
        message: error?.data?.message || 'Failed to delete category' 
      });
      setDeleteConfirm(null);
    }
  };

  if (isLoading) {
    return (
      <div className="p-6">
        <div className="flex justify-center items-center min-h-screen">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6">
        <Alert 
          variant="error" 
          title="Error" 
          message="Failed to load categories. Please try again."
        />
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* Page Header */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Categories
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Organize your products with categories
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

      {/* Add/Edit Form */}
      {showForm && (
        <div className="mb-6 bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-6">
          <div className="flex items-center justify-between mb-6">
            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
              {editingCategory ? 'Edit Category' : 'Add New Category'}
            </h2>
            <Button variant="ghost" onClick={resetForm}>
              Cancel
            </Button>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {/* Name */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Category Name <span className="text-danger">*</span>
                </label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                  placeholder="Enter category name"
                  required
                />
              </div>

              {/* Slug */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Slug
                </label>
                <input
                  type="text"
                  name="slug"
                  value={formData.slug}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                  placeholder="auto-generated"
                />
              </div>

              {/* Parent Category */}
              <div>
                <label className="mb-2 block text-sm font-medium text-gray-900 dark:text-white">
                  Parent Category
                </label>
                <select
                  name="parent_id"
                  value={formData.parent_id || ''}
                  onChange={handleChange}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                >
                  <option value="">None (Top Level)</option>
                  {categories
                    .filter(cat => editingCategory ? cat.id !== editingCategory.id : true)
                    .map(category => (
                      <option key={category.id} value={category.id}>
                        {category.name}
                      </option>
                    ))}
                </select>
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
                  rows={3}
                  className="w-full rounded-lg border border-stroke dark:border-strokedark bg-white dark:bg-boxdark py-3 px-4.5 text-dark dark:text-white focus:border-primary focus:outline-none"
                  placeholder="Optional description"
                />
              </div>
            </div>

            <div className="flex gap-3 justify-end">
              <Button type="button" variant="ghost" onClick={resetForm}>
                Cancel
              </Button>
              <Button type="submit" variant="primary" disabled={isCreating || isUpdating}>
                {isCreating || isUpdating ? 'Saving...' : editingCategory ? 'Update Category' : 'Create Category'}
              </Button>
            </div>
          </form>
        </div>
      )}

      {/* Add Button */}
      {!showForm && (
        <div className="mb-6">
          <Button variant="primary" onClick={() => setShowForm(true)}>
            + Add Category
          </Button>
        </div>
      )}

      {/* Categories Table */}
      <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50 dark:bg-boxdark-2 border-b border-stroke dark:border-strokedark">
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                  Name
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                  Slug
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                  Parent
                </th>
                <th className="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                  Products
                </th>
                <th className="px-6 py-4 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-stroke dark:divide-strokedark">
              {categories.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-12 text-center">
                    <p className="text-gray-500 dark:text-gray-400">
                      No categories found. Click "Add Category" to create your first category.
                    </p>
                  </td>
                </tr>
              ) : (
                categories.map((category) => (
                  <tr key={category.id} className="hover:bg-gray-50 dark:hover:bg-boxdark-2">
                    <td className="px-6 py-4">
                      <div className="font-medium text-gray-900 dark:text-white">
                        {category.name}
                      </div>
                      {category.description && (
                        <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                          {category.description}
                        </div>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      <code className="text-sm text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-boxdark-2 px-2 py-1 rounded">
                        {category.slug}
                      </code>
                    </td>
                    <td className="px-6 py-4">
                      {category.parent_id ? (
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {categories.find(c => c.id === category.parent_id)?.name || 'Unknown'}
                        </span>
                      ) : (
                        <span className="text-sm text-gray-400 dark:text-gray-500">
                          Top Level
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      <Badge color="primary">
                        {category.products_count || 0} products
                      </Badge>
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button 
                          variant="ghost" 
                          size="sm"
                          onClick={() => handleEdit(category)}
                        >
                          Edit
                        </Button>
                        <Button 
                          variant="danger" 
                          size="sm"
                          onClick={() => setDeleteConfirm(category)}
                        >
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
      </div>

      {/* Delete Confirmation Modal */}
      {deleteConfirm && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white dark:bg-boxdark rounded-lg shadow-xl max-w-md w-full m-4 p-6">
            <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
              Delete Category
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Are you sure you want to delete "{deleteConfirm.name}"? This action cannot be undone.
            </p>
            <div className="flex gap-3 justify-end">
              <Button variant="ghost" onClick={() => setDeleteConfirm(null)}>
                Cancel
              </Button>
              <Button variant="danger" onClick={() => handleDelete(deleteConfirm)}>
                Delete
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default CategoriesPage;
