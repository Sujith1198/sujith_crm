import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { ToastService, AuthService } from '@crm/services';
import { SocialAccount } from '@crm/models';
import { environment } from '@env/environment';

/**
 * SocialComponent
 * Displays connected Facebook and Instagram accounts.
 * Allows connecting, disconnecting, and reconnecting accounts.
 */
@Component({
  selector: 'app-social',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './social.component.html',
})
export class SocialComponent implements OnInit {
  private http  = inject(HttpClient);
  private toast = inject(ToastService);
  private auth  = inject(AuthService);

  fbAccounts  = signal<SocialAccount[]>([]);
  igAccounts  = signal<SocialAccount[]>([]);
  loading     = signal(true);
  connecting  = signal(false);
  disconnecting = signal<number | null>(null);

  ngOnInit(): void { this.loadAccounts(); }

  loadAccounts(): void {
    this.loading.set(true);
    this.http.get<{ data: SocialAccount[] }>(`${environment.apiUrl}/facebook/accounts`).subscribe({
      next: (res) => {
        this.fbAccounts.set(res.data);
        this.loadInstagram();
      },
    });
  }

  loadInstagram(): void {
    this.http.get<{ data: SocialAccount[] }>(`${environment.apiUrl}/instagram/accounts`).subscribe({
      next: (res) => { this.igAccounts.set(res.data); this.loading.set(false); },
      error: ()  => this.loading.set(false),
    });
  }

  connectFacebook(): void {
    this.connecting.set(true);
    this.http.get<{ data: { url: string } }>(`${environment.apiUrl}/facebook/redirect`).subscribe({
      next: (res) => {
        window.location.href = res.data.url;
      },
      error: () => {
        this.toast.error('Error', 'Could not initiate Facebook connection.');
        this.connecting.set(false);
      },
    });
  }

  connectInstagram(fbAccountId: number): void {
    this.http.post<{ success: boolean; message: string }>(
      `${environment.apiUrl}/instagram/connect`,
      { facebook_account_id: fbAccountId }
    ).subscribe({
      next: (res) => {
        this.toast.success('Connected', res.message);
        this.loadAccounts();
      },
      error: (err) => {
        this.toast.error('Connection Failed', err.error?.message ?? 'Check that your Facebook Page is linked to an Instagram Business Account.');
      },
    });
  }

  disconnect(account: SocialAccount): void {
    if (!confirm(`Disconnect "${account.account_name}"? Posts and insights will stop syncing.`)) return;
    this.disconnecting.set(account.id);
    const endpoint = `${environment.apiUrl}/${account.platform}/${account.id}`;
    this.http.delete<{ success: boolean }>(endpoint).subscribe({
      next: () => {
        this.toast.success('Disconnected', `${account.account_name} has been disconnected.`);
        this.loadAccounts();
        this.disconnecting.set(null);
      },
      error: (err) => {
        this.toast.error('Error', err.error?.message ?? 'Disconnect failed.');
        this.disconnecting.set(null);
      },
    });
  }

  reconnect(account: SocialAccount): void {
    this.http.post<{ success: boolean; message: string }>(
      `${environment.apiUrl}/facebook/${account.id}/reconnect`, {}
    ).subscribe({
      next: (res) => {
        this.toast.success('Reconnected', res.message);
        this.loadAccounts();
      },
      error: (err) => this.toast.error('Error', err.error?.message ?? 'Reconnect failed.'),
    });
  }

  formatDate(dateStr: string | null): string {
    if (!dateStr) return 'No expiry';
    return new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  }
}
