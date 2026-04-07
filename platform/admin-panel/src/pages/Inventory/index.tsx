const InventoryPage = () => {
  return (
    <div className="p-6">
      <div className="mb-6">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">Stock Levels</h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">Monitor inventory across all warehouses</p>
      </div>
      
      <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <p className="text-gray-700 dark:text-gray-300">
          Inventory overview will be implemented here.
        </p>
        <p className="text-sm text-gray-500 dark:text-gray-400 mt-4">
          Features: Stock alerts, reorder points, multi-warehouse tracking.
        </p>
      </div>
    </div>
  );
};

export default InventoryPage;
