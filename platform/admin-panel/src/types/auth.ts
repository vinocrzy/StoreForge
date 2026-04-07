export interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  status: string;
}

export interface Store {
  id: number;
  name: string;
  slug?: string;
  role?: string;
  domain?: string;
  status?: 'active' | 'inactive' | 'suspended';
  created_at?: string;
  updated_at?: string;
}

export interface Role {
  id: number;
  name: string;
  permissions: Permission[];
}

export interface Permission {
  id: number;
  name: string;
}

export interface LoginRequest {
  login: string; // Phone or email
  password: string;
}

export interface LoginResponse {
  user: User;
  token: string;
  stores: Store[];  // Backend returns array of stores
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}