// src/app/models/index.ts

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'customer' | 'admin';
}

export interface AuthResponse {
  token: string;
  user: User;
}

export interface Service {
  id: number;
  name: string;
  description: string;
  icon: string;
  prefix: string;
  waiting_count: number;
}

export interface Ticket {
  id: number;
  ticket_number: string;
  status: 'waiting' | 'called' | 'serving' | 'completed' | 'cancelled';
  position: number;
  estimated_wait: number;
  service_id: number;
  service_name: string;
  service_icon: string;
  created_at: string;
  called_at: string | null;
}

export interface QueueInfo {
  service: Service;
  waiting_count: number;
  currently_serving: string | null;
  estimated_wait: number;
}

export interface AdminTicket {
  id: number;
  ticket_number: string;
  status: string;
  created_at: string;
  called_at: string | null;
  cliente_nome: string;
}

export interface Stats {
  date: string;
  service_id: number;
  total: number;
  completed: number;
  waiting: number;
  called: number;
  cancelled: number;
  avg_wait_minutes: number;
}

export interface ApiResponse<T> {
  success: boolean;
  message: string;
  data: T;
  errors?: Record<string, string>;
}
