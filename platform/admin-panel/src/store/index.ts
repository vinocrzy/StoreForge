import { configureStore } from '@reduxjs/toolkit';
import authReducer from './authSlice';
import { authApi } from '../services/auth';
import { productsApi } from '../services/products';
import { ordersApi } from '../services/orders';
import { customersApi } from '../services/customers';
import { storesApi } from '../services/stores';

export const store = configureStore({
  reducer: {
    auth: authReducer,
    [authApi.reducerPath]: authApi.reducer,
    [productsApi.reducerPath]: productsApi.reducer,
    [ordersApi.reducerPath]: ordersApi.reducer,
    [customersApi.reducerPath]: customersApi.reducer,
    [storesApi.reducerPath]: storesApi.reducer,
  },
  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware()
      .concat(authApi.middleware)
      .concat(productsApi.middleware)
      .concat(ordersApi.middleware)
      .concat(customersApi.middleware)
      .concat(storesApi.middleware),
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
