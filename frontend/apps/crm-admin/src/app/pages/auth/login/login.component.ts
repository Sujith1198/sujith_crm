import { Component, signal, inject } from '@angular/core';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { AuthService, ToastService } from '@crm/services';

/**
 * LoginComponent
 * JWT login form with email/password validation, loading state, and error handling.
 * On success: stores token + user → navigates to returnUrl or /dashboard.
 */
@Component({
  selector: 'app-login',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule, RouterLink],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  private fb          = inject(FormBuilder);
  private authService = inject(AuthService);
  private toast       = inject(ToastService);
  private router      = inject(Router);
  private route       = inject(ActivatedRoute);

  form = this.fb.nonNullable.group({
    email:    ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(6)]],
  });

  loading       = signal(false);
  showPassword  = signal(false);
  errorMessage  = signal('');
  currentYear   = new Date().getFullYear();

  get email()    { return this.form.controls.email; }
  get password() { return this.form.controls.password; }

  togglePassword(): void { this.showPassword.update(v => !v); }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.errorMessage.set('');

    this.authService.login(this.form.getRawValue()).subscribe({
      next: (res) => {
        this.authService.saveToken(res.data.token);
        this.authService.saveUser(res.data.user);

        this.toast.success('Welcome back!', `Hello, ${res.data.user.name}`);

        const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl') || '/dashboard';
        this.router.navigateByUrl(returnUrl);
      },
      error: (err) => {
        this.loading.set(false);
        this.errorMessage.set(err.error?.message || 'Invalid credentials. Please try again.');
      },
      complete: () => this.loading.set(false),
    });
  }
}
