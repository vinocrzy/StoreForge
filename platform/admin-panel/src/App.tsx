import { BrowserRouter as Router, Routes, Route, Navigate } from "react-router-dom";
import { useAppSelector } from "./store/hooks";
import SignIn from "./pages/AuthPages/SignIn";
import NotFound from "./pages/OtherPage/NotFound";
import AppLayout from "./layout/AppLayout";
import { ScrollToTop } from "./components/common/ScrollToTop";
import Home from "./pages/Dashboard/Home";
import UserProfiles from "./pages/UserProfiles";

// E-commerce Pages
import ProductsPage from "./pages/Products";
import NewProductPage from "./pages/Products/NewProduct";
import EditProductPage from "./pages/Products/EditProduct";
import CategoriesPage from "./pages/Categories";
import OrdersPage from "./pages/Orders";
import OrderDetailsPage from "./pages/Orders/OrderDetails";
import PendingOrdersPage from "./pages/Orders/PendingOrders";
import CompletedOrdersPage from "./pages/Orders/CompletedOrders";
import CustomersPage from "./pages/Customers";
import NewCustomerPage from "./pages/Customers/NewCustomer";
import EditCustomerPage from "./pages/Customers/EditCustomer";
import CustomerDetailsPage from "./pages/Customers/CustomerDetails";
import InventoryPage from "./pages/Inventory";
import WarehousesPage from "./pages/Inventory/Warehouses";
import StockMovementsPage from "./pages/Inventory/StockMovements";
import StoreSettingsPage from "./pages/Settings/StoreSettings";

// Protected Route Component
function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { token } = useAppSelector((state) => state.auth);
  
  if (!token) {
    return <Navigate to="/signin" replace />;
  }
  
  return <>{children}</>;
}

function App() {
  return (
    <>
      <Router>
        <ScrollToTop />
        <Routes>
          {/* Dashboard Layout - Protected */}
          <Route element={
            <ProtectedRoute>
              <AppLayout />
            </ProtectedRoute>
          }>
            {/* Dashboard */}
            <Route index path="/" element={<Home />} />

            {/* Products */}
            <Route path="/products" element={<ProductsPage />} />
            <Route path="/products/new" element={<NewProductPage />} />
            <Route path="/products/:id" element={<EditProductPage />} />
            <Route path="/categories" element={<CategoriesPage />} />

            {/* Orders */}
            <Route path="/orders" element={<OrdersPage />} />
            <Route path="/orders/:id" element={<OrderDetailsPage />} />
            <Route path="/orders/pending" element={<PendingOrdersPage />} />
            <Route path="/orders/completed" element={<CompletedOrdersPage />} />

            {/* Customers */}
            <Route path="/customers" element={<CustomersPage />} />
            <Route path="/customers/new" element={<NewCustomerPage />} />
            <Route path="/customers/:id/edit" element={<EditCustomerPage />} />
            <Route path="/customers/:id" element={<CustomerDetailsPage />} />

            {/* Inventory */}
            <Route path="/inventory" element={<InventoryPage />} />
            <Route path="/warehouses" element={<WarehousesPage />} />
            <Route path="/inventory/movements" element={<StockMovementsPage />} />

            {/* Settings */}
            <Route path="/settings/store" element={<StoreSettingsPage />} />
            <Route path="/profile" element={<UserProfiles />} />
          </Route>

          {/* Auth Routes - Public */}
          <Route path="/signin" element={<SignIn />} />

          {/* Fallback Route */}
          <Route path="*" element={<NotFound />} />
        </Routes>
      </Router>
    </>
  );
}

export default App;
