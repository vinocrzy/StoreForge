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
import StoresPage from "./pages/Stores";
import NewStorePage from "./pages/Stores/NewStore";
import StoreDetailsPage from "./pages/Stores/StoreDetails";
import InventoryPage from "./pages/Inventory";
import WarehousesPage from "./pages/Inventory/Warehouses";
import StockMovementsPage from "./pages/Inventory/StockMovements";
import StockAlertsPage from "./pages/Inventory/StockAlerts";
import StoreSettingsPage from "./pages/Settings/StoreSettings";
import CouponsPage from "./pages/Coupons";
import NewCouponPage from "./pages/Coupons/NewCoupon";
import EditCouponPage from "./pages/Coupons/EditCoupon";
import ReviewsPage from "./pages/Reviews";
import ReviewDetailPage from "./pages/Reviews/ReviewDetail";

// Protected Route Component
function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { token } = useAppSelector((state) => state.auth);
  
  if (!token) {
    return <Navigate to="/signin" replace />;
  }
  
  return <>{children}</>;
}

function SuperAdminOnlyRoute({ children }: { children: React.ReactNode }) {
  const { user } = useAppSelector((state) => state.auth);

  if (!user?.is_super_admin) {
    return <Navigate to="/" replace />;
  }

  return <>{children}</>;
}

function StoreAdminOnlyRoute({ children }: { children: React.ReactNode }) {
  const { user } = useAppSelector((state) => state.auth);

  if (user?.is_super_admin) {
    return <Navigate to="/stores" replace />;
  }

  return <>{children}</>;
}

function RoleAwareHomeRoute() {
  const { user } = useAppSelector((state) => state.auth);

  if (user?.is_super_admin) {
    return <Navigate to="/stores" replace />;
  }

  return <Home />;
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
            <Route index path="/" element={<RoleAwareHomeRoute />} />

            {/* Products */}
            <Route path="/products" element={<StoreAdminOnlyRoute><ProductsPage /></StoreAdminOnlyRoute>} />
            <Route path="/products/new" element={<StoreAdminOnlyRoute><NewProductPage /></StoreAdminOnlyRoute>} />
            <Route path="/products/:id" element={<StoreAdminOnlyRoute><EditProductPage /></StoreAdminOnlyRoute>} />
            <Route path="/categories" element={<StoreAdminOnlyRoute><CategoriesPage /></StoreAdminOnlyRoute>} />

            {/* Orders */}
            <Route path="/orders" element={<StoreAdminOnlyRoute><OrdersPage /></StoreAdminOnlyRoute>} />
            <Route path="/orders/:id" element={<StoreAdminOnlyRoute><OrderDetailsPage /></StoreAdminOnlyRoute>} />
            <Route path="/orders/pending" element={<StoreAdminOnlyRoute><PendingOrdersPage /></StoreAdminOnlyRoute>} />
            <Route path="/orders/completed" element={<StoreAdminOnlyRoute><CompletedOrdersPage /></StoreAdminOnlyRoute>} />

            {/* Customers */}
            <Route path="/customers" element={<StoreAdminOnlyRoute><CustomersPage /></StoreAdminOnlyRoute>} />
            <Route path="/customers/new" element={<StoreAdminOnlyRoute><NewCustomerPage /></StoreAdminOnlyRoute>} />
            <Route path="/customers/:id/edit" element={<StoreAdminOnlyRoute><EditCustomerPage /></StoreAdminOnlyRoute>} />
            <Route path="/customers/:id" element={<StoreAdminOnlyRoute><CustomerDetailsPage /></StoreAdminOnlyRoute>} />

            {/* Stores (Super Admin) */}
            <Route path="/stores" element={<SuperAdminOnlyRoute><StoresPage /></SuperAdminOnlyRoute>} />
            <Route path="/stores/new" element={<SuperAdminOnlyRoute><NewStorePage /></SuperAdminOnlyRoute>} />
            <Route path="/stores/:id" element={<SuperAdminOnlyRoute><StoreDetailsPage /></SuperAdminOnlyRoute>} />

            {/* Inventory */}
            <Route path="/inventory" element={<StoreAdminOnlyRoute><InventoryPage /></StoreAdminOnlyRoute>} />
            <Route path="/warehouses" element={<StoreAdminOnlyRoute><WarehousesPage /></StoreAdminOnlyRoute>} />
            <Route path="/inventory/movements" element={<StoreAdminOnlyRoute><StockMovementsPage /></StoreAdminOnlyRoute>} />
            <Route path="/inventory/alerts" element={<StoreAdminOnlyRoute><StockAlertsPage /></StoreAdminOnlyRoute>} />

            {/* Coupons */}
            <Route path="/coupons" element={<StoreAdminOnlyRoute><CouponsPage /></StoreAdminOnlyRoute>} />
            <Route path="/coupons/new" element={<StoreAdminOnlyRoute><NewCouponPage /></StoreAdminOnlyRoute>} />
            <Route path="/coupons/:id/edit" element={<StoreAdminOnlyRoute><EditCouponPage /></StoreAdminOnlyRoute>} />

            {/* Reviews */}
            <Route path="/reviews" element={<StoreAdminOnlyRoute><ReviewsPage /></StoreAdminOnlyRoute>} />
            <Route path="/reviews/:id" element={<StoreAdminOnlyRoute><ReviewDetailPage /></StoreAdminOnlyRoute>} />

            {/* Settings */}
            <Route path="/settings/store" element={<StoreAdminOnlyRoute><StoreSettingsPage /></StoreAdminOnlyRoute>} />
            <Route path="/profile" element={<StoreAdminOnlyRoute><UserProfiles /></StoreAdminOnlyRoute>} />
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
