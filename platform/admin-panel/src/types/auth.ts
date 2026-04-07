export interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  stores: Store[];
  roles: Role[];
  permissions: string[];
}

export interface Store {
  id: number;
  name: string;
  domain: string;
  status: 'active' | 'inactive' | 'suspended';
  created_at: string;
  updated_at: string;
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
  store?: Store;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}