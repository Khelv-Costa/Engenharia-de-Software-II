// src/app/services/queue.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, interval, switchMap, startWith, shareReplay } from 'rxjs';
import { environment } from '../../environments/environment';
import { ApiResponse, Service, QueueInfo, Ticket, AdminTicket, Stats } from '../models';

@Injectable({ providedIn: 'root' })
export class QueueService {
  private readonly API = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // ── Público ──────────────────────────────────────────────
  getServices(): Observable<ApiResponse<Service[]>> {
    return this.http.get<ApiResponse<Service[]>>(`${this.API}/services`);
  }

  getQueueInfo(serviceId: number): Observable<ApiResponse<QueueInfo>> {
    return this.http.get<ApiResponse<QueueInfo>>(`${this.API}/queue/${serviceId}`);
  }

  // Polling automático da fila pública
  pollQueueInfo(serviceId: number): Observable<ApiResponse<QueueInfo>> {
    return interval(environment.pollIntervalClient).pipe(
      startWith(0),
      switchMap(() => this.getQueueInfo(serviceId)),
      shareReplay(1)
    );
  }

  // ── Cliente ──────────────────────────────────────────────
  createTicket(serviceId: number): Observable<ApiResponse<Ticket>> {
    return this.http.post<ApiResponse<Ticket>>(`${this.API}/tickets`, { service_id: serviceId });
  }

  getMyTicket(): Observable<ApiResponse<Ticket | null>> {
    return this.http.get<ApiResponse<Ticket | null>>(`${this.API}/tickets/my`);
  }

  pollMyTicket(): Observable<ApiResponse<Ticket | null>> {
    return interval(environment.pollIntervalClient).pipe(
      startWith(0),
      switchMap(() => this.getMyTicket()),
      shareReplay(1)
    );
  }

  cancelTicket(id: number): Observable<ApiResponse<null>> {
    return this.http.delete<ApiResponse<null>>(`${this.API}/tickets/${id}`);
  }

  // ── Admin ────────────────────────────────────────────────
  getAdminServices(): Observable<ApiResponse<any[]>> {
    return this.http.get<ApiResponse<any[]>>(`${this.API}/admin/services`);
  }

  getAdminQueue(serviceId: number): Observable<ApiResponse<AdminTicket[]>> {
    return this.http.get<ApiResponse<AdminTicket[]>>(`${this.API}/admin/queue/${serviceId}`);
  }

  pollAdminQueue(serviceId: number): Observable<ApiResponse<AdminTicket[]>> {
    return interval(environment.pollIntervalAdmin).pipe(
      startWith(0),
      switchMap(() => this.getAdminQueue(serviceId)),
      shareReplay(1)
    );
  }

  callNext(serviceId: number): Observable<ApiResponse<any>> {
    return this.http.post<ApiResponse<any>>(`${this.API}/admin/call/${serviceId}`, {});
  }

  completeTicket(ticketId: number): Observable<ApiResponse<null>> {
    return this.http.post<ApiResponse<null>>(`${this.API}/admin/complete/${ticketId}`, {});
  }

  getStats(serviceId: number): Observable<ApiResponse<Stats>> {
    return this.http.get<ApiResponse<Stats>>(`${this.API}/admin/stats/${serviceId}`);
  }
}
