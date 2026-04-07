const PendingOrdersPage = () => {
  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Pending Orders</h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">Orders awaiting processing</p>
      </div>
      
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <p className="text-gray-700 dark:text-gray-300">
          Pending orders view will be implemented here.
        </p>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-4">
          Features: Quick actions, batch processing, priority sorting.
        </p>
      </div>
    </div>
  );
};

export default PendingOrdersPage;
