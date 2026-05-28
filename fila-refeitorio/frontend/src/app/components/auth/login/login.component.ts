// src/app/components/auth/login/login.component.ts
import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../services/auth.service';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss'],
})
export class LoginComponent {
  form: FormGroup;
  loading = false;
  error   = '';
  showPass = false;

  constructor(private fb: FormBuilder, private auth: AuthService, private router: Router) {
    this.form = this.fb.group({
      email:    ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]],
    });
  }

  submit(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }
    this.loading = true;
    this.error   = '';

    const { email, password } = this.form.value;
    this.auth.login(email, password).subscribe({
      next: res => {
        this.loading = false;
        if (res.success) {
          this.router.navigate([res.data.user.role === 'admin' ? '/admin' : '/dashboard']);
        } else {
          this.error = res.message;
        }
      },
      error: err => {
        this.loading = false;
        this.error   = err.error?.message ?? 'Erro ao fazer login.';
      },
    });
  }

  field(name: string) { return this.form.get(name)!; }
  hasError(name: string, err: string) { return this.field(name).hasError(err) && this.field(name).touched; }
}
