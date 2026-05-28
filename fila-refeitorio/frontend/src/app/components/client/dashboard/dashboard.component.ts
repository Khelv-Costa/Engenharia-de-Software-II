// src/app/components/client/dashboard/dashboard.component.ts
import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Subscription } from 'rxjs';
import { AuthService } from '../../../services/auth.service';
import { QueueService } from '../../../services/queue.service';
import { Service, Ticket } from '../../../models';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss'],
})
export class DashboardComponent implements OnInit, OnDestroy {
  services: Service[] = [];
  activeTicket: Ticket | null = null;
  loadingServices = true;
  loadingTicket   = true;
  creatingTicket  = false;
  cancellingTicket = false;
  notification    = '';
  notifType: 'success' | 'error' | '' = '';

  private subs = new Subscription();

  constructor(public auth: AuthService, private queue: QueueService) {}

  ngOnInit(): void {
    this.loadServices();
    this.startTicketPolling();
  }

  ngOnDestroy(): void { this.subs.unsubscribe(); }

  private loadServices(): void {
    this.subs.add(
      this.queue.getServices().subscribe({
        next: res => { this.services = res.data ?? []; this.loadingServices = false; },
        error: ()  => { this.loadingServices = false; this.showNotif('Erro ao carregar serviços.', 'error'); },
      })
    );
  }

  private startTicketPolling(): void {
    this.subs.add(
      this.queue.pollMyTicket().subscribe({
        next: res => {
          const prev = this.activeTicket?.status;
          this.activeTicket  = res.data ?? null;
          this.loadingTicket = false;

          // Notifica quando chamado
          if (res.data?.status === 'called' && prev === 'waiting') {
            this.showNotif(`🔔 A sua senha ${res.data.ticket_number} foi chamada!`, 'success');
          }
        },
        error: () => { this.loadingTicket = false; },
      })
    );
  }

  createTicket(serviceId: number): void {
    if (this.creatingTicket) return;
    this.creatingTicket = true;

    this.subs.add(
      this.queue.createTicket(serviceId).subscribe({
        next: res => {
          this.creatingTicket = false;
          if (res.success) {
            this.showNotif(`Senha ${res.data.ticket_number} criada! Posição: ${res.data.position}`, 'success');
            this.activeTicket = res.data as any;
          } else {
            this.showNotif(res.message, 'error');
          }
        },
        error: err => {
          this.creatingTicket = false;
          this.showNotif(err.error?.message ?? 'Erro ao criar senha.', 'error');
        },
      })
    );
  }

  cancelTicket(): void {
    if (!this.activeTicket || this.cancellingTicket) return;
    if (!confirm('Confirma o cancelamento da sua senha?')) return;

    this.cancellingTicket = true;
    this.subs.add(
      this.queue.cancelTicket(this.activeTicket.id).subscribe({
        next: res => {
          this.cancellingTicket = false;
          if (res.success) {
            this.activeTicket = null;
            this.showNotif('Senha cancelada com sucesso.', 'success');
          }
        },
        error: err => {
          this.cancellingTicket = false;
          this.showNotif(err.error?.message ?? 'Erro ao cancelar.', 'error');
        },
      })
    );
  }

  private showNotif(msg: string, type: 'success' | 'error'): void {
    this.notification = msg;
    this.notifType    = type;
    setTimeout(() => { this.notification = ''; this.notifType = ''; }, 5000);
  }

  statusLabel(s: string): string {
    return { waiting: 'Em espera', called: 'Chamado!', serving: 'Em atendimento', completed: 'Concluído', cancelled: 'Cancelado' }[s] ?? s;
  }
}
