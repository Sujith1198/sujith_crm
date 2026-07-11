import { Component, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ToastService } from '@crm/services';
import { environment } from '@env/environment';

interface ReportFilters {
  type: 'posts' | 'analytics';
  format: 'xlsx' | 'csv' | 'pdf';
  platform: string;
  status: string;
  date_from: string;
  date_to: string;
}

/**
 * ReportsComponent
 * Provides export UI for Posts and Analytics reports in Excel, CSV, and PDF.
 */
@Component({
  selector: 'app-reports',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './reports.component.html',
})
export class ReportsComponent {
  private http  = inject(HttpClient);
  private toast = inject(ToastService);

  exporting   = signal(false);
  lastExported = signal('');

  filters: ReportFilters = {
    type:      'posts',
    format:    'xlsx',
    platform:  '',
    status:    '',
    date_from: new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0],
    date_to:   new Date().toISOString().split('T')[0],
  };

  export(): void {
    this.exporting.set(true);

    const endpoint = `${environment.apiUrl}/reports/${this.filters.type}`;
    const params: Record<string, string> = {
      format: this.filters.format,
    };
    if (this.filters.platform) params['platform']  = this.filters.platform;
    if (this.filters.status)   params['status']    = this.filters.status;
    if (this.filters.date_from) params['date_from'] = this.filters.date_from;
    if (this.filters.date_to)   params['date_to']   = this.filters.date_to;

    const queryString = new URLSearchParams(params).toString();

    this.http.get(`${endpoint}?${queryString}`, { responseType: 'blob', observe: 'response' }).subscribe({
      next: (response) => {
        const contentDisp = response.headers.get('Content-Disposition') ?? '';
        const match = contentDisp.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        const filename = match?.[1]?.replace(/['"]/g, '') ??
          `${this.filters.type}-report.${this.filters.format}`;

        const blob = new Blob([response.body!]);
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);

        this.lastExported.set(new Date().toLocaleTimeString());
        this.toast.success('Export Complete', `${filename} downloaded successfully.`);
        this.exporting.set(false);
      },
      error: () => {
        this.toast.error('Export Failed', 'Could not generate the report. Please try again.');
        this.exporting.set(false);
      },
    });
  }
}
