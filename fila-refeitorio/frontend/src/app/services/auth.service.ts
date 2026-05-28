// src/app/services/auth.service.ts
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../environments/environment';
import { AuthResponse, User, ApiResponse } from '../models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly API = environment.apiUrl;
  private readonly TOKEN_KEY = 'fr_token';
  private readonly USER_KEY  = 'fr_user';

  private userSubject = new BehaviorSubject<User | null>(this.storedUser());
  user$ = this.userSubject.asObservable();

  constructor(private http: HttpClient, private router: Router) {}

  // ── Estado atual ─────────────────────────────────────────
  get currentUser(): User | null { return this.userSubject.value; }
  get token(): string | null     { return localStorage.getItem(this.TOKEN_KEY); }
  get isLoggedIn(): boolean      { return !!this.token && !!this.currentUser; }
  get isAdmin(): boolean         { return this.currentUser?.role === 'admin'; }

  // ── Registo ──────────────────────────────────────────────
  register(name: string, email: string, password: string): Observable<ApiResponse<AuthResponse>> {
    return this.http.post<ApiResponse<AuthResponse>>(`${this.API}/auth/register`, { name, email, password })
      .pipe(tap(res => { if (res.success) this.storeSession(res.data); }));
  }

  // ── Login ────────────────────────────────────────────────
  login(email: string, password: string): Observable<ApiResponse<AuthResponse>> {
    return this.http.post<ApiResponse<AuthResponse>>(`${this.API}/auth/login`, { email, password })
      .pipe(tap(res => { if (res.success) this.storeSession(res.data); }));
  }

  // ── Logout ───────────────────────────────────────────────
  logout(): void {
    localStorage.removeItem(this.TOKEN_KEY);
    localStorage.removeItem(this.USER_KEY);
    this.userSubject.next(null);
    this.router.navigate(['/login']);
  }

  // ── Internos ─────────────────────────────────────────────
  private storeSession(auth: AuthResponse): void {
    localStorage.setItem(this.TOKEN_KEY, auth.token);
    localStorage.setItem(this.USER_KEY,  JSON.stringify(auth.user));
    this.userSubject.next(auth.user);
  }

  private storedUser(): User | null {
    try { return JSON.parse(localStorage.getItem(this.USER_KEY) ?? 'null'); }
    catch { return null; }
  }
}
