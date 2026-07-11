import { Component, signal, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { ReactiveFormsModule, FormBuilder, Validators, AbstractControl, ValidationErrors } from '@angular/forms';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { ToastService } from '@crm/services';
import { environment } from '@env/environment';

/**
 * Validator: password === confirmPassword
 */
function passwordMatchValidator(control: AbstractControl): ValidationErrors | null {
  const pw  = control.get('password')?.value;
  const cpw = control.get('password_confirmation')?.value;
  return pw && cpw && pw !== cpw ? { passwordMismatch: true } : null;
}

/**
 * RegisterComponent
 * Public self-registration form. On success, redirects to login.
 */
@Component({
  selector: 'app-register',
  standalone: true,
  imports: [ReactiveFormsModule, CommonModule, RouterLink],
  templateUrl: './register.component.html',
})
export class RegisterComponent {
  private fb    = inject(FormBuilder);
  private http  = inject(HttpClient);
  private toast = inject(ToastService);
  private router = inject(Router);

  form = this.fb.nonNullable.group(
    {
      name:                  ['', [Validators.required, Validators.minLength(2)]],
      email:                 ['', [Validators.required, Validators.email]],
      password:              ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: passwordMatchValidator }
  );

  loading      = signal(false);
  showPassword = signal(false);
  showConfirm  = signal(false);
  errorMsg     = signal('');

  get name()     { return this.form.controls.name; }
  get email()    { return this.form.controls.email; }
  get password() { return this.form.controls.password; }
  get confirm()  { return this.form.controls.password_confirmation; }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.errorMsg.set('');

    this.http.post(`${environment.apiUrl}/register`, this.form.getRawValue()).subscribe({
      next: () => {
        this.toast.success('Account Created!', 'You can now log in with your credentials.');
        this.router.navigateByUrl('/auth/login');
      },
      error: (err) => {
        this.errorMsg.set(err.error?.message || 'Registration failed. Please try again.');
        this.loading.set(false);
      },
    });
  }
}
