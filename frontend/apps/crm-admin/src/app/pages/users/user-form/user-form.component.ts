import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators, AbstractControl } from '@angular/forms';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { UserService, ToastService } from '@crm/services';

/**
 * UserFormComponent — Shared Create / Edit user form.
 * In edit mode, password field is optional.
 */
@Component({
  selector: 'app-user-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './user-form.component.html',
})
export class UserFormComponent implements OnInit {
  private fb          = inject(FormBuilder);
  private userService = inject(UserService);
  private toast       = inject(ToastService);
  private router      = inject(Router);
  private route       = inject(ActivatedRoute);

  form = this.fb.nonNullable.group({
    name:                  ['', [Validators.required, Validators.minLength(2), Validators.maxLength(255)]],
    email:                 ['', [Validators.required, Validators.email]],
    phone:                 [''],
    role:                  ['user', Validators.required],
    status:                ['active', Validators.required],
    timezone:              ['UTC'],
    password:              ['', [Validators.minLength(8)]],
    password_confirmation: [''],
  });

  editId       = signal<number | null>(null);
  loading      = signal(false);
  submitting   = signal(false);
  showPassword = signal(false);

  readonly timezones = Intl.supportedValuesOf('timeZone');

  get isEdit() { return !!this.editId(); }
  get name()   { return this.form.controls.name; }
  get email()  { return this.form.controls.email; }
  get password(){ return this.form.controls.password; }

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.editId.set(+id);
      this.loadUser(+id);
      // Password not required in edit mode
      this.form.controls.password.clearValidators();
      this.form.controls.password.updateValueAndValidity();
    } else {
      // Password required in create mode
      this.form.controls.password.addValidators(Validators.required);
      this.form.controls.password.updateValueAndValidity();
    }
  }

  loadUser(id: number): void {
    this.loading.set(true);
    this.userService.getById(id).subscribe({
      next: (res) => {
        const u = res.data;
        this.form.patchValue({
          name:     u.name,
          email:    u.email,
          phone:    u.phone ?? '',
          role:     u.roles?.[0] ?? 'user',
          status:   u.status,
          timezone: u.timezone,
        });
        this.loading.set(false);
      },
      error: () => {
        this.toast.error('Error', 'User not found.');
        this.router.navigate(['/users']);
      },
    });
  }

  submit(): void {
    if (this.form.invalid) { this.form.markAllAsTouched(); return; }

    const v = this.form.getRawValue();
    if (v.password && v.password !== v.password_confirmation) {
      this.toast.error('Validation', 'Passwords do not match.');
      return;
    }

    const payload: Record<string, unknown> = {
      name: v.name, email: v.email, phone: v.phone || undefined,
      role: v.role, status: v.status, timezone: v.timezone,
    };
    if (v.password) {
      payload['password'] = v.password;
      payload['password_confirmation'] = v.password_confirmation;
    }

    this.submitting.set(true);
    const request$ = this.isEdit
      ? this.userService.update(this.editId()!, payload as any)
      : this.userService.create(payload as any);

    request$.subscribe({
      next: () => {
        this.toast.success(
          this.isEdit ? 'User Updated' : 'User Created',
          this.isEdit ? 'User account has been updated.' : 'New user account created successfully.',
        );
        this.router.navigate(['/users']);
      },
      error: (err) => {
        this.submitting.set(false);
        const errors = err.error?.errors;
        if (errors) {
          const msg = Object.values(errors).flat().join(' ');
          this.toast.error('Validation Error', msg as string);
        } else {
          this.toast.error('Error', err.error?.message ?? 'Failed to save user.');
        }
      },
    });
  }
}
