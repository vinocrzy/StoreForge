import { createSlice, type PayloadAction } from '@reduxjs/toolkit';
import type { User, Store } from '../types/auth';

interface AuthState {
  user: User | null;
  token: string | null;
  currentStore: Store | null;
  isAuthenticated: boolean;
}

const loadStoreFromStorage = (): Store | null => {
  const storeId = localStorage.getItem('store_id');
  const storeName = localStorage.getItem('store_name');
  
  if (storeId) {
    return {
      id: parseInt(storeId),
      name: storeName || 'Store',
      domain: '',
      status: 'active',
      created_at: '',
      updated_at: '',
    };
  }
  return null;
};

const initialState: AuthState = {
  user: null,
  token: localStorage.getItem('auth_token'),
  currentStore: loadStoreFromStorage(),
  isAuthenticated: !!localStorage.getItem('auth_token'),
};

const authSlice = createSlice({
  name: 'auth',
  initialState,
  reducers: {
    setCredentials: (state, action: PayloadAction<{ user: User; token: string; store?: Store }>) => {
      state.user = action.payload.user;
      state.token = action.payload.token;
      state.currentStore = action.payload.store || null;
      state.isAuthenticated = true;
      
      // Save to localStorage
      localStorage.setItem('auth_token', action.payload.token);
      if (action.payload.store) {
        localStorage.setItem('store_id', action.payload.store.id.toString());
        localStorage.setItem('store_name', action.payload.store.name);
      }
    },
    setCurrentStore: (state, action: PayloadAction<Store>) => {
      state.currentStore = action.payload;
      localStorage.setItem('store_id', action.payload.id.toString());
      localStorage.setItem('store_name', action.payload.name);
    },
    logout: (state) => {
      state.user = null;
      state.token = null;
      state.currentStore = null;
      state.isAuthenticated = false;
      
      // Clear localStorage
      localStorage.removeItem('auth_token');
      localStorage.removeItem('store_id');
      localStorage.removeItem('store_name');
    },
  },
});

export const { setCredentials, setCurrentStore, logout } = authSlice.actions;
export default authSlice.reducer;
