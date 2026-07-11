import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ToastService } from '@crm/services';
import { environment } from '@env/environment';

interface SystemSetting {
  id: number;
  setting_key: string;
  setting_value: string;
  display_name: string;
  group_name: string;
}

/**
 * SettingsComponent
 * Admin settings page to update app configs, API configurations, and scheduler options.
 */
@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './settings.component.html',
})
export class SettingsComponent implements OnInit {
  private fb    = inject(FormBuilder);
  private http  = inject(HttpClient);
  private toast = inject(ToastService);

  loading    = signal(true);
  submitting = signal(false);

  form = this.fb.group({
    app_name:                 ['CRM Social Media', Validators.required],
    facebook_client_id:       [''],
    facebook_client_secret:   [''],
    instagram_client_id:      [''],
    instagram_client_secret:  [''],
    scheduler_frequency_mins: [1, [Validators.required, Validators.min(1)]],
    auto_refresh_tokens:      [true],
    enable_notifications:     [true],
  });

  ngOnInit(): void {
    this.loadSettings();
  }

  loadSettings(): void {
    this.loading.set(true);
    this.http.get<{ data: SystemSetting[] }>(`${environment.apiUrl}/settings`).subscribe({
      next: (res) => {
        const settingsMap: Record<string, string> = {};
        res.data.forEach(s => {
          settingsMap[s.setting_key] = s.setting_value;
        });

        this.form.patchValue({
          app_name:                 settingsMap['app_name'] || 'CRM Social Media',
          facebook_client_id:       settingsMap['facebook_client_id'] || '',
          facebook_client_secret:   settingsMap['facebook_client_secret'] || '',
          instagram_client_id:      settingsMap['instagram_client_id'] || '',
          instagram_client_secret:  settingsMap['instagram_client_secret'] || '',
          scheduler_frequency_mins: settingsMap['scheduler_frequency_mins'] ? +settingsMap['scheduler_frequency_mins'] : 1,
          auto_refresh_tokens:      settingsMap['auto_refresh_tokens'] === '1' || settingsMap['auto_refresh_tokens'] === 'true',
          enable_notifications:     settingsMap['enable_notifications'] === '1' || settingsMap['enable_notifications'] === 'true',
        });
        this.loading.set(false);
      },
      error: () => {
        this.toast.error('Error', 'Failed to load settings.');
        this.loading.set(false);
      }
    });
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    const raw = this.form.getRawValue();
    const payload = Object.entries(raw).map(([key, val]) => ({
      key,
      value: typeof val === 'boolean' ? (val ? '1' : '0') : String(val),
    }));

    this.http.post(`${environment.apiUrl}/settings`, { settings: payload }).subscribe({
      next: () => {
        this.toast.success('Settings Saved', 'System configuration has been updated.');
        this.submitting.set(false);
      },
      error: (err) => {
        this.toast.error('Error', err.error?.message || 'Failed to save settings.');
        this.submitting.set(false);
      }
    });
  }
}
