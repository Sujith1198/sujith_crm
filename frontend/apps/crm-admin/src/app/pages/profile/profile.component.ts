import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { AuthService, UserService, ToastService } from '@crm/services';
import { User } from '@crm/models';

/**
 * ProfileComponent — Lets the current user update their own profile and password.
 */
@Component({
  selector: 'app-profile',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './profile.component.html',
})
export class ProfileComponent implements OnInit {
  private fb          = inject(FormBuilder);
  private authService = inject(AuthService);
  private userService = inject(UserService);
  private toast       = inject(ToastService);

  user         = signal<User | null>(this.authService.getUser());
  submitting   = signal(false);
  pwSubmitting = signal(false);
  showPw       = signal(false);

  profileForm = this.fb.nonNullable.group({
    name:     ['', [Validators.required, Validators.minLength(2)]],
    phone:    [''],
    timezone: ['UTC'],
  });

  passwordForm = this.fb.nonNullable.group({
    password:              ['', [Validators.required, Validators.minLength(8)]],
    password_confirmation: ['', Validators.required],
  });

  readonly timezones = Intl.supportedValuesOf('timeZone');

  ngOnInit(): void {
    const u = this.user();
    if (u) {
      this.profileForm.patchValue({ name: u.name, phone: u.phone ?? '', timezone: u.timezone });
    }
  }

  saveProfile(): void {
    if (this.profileForm.invalid) { this.profileForm.markAllAsTouched(); return; }
    this.submitting.set(true);
    this.userService.updateProfile(this.profileForm.getRawValue()).subscribe({
      next: (res) => {
        this.authService.saveUser(res.data);
        this.user.set(res.data);
        this.toast.success('Profile Updated', 'Your information has been saved.');
        this.submitting.set(false);
      },
      error: (err) => {
        this.toast.error('Error', err.error?.message ?? 'Failed to update profile.');
        this.submitting.set(false);
      },
    });
  }

  changePassword(): void {
    if (this.passwordForm.invalid) { this.passwordForm.markAllAsTouched(); return; }
    const { password, password_confirmation } = this.passwordForm.getRawValue();
    if (password !== password_confirmation) { this.toast.error('Mismatch', 'Passwords do not match.'); return; }
    this.pwSubmitting.set(true);
    this.userService.updateProfile({ password, password_confirmation }).subscribe({
      next: () => {
        this.toast.success('Password Changed', 'Your password has been updated.');
        this.passwordForm.reset();
        this.pwSubmitting.set(false);
      },
      error: (err) => {
        this.toast.error('Error', err.error?.message ?? 'Failed to change password.');
        this.pwSubmitting.set(false);
      },
    });
  }
}
