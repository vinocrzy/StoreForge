import { useState } from 'react';
import { useNavigate } from 'react-router';
import { useGetCustomersQuery } from '../../services/customers';
import Button from '../../components/ui/button/Button';
import Badge from '../../components/ui/badge/Badge';
import Alert from '../../components/ui/alert/Alert';
import type { CustomerFilters, CustomerStatus } from '../../types/customer';

const CustomersPage = () => {
  const navigate = useNavigate();
  const [filters, setFilters] = useState<CustomerFilters>({
    page: 1,
    per_page: 20,
    search: '',
    status: undefined,
    sort_by: 'created_at',
    sort_order: 'desc',
  });

  const { data: customersData, isLoading, error } = useGetCustomersQuery(filters);
  const customers = customersData?.data || [];
  const meta = customersData?.meta;

  // Handle filter changes
  const handleSearchChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    setFilters(prev => ({ ...prev, search: event.target.value, page: 1 }));
  };

  const handleStatusChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    const value = event.target.value;
    setFilters(prev => ({ 
      ...prev, 
      status: value ? value as CustomerStatus : undefined, 
      page: 1 
    }));
  };

  const handlePageChange = (newPage: number) => {
    setFilters(prev => ({ ...prev, page: newPage }));
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleViewCustomer = (id: number) => {
    navigate(`/customers/${id}`);
  };

  const handleEditCustomer = (id: number) => {
    navigate(`/customers/${id}/edit`);
  };

  // Helper function to get status badge color
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

  if (error) {
    return (
      <div className="p-6">
        <Alert 
          variant="error" 
          title="Error Loading Customers"
          message="Unable to load customers. Please try again later." 
        />
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Customers</h1>
          <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
            View and manage your customer base
          </p>
        </div>
        <Button
          variant="primary"
          onClick={() => navigate('/customers/new')}
        >
          + Add Customer
        </Button>
      </div>

      {/* Filters */}
      <div className="mb-6 grid grid-cols-1 gap-4 rounded-lg bg-white p-4 shadow dark:bg-boxdark md:grid-cols-3">
        {/* Search */}
        <div className="md:col-span-2">
          <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Search
          </label>
          <input
            type="text"
            value={filters.search}
            onChange={handleSearchChange}
            placeholder="Search by name, email, or phone..."
            className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-2 px-3 text-dark dark:text-white focus:border-primary focus:outline-none"
          />
        </div>

        {/* Status Filter */}
        <div>
          <label className="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Status
          </label>
          <select
            value={filters.status || ''}
            onChange={handleStatusChange}
            className="w-full rounded-lg border border-stroke dark:border-strokedark bg-transparent py-2 px-3 text-dark dark:text-white focus:border-primary focus:outline-none"
          >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="banned">Banned</option>
          </select>
        </div>
      </div>

      {/* Results Count */}
      {meta && (
        <div className="mb-4 text-sm text-gray-600 dark:text-gray-400">
          Showing {((meta.current_page - 1) * meta.per_page) + 1} to {Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total} customers
        </div>
      )}

      {/* Customers Table */}
      <div className="overflow-hidden rounded-lg bg-white shadow dark:bg-boxdark">
        {isLoading ? (
          <div className="p-12 text-center text-gray-500 dark:text-gray-400">
            Loading customers...
          </div>
        ) : customers.length === 0 ? (
          <div className="p-12 text-center">
            <p className="text-gray-500 dark:text-gray-400">No customers found</p>
            <Button
              variant="primary"
              onClick={() => navigate('/customers/new')}
              className="mt-4"
            >
              Add First Customer
            </Button>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b border-stroke dark:border-strokedark bg-gray-50 dark:bg-meta-4">
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Customer
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Contact
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Status
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Verification
                  </th>
                  <th className="px-6 py-4 text-left text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Joined
                  </th>
                  <th className="px-6 py-4 text-right text-sm font-semibold text-gray-700 dark:text-gray-300">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody>
                {customers.map((customer: any) => (
                  <tr
                    key={customer.id}
                    className="border-b border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-meta-4 cursor-pointer"
                    onClick={() => handleViewCustomer(customer.id)}
                  >
                    {/* Customer Name */}
                    <td className="px-6 py-4">
                      <div>
                        <p className="font-medium text-gray-900 dark:text-white">
                          {customer.first_name} {customer.last_name}
                        </p>
                        {customer.last_login_at && (
                          <p className="text-sm text-gray-500 dark:text-gray-400">
                            Last login: {new Date(customer.last_login_at).toLocaleDateString()}
                          </p>
                        )}
                      </div>
                    </td>

                    {/* Contact */}
                    <td className="px-6 py-4">
                      <div className="space-y-1">
                        <p className="text-sm text-gray-700 dark:text-gray-300">
                          {customer.phone}
                        </p>
                        {customer.email && (
                          <p className="text-sm text-gray-500 dark:text-gray-400">
                            {customer.email}
                          </p>
                        )}
                      </div>
                    </td>

                    {/* Status */}
                    <td className="px-6 py-4">
                      <Badge color={getStatusBadgeColor(customer.status)} size="sm">
                        {customer.status.charAt(0).toUpperCase() + customer.status.slice(1)}
                      </Badge>
                    </td>

                    {/* Verification */}
                    <td className="px-6 py-4">
                      <div className="flex gap-2">
                        {customer.phone_verified_at ? (
                          <Badge color="success" size="sm">Phone ✓</Badge>
                        ) : (
                          <Badge color="warning" size="sm">Phone</Badge>
                        )}
                        {customer.email && (
                          customer.email_verified_at ? (
                            <Badge color="success" size="sm">Email ✓</Badge>
                          ) : (
                            <Badge color="warning" size="sm">Email</Badge>
                          )
                        )}
                      </div>
                    </td>

                    {/* Joined */}
                    <td className="px-6 py-4 text-gray-700 dark:text-gray-300">
                      <p className="text-sm">
                        {new Date(customer.created_at).toLocaleDateString('en-US', {
                          month: 'short',
                          day: 'numeric',
                          year: 'numeric'
                        })}
                      </p>
                    </td>

                    {/* Actions */}
                    <td className="px-6 py-4 text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleViewCustomer(customer.id)}
                        >
                          View
                        </Button>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleEditCustomer(customer.id)}
                        >
                          Edit
                        </Button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Pagination */}
      {meta && meta.last_page > 1 && (
        <div className="mt-6 flex items-center justify-between">
          <div className="text-sm text-gray-600 dark:text-gray-400">
            Page {meta.current_page} of {meta.last_page}
          </div>
          <div className="flex gap-2">
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handlePageChange(meta.current_page - 1)}
              disabled={meta.current_page === 1}
            >
              Previous
            </Button>
            <Button
              variant="secondary"
              size="sm"
              onClick={() => handlePageChange(meta.current_page + 1)}
              disabled={meta.current_page === meta.last_page}
            >
              Next
            </Button>
          </div>
        </div>
      )}
    </div>
  );
};

export default CustomersPage;
