import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useGetProductsQuery, useDeleteProductMutation } from '../../services/products';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import type { ProductFilters } from '../../types/product';
import { formatPrice, getStoreCurrency } from '../../utils/currency';

const ProductsPage = () => {
  const navigate = useNavigate();
  const [filters, setFilters] = useState<ProductFilters>({
    page: 1,
    per_page: 20,
    search: '',
    status: undefined,
    stock_status: undefined,
  });
  
  const [alert, setAlert] = useState<{type: 'success' | 'error', message: string} | null>(null);
  const [deleteProductId, setDeleteProductId] = useState<number | null>(null);

  const { data: productsData, isLoading, error } = useGetProductsQuery(filters);
  const [deleteProduct, { isLoading: isDeleting }] = useDeleteProductMutation();

  const handleSearch = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFilters((prev) => ({ ...prev, search: e.target.value, page: 1 }));
  };

  const handleStatusFilter = (status: 'active' | 'draft' | 'archived' | undefined) => {
    setFilters((prev) => ({ ...prev, status, page: 1 }));
  };

  const handleStockFilter = (stock_status: 'in_stock' | 'low_stock' | 'out_of_stock' | undefined) => {
    setFilters((prev) => ({ ...prev, stock_status, page: 1 }));
  };

  const handleDelete = async (id: number) => {
    try {
      await deleteProduct(id).unwrap();
      setAlert({ type: 'success', message: 'Product deleted successfully' });
      setDeleteProductId(null);
    } catch (error) {
      setAlert({ type: 'error', message: 'Failed to delete product' });
    }
  };

  const getStatusBadge = (status: string) => {
    const colors: Record<string, 'success' | 'warning' | 'error'> = {
      active: 'success',
      draft: 'warning',
      archived: 'error',
    };
    return <Badge color={colors[status] || 'warning'}>{status}</Badge>;
  };

  const getStockBadge = (quantity: number, threshold: number) => {
    if (quantity === 0) {
      return <Badge color="error">Out of Stock</Badge>;
    } else if (quantity <= threshold) {
      return <Badge color="warning">Low Stock</Badge>;
    }
    return <Badge color="success">In Stock</Badge>;
  };

  if (isLoading) {
    return (
      <div className="p-6">
        <div className="text-center py-12">
          <div className="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-primary border-r-transparent"></div>
          <p className="mt-4 text-gray-600 dark:text-gray-400">Loading products...</p>
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
          message="Failed to load products. Please try again."
        />
      </div>
    );
  }

  const products = productsData?.data || [];
  const meta = productsData?.meta;

  return (
    <div className="p-6">
      {/* Page Header */}
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Products
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Manage your product catalog ({meta?.total || 0} products)
        </p>
      </div>

      {alert && (
        <div className="mb-6">
          <Alert 
            variant={alert.type} 
            title={alert.type === 'success' ? 'Success' : 'Error'} 
            message={alert.message}
          />
        </div>
      )}

      {/* Filters & Actions */}
      <div className="mb-6 bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark p-4">
        <div className="flex flex-col lg:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <input
              type="text"
              placeholder="Search products by name or SKU..."
              value={filters.search}
              onChange={handleSearch}
              className="w-full rounded-lg border border-stroke bg-white py-2.5 px-4 text-dark focus:border-primary focus:outline-none dark:border-strokedark dark:bg-boxdark dark:text-white"
            />
          </div>

          {/* Status Filter */}
          <div className="flex gap-2">
            <Button
              variant={filters.status === undefined ? 'primary' : 'ghost'}
              onClick={() => handleStatusFilter(undefined)}
              size="sm"
            >
              All
            </Button>
            <Button
              variant={filters.status === 'active' ? 'primary' : 'ghost'}
              onClick={() => handleStatusFilter('active')}
              size="sm"
            >
              Active
            </Button>
            <Button
              variant={filters.status === 'draft' ? 'primary' : 'ghost'}
              onClick={() => handleStatusFilter('draft')}
              size="sm"
            >
              Draft
            </Button>
          </div>

          {/* Add Product Button */}
          <Button
            variant="primary"
            onClick={() => navigate('/products/new')}
          >
            + Add Product
          </Button>
        </div>

        {/* Stock Filter */}
        <div className="mt-4 flex gap-2">
          <Button
            variant={filters.stock_status === undefined ? 'primary' : 'ghost'}
            onClick={() => handleStockFilter(undefined)}
            size="sm"
          >
            All Stock
          </Button>
          <Button
            variant={filters.stock_status === 'in_stock' ? 'primary' : 'ghost'}
            onClick={() => handleStockFilter('in_stock')}
            size="sm"
          >
            In Stock
          </Button>
          <Button
            variant={filters.stock_status === 'low_stock' ? 'primary' : 'ghost'}
            onClick={() => handleStockFilter('low_stock')}
            size="sm"
          >
            Low Stock
          </Button>
          <Button
            variant={filters.stock_status === 'out_of_stock' ? 'primary' : 'ghost'}
            onClick={() => handleStockFilter('out_of_stock')}
            size="sm"
          >
            Out of Stock
          </Button>
        </div>
      </div>

      {/* Products Table */}
      <div className="bg-white dark:bg-boxdark rounded-lg shadow border border-stroke dark:border-strokedark overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="bg-gray-50 dark:bg-boxdark-2 border-b border-stroke dark:border-strokedark">
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Product
                </th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  SKU
                </th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Price
                </th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Stock
                </th>
                <th className="px-6 py-4 text-left text-sm font-semibold text-gray-900 dark:text-white">
                  Status
                </th>
                <th className="px-6 py-4 text-right text-sm font-semibold text-gray-900 dark:text-white">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
              {products.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    No products found. Click "Add Product" to create your first product.
                  </td>
                </tr>
              ) : (
                products.map((product) => (
                  <tr
                    key={product.id}
                    className="hover:bg-gray-50 dark:hover:bg-boxdark-2 transition-colors"
                  >
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-3">
                        <div className="h-12 w-12 flex-shrink-0">
                          {product.primary_image?.url ? (
                            <img
                              src={product.primary_image.url}
                              alt={product.name}
                              className="h-12 w-12 rounded-lg object-cover"
                            />
                          ) : (
                            <div className="h-12 w-12 rounded-lg bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                              <span className="text-gray-400 dark:text-gray-500 text-xs">No Image</span>
                            </div>
                          )}
                        </div>
                        <div>
                          <p className="font-medium text-gray-900 dark:text-white">
                            {product.name}
                          </p>
                          {product.categories && product.categories.length > 0 && (
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                              {product.categories.map(c => c.name).join(', ')}
                            </p>
                          )}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                      {product.sku}
                    </td>
                    <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                      {formatPrice(product.price, getStoreCurrency())}
                      {product.compare_price && Number(product.compare_price) > Number(product.price) && (
                        <span className="ml-2 text-sm text-gray-500 line-through">
                          {formatPrice(product.compare_price, getStoreCurrency())}
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <span className="text-gray-700 dark:text-gray-300">
                          {product.stock_quantity}
                        </span>
                        {getStockBadge(product.stock_quantity, product.low_stock_threshold)}
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      {getStatusBadge(product.status)}
                    </td>
                    <td className="px-6 py-4 text-right">
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => navigate(`/products/${product.id}`)}
                          className="text-primary hover:text-primary/80 font-medium text-sm"
                        >
                          Edit
                        </button>
                        <button
                          onClick={() => setDeleteProductId(product.id)}
                          className="text-danger hover:text-danger/80 font-medium text-sm"
                        >
                          Delete
                        </button>
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
          <div className="border-t border-stroke dark:border-strokedark px-6 py-4">
            <div className="flex items-center justify-between">
              <p className="text-sm text-gray-600 dark:text-gray-400">
                Showing {((meta.current_page - 1) * meta.per_page) + 1} to{' '}
                {Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total} products
              </p>
              <div className="flex gap-2">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setFilters(prev => ({ ...prev, page: meta.current_page - 1 }))}
                  disabled={meta.current_page === 1}
                >
                  Previous
                </Button>
                <span className="px-4 py-2 text-sm text-gray-700 dark:text-gray-300">
                  Page {meta.current_page} of {meta.last_page}
                </span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setFilters(prev => ({ ...prev, page: meta.current_page + 1 }))}
                  disabled={meta.current_page >= meta.last_page}
                >
                  Next
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>

      {/* Delete Confirmation Modal */}
      {deleteProductId && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <div className="bg-white dark:bg-boxdark rounded-lg shadow-lg max-w-md w-full p-6">
            <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
              Confirm Delete
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Are you sure you want to delete this product? This action cannot be undone.
            </p>
            <div className="flex gap-3 justify-end">
              <Button
                variant="ghost"
                onClick={() => setDeleteProductId(null)}
                disabled={isDeleting}
              >
                Cancel
              </Button>
              <Button
                variant="danger"
                onClick={() => handleDelete(deleteProductId)}
                disabled={isDeleting}
              >
                {isDeleting ? 'Deleting...' : 'Delete Product'}
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ProductsPage;
