// src/app/components/admin/admin-panel/admin-panel.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subscription, switchMap } from 'rxjs';
import { AuthService } from '../../../services/auth.service';
import { QueueService } from '../../../services/queue.service';
import { AdminTicket, Stats } from '../../../models';

interface AdminService { id: number; name: string; icon: string; prefix: string; waiting: number; }

@Component({
  selector: 'app-admin-panel',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './admin-panel.component.html',
  styleUrls: ['./admin-panel.component.scss'],
})
export class AdminPanelComponent implements OnInit, OnDestroy {
  services: AdminService[] = [];
  selectedServiceId = 0;
  tickets: AdminTicket[] = [];
  stats: Stats | null = null;
  loadingServices = true;
  loadingQueue    = true;
  processingId    = 0;     // id do ticket em processamento
  notification    = '';
  notifType: 'success' | 'error' | '' = '';
  view: 'queue' | 'stats' = 'queue';

  private subs = new Subscription();

  constructor(public auth: AuthService, private queue: QueueService) {}

  ngOnInit(): void { this.loadServices(); }
  ngOnDestroy(): void { this.subs.unsubscribe(); }

  private loadServices(): void {
    this.subs.add(
      this.queue.getAdminServices().subscribe({
        next: res => {
          this.services = res.data ?? [];
          this.loadingServices = false;
          if (this.services.length > 0) this.selectService(this.services[0].id);
        },
        error: () => { this.loadingServices = false; this.showNotif('Erro ao carregar serviços.', 'error'); },
      })
    );
  }

  selectService(id: number): void {
    this.selectedServiceId = id;
    this.loadingQueue = true;
    this.subs.unsubscribe();
    this.subs = new Subscription();

    this.subs.add(
      this.queue.pollAdminQueue(id).subscribe({
        next: res => { this.tickets = res.data ?? []; this.loadingQueue = false; },
        error: ()  => { this.loadingQueue = false; },
      })
    );

    if (this.view === 'stats') this.loadStats();
  }

  callNext(): void {
    this.subs.add(
      this.queue.callNext(this.selectedServiceId).subscribe({
        next: res => {
          if (res.success) this.showNotif(`${res.data.ticket_number} chamado!`, 'success');
          else this.showNotif(res.message, 'error');
        },
        error: err => this.showNotif(err.error?.message ?? 'Fila vazia.', 'error'),
      })
    );
  }

  completeTicket(ticketId: number): void {
    this.processingId = ticketId;
    this.subs.add(
      this.queue.completeTicket(ticketId).subscribe({
        next: res => {
          this.processingId = 0;
          if (res.success) this.showNotif('Atendimento concluído.', 'success');
          else this.showNotif(res.message, 'error');
        },
        error: err => {
          this.processingId = 0;
          this.showNotif(err.error?.message ?? 'Erro ao completar.', 'error');
        },
      })
    );
  }

  loadStats(): void {
    if (!this.selectedServiceId) return;
    this.subs.add(
      this.queue.getStats(this.selectedServiceId).subscribe({
        next: res => { this.stats = res.data; },
        error: ()  => { this.showNotif('Erro ao carregar estatísticas.', 'error'); },
      })
    );
  }

  setView(v: 'queue' | 'stats'): void {
    this.view = v;
    if (v === 'stats') this.loadStats();
  }

  get selectedService(): AdminService | undefined {
    return this.services.find(s => s.id === this.selectedServiceId);
  }

  get waitingTickets():  AdminTicket[] { return this.tickets.filter(t => t.status === 'waiting');  }
  get calledTickets():   AdminTicket[] { return this.tickets.filter(t => t.status === 'called');   }

  statusLabel(s: string): string {
    return { waiting: 'Espera', called: 'Chamado', serving: 'Em atendimento' }[s] ?? s;
  }

  private showNotif(msg: string, type: 'success' | 'error'): void {
    this.notification = msg;
    this.notifType    = type;
    setTimeout(() => { this.notification = ''; this.notifType = ''; }, 4000);
  }
}
