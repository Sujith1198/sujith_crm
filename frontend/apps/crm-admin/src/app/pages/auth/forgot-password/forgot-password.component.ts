import { Component, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ToastService } from '@crm/services';
import { environment } from '@env/environment';

/**
 * ForgotPasswordComponent
 * Requests a password reset link by email.
 */
@Component({
  selector: 'app-forgot-password',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './forgot-password.component.html',
})
export class ForgotPasswordComponent {
  private fb    = inject(FormBuilder);
  private http  = inject(HttpClient);
  private toast = inject(ToastService);

  form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
  });

  loading   = signal(false);
  success   = signal(false);
  errorMsg  = signal('');

  get email() { return this.form.controls.email; }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.errorMsg.set('');

    this.http.post(`${environment.apiUrl}/forgot-password`, this.form.getRawValue()).subscribe({
      next: () => {
        this.success.set(true);
        this.toast.success('Reset Email Sent', 'Check your inbox for instructions.');
        this.loading.set(false);
      },
      error: (err) => {
        this.errorMsg.set(err.error?.message || 'Failed to send password reset email.');
        this.loading.set(false);
      },
    });
  }
}
