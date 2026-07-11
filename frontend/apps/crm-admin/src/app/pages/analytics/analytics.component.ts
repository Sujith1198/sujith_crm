import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { BaseChartDirective } from 'ng2-charts';
import { ChartConfiguration, ChartData } from 'chart.js';
import { AnalyticsService } from '@crm/services';
import { AnalyticsFilters, AnalyticsData, AnalyticsSummary } from '@crm/models';

/**
 * AnalyticsComponent
 * Full analytics dashboard with period/platform filtering and multiple chart types.
 */
@Component({
  selector: 'app-analytics',
  standalone: true,
  imports: [CommonModule, FormsModule, BaseChartDirective],
  templateUrl: './analytics.component.html',
})
export class AnalyticsComponent implements OnInit {
  private analyticsService = inject(AnalyticsService);

  data       = signal<AnalyticsData[]>([]);
  loading    = signal(true);
  summary    = signal<AnalyticsSummary | null>(null);

  filters: AnalyticsFilters = {
    period:   'daily',
    platform: '',
    date_from: new Date(Date.now() - 30 * 86400000).toISOString().split('T')[0],
    date_to:   new Date().toISOString().split('T')[0],
  };

  // ── Line Chart ──────────────────────────
  lineChartData: ChartData<'line'> = {
    labels: [],
    datasets: [
      { label: 'Reach',       data: [], borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.1)',  tension: 0.4, fill: true },
      { label: 'Impressions', data: [], borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.06)', tension: 0.4, fill: true },
      { label: 'Views',       data: [], borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,0.06)',  tension: 0.4, fill: true },
    ],
  };

  lineOptions: ChartConfiguration['options'] = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top' }, tooltip: { mode: 'index', intersect: false } },
    scales: { x: { grid: { display: false } }, y: { beginAtZero: true } },
  };

  // ── Bar Chart (Engagement) ───────────────
  barChartData: ChartData<'bar'> = {
    labels: [],
    datasets: [
      { label: 'Likes',    data: [], backgroundColor: '#f43f5e', borderRadius: 4 },
      { label: 'Comments', data: [], backgroundColor: '#f59e0b', borderRadius: 4 },
      { label: 'Shares',   data: [], backgroundColor: '#10b981', borderRadius: 4 },
    ],
  };

  barOptions: ChartConfiguration['options'] = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: 'top' } },
    scales: { x: { grid: { display: false }, stacked: false }, y: { beginAtZero: true } },
  };

  // ── Area Chart (Engagement Rate) ─────────
  areaChartData: ChartData<'line'> = {
    labels: [],
    datasets: [{
      label: 'Engagement Rate (%)',
      data: [],
      borderColor: '#10b981',
      backgroundColor: 'rgba(16,185,129,0.15)',
      tension: 0.4,
      fill: true,
      pointRadius: 3,
    }],
  };

  ngOnInit(): void {
    this.load();
    this.loadSummary();
  }

  load(): void {
    this.loading.set(true);
    this.analyticsService.getAggregated(this.filters).subscribe({
      next: (res) => {
        const rows = res.data;
        this.data.set(rows);

        const labels = rows.map(r => r.period);
        this.lineChartData.labels = labels;
        this.lineChartData.datasets[0].data = rows.map(r => r.reach);
        this.lineChartData.datasets[1].data = rows.map(r => r.impressions);
        this.lineChartData.datasets[2].data = rows.map(r => r.views);

        this.barChartData.labels = labels;
        this.barChartData.datasets[0].data = rows.map(r => r.likes);
        this.barChartData.datasets[1].data = rows.map(r => r.comments);
        this.barChartData.datasets[2].data = rows.map(r => r.shares);

        this.areaChartData.labels = labels;
        this.areaChartData.datasets[0].data = rows.map(r => r.engagement_rate);

        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  loadSummary(): void {
    this.analyticsService.getSummary().subscribe({
      next: (res) => this.summary.set(res.data),
    });
  }

  applyFilters(): void { this.load(); }

  totalMetric(key: keyof AnalyticsData): number {
    return this.data().reduce((sum, row) => sum + (Number(row[key]) || 0), 0);
  }

  formatNum(n: number): string {
    if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
    if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
    return n?.toFixed(0) ?? '0';
  }
}
