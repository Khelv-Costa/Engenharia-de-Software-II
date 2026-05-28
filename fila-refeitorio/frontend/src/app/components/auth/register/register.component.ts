// src/app/components/auth/register/register.component.ts
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  template: `
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <div class="logo">🍽️</div>
      <h1>Criar Conta</h1>
      <p>Sistema de Fila do Refeitório</p>
    </div>

    <form [formGroup]="form" (ngSubmit)="submit()" novalidate>

      <div class="form-group">
        <label>Nome completo</label>
        <input type="text" formControlName="name" placeholder="O seu nome"
               [class.error]="hasError('name','required') || hasError('name','minlength')" />
        <span class="hint error" *ngIf="hasError('name','required')">Nome é obrigatório.</span>
        <span class="hint error" *ngIf="hasError('name','minlength')">Mínimo 2 caracteres.</span>
      </div>

      <div class="form-group">
        <label>E-mail</label>
        <input type="email" formControlName="email" placeholder="seu@email.com"
               [class.error]="hasError('email','required') || hasError('email','email')" />
        <span class="hint error" *ngIf="hasError('email','required')">E-mail é obrigatório.</span>
        <span class="hint error" *ngIf="hasError('email','email')">E-mail inválido.</span>
      </div>

      <div class="form-group">
        <label>Senha</label>
        <input type="password" formControlName="password" placeholder="Mínimo 6 caracteres"
               [class.error]="hasError('password','required') || hasError('password','minlength')" />
        <span class="hint error" *ngIf="hasError('password','required')">Senha é obrigatória.</span>
        <span class="hint error" *ngIf="hasError('password','minlength')">Mínimo 6 caracteres.</span>
      </div>

      <div class="alert error" *ngIf="error">{{ error }}</div>
      <div class="alert success" *ngIf="success">{{ success }}</div>

      <button type="submit" class="btn-primary" [disabled]="loading">
        <span *ngIf="!loading">Criar Conta</span>
        <span *ngIf="loading" class="spinner"></span>
      </button>
    </form>

    <p class="auth-footer">Já tem conta? <a routerLink="/login">Entrar</a></p>
  </div>
</div>
  `,
  styleUrls: ['../login/login.component.scss'],
})
export class RegisterComponent {
  form: FormGroup;
  loading = false;
  error   = '';
  success = '';

  constructor(private fb: FormBuilder, private auth: AuthService, private router: Router) {
    this.form = this.fb.group({
      name:     ['', [Validators.required, Validators.minLength(2)]],
      email:    ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
    });
  }

  submit(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.loading = true; this.error = '';

    const { name, email, password } = this.form.value;
    this.auth.register(name, email, password).subscribe({
      next: res => {
        this.loading = false;
        if (res.success) this.router.navigate(['/dashboard']);
        else this.error = res.message;
      },
      error: err => {
        this.loading = false;
        this.error   = err.error?.message ?? 'Erro ao criar conta.';
      },
    });
  }

  field(n: string) { return this.form.get(n)!; }
  hasError(n: string, e: string) { return this.field(n).hasError(e) && this.field(n).touched; }
}
