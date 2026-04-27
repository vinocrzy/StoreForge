import { configureStore } from '@reduxjs/toolkit';
import authReducer from './authSlice';
import { authApi } from '../services/auth';
import { productsApi } from '../services/products';
import { ordersApi } from '../services/orders';
import { customersApi } from '../services/customers';
import { storesApi } from '../services/stores';
import { dashboardApi } from '../services/dashboard';
import { inventoryApi } from '../services/inventory';
import { settingsApi } from '../services/settings';
import { profileApi } from '../services/profile';
import { couponsApi } from '../services/coupons';
import { reviewsApi } from '../services/reviews';
import { paymentsApi } from '../services/payments';
import { analyticsApi } from '../services/analytics';
import { shippingApi } from '../services/shipping';
import { returnsApi } from '../services/returns';
import { settingsExtApi } from '../services/settingsExt';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    [authApi.reducerPath]: authApi.reducer,
    [productsApi.reducerPath]: productsApi.reducer,
    [ordersApi.reducerPath]: ordersApi.reducer,
    [customersApi.reducerPath]: customersApi.reducer,
    [storesApi.reducerPath]: storesApi.reducer,
    [dashboardApi.reducerPath]: dashboardApi.reducer,
    [inventoryApi.reducerPath]: inventoryApi.reducer,
    [settingsApi.reducerPath]: settingsApi.reducer,
    [profileApi.reducerPath]: profileApi.reducer,
    [couponsApi.reducerPath]: couponsApi.reducer,
    [reviewsApi.reducerPath]: reviewsApi.reducer,
    [paymentsApi.reducerPath]: paymentsApi.reducer,
    [analyticsApi.reducerPath]: analyticsApi.reducer,
    [shippingApi.reducerPath]: shippingApi.reducer,
    [returnsApi.reducerPath]: returnsApi.reducer,
    [settingsExtApi.reducerPath]: settingsExtApi.reducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware()
      .concat(authApi.middleware)
      .concat(productsApi.middleware)
      .concat(ordersApi.middleware)
      .concat(customersApi.middleware)
      .concat(storesApi.middleware)
      .concat(dashboardApi.middleware)
      .concat(inventoryApi.middleware)
      .concat(settingsApi.middleware)
      .concat(profileApi.middleware)
      .concat(couponsApi.middleware)
      .concat(reviewsApi.middleware)
      .concat(paymentsApi.middleware)
      .concat(analyticsApi.middleware)
      .concat(shippingApi.middleware)
      .concat(returnsApi.middleware)
      .concat(settingsExtApi.middleware),
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
