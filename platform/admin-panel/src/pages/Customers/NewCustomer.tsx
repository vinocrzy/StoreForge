const NewCustomerPage = () => {
  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Add New Customer</h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">Register a new customer account</p>
      </div>
      
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <p className="text-gray-700 dark:text-gray-300">
          Customer registration form will be implemented here.
        </p>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-4">
          Features: Contact info, addresses, preferences, initial order.
        </p>
      </div>
    </div>
  );
};

export default NewCustomerPage;
