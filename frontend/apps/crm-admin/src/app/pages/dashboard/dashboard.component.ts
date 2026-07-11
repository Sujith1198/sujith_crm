import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { BaseChartDirective } from 'ng2-charts';
import { ChartConfiguration, ChartData } from 'chart.js';
import { AnalyticsService } from '@crm/services';
import { DashboardStats } from '@crm/models';

/**
 * DashboardComponent
 * Displays all KPIs, stat cards, line/bar charts, and top performing posts.
 */
@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, BaseChartDirective],
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  private analyticsService = inject(AnalyticsService);

  stats         = signal<DashboardStats | null>(null);
  loading       = signal(true);
  chartLoading  = signal(true);

  // ── Line Chart (Reach over last 7 days) ──
  lineChartData: ChartData<'line'> = {
    labels: [],
    datasets: [
      {
        label: 'Reach',
        data: [],
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.12)',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#6366f1',
      },
      {
        label: 'Impressions',
        data: [],
        borderColor: '#8b5cf6',
        backgroundColor: 'rgba(139,92,246,0.08)',
        tension: 0.4,
        fill: true,
        pointRadius: 4,
        pointBackgroundColor: '#8b5cf6',
      },
    ],
  };

  lineChartOptions: ChartConfiguration['options'] = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top', labels: { usePointStyle: true, padding: 20, font: { size: 12 } } },
      tooltip: { mode: 'index', intersect: false },
    },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } },
    },
    interaction: { mode: 'nearest', axis: 'x', intersect: false },
  };

  // ── Bar Chart (Engagement breakdown) ──
  barChartData: ChartData<'bar'> = {
    labels: ['Likes', 'Comments', 'Shares', 'Saves', 'Clicks'],
    datasets: [
      {
        label: 'Facebook',
        data: [],
        backgroundColor: '#1877f2',
        borderRadius: 6,
      },
      {
        label: 'Instagram',
        data: [],
        backgroundColor: '#e1306c',
        borderRadius: 6,
      },
    ],
  };

  barChartOptions: ChartConfiguration['options'] = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { position: 'top', labels: { usePointStyle: true, padding: 16, font: { size: 12 } } },
    },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true },
    },
  };

  // ── Pie Chart (Post status distribution) ──
  pieChartData: ChartData<'doughnut'> = {
    labels: ['Published', 'Scheduled', 'Draft', 'Failed'],
    datasets: [{
      data: [],
      backgroundColor: ['#10b981', '#6366f1', '#94a3b8', '#ef4444'],
      borderWidth: 0,
      hoverOffset: 8,
    }],
  };

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  pieChartOptions: any = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '65%',
    plugins: {
      legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16, font: { size: 12 } } },
    },
  };

  ngOnInit(): void {
    this.loadDashboard();
    this.loadChartData();
  }

  loadDashboard(): void {
    this.analyticsService.getDashboard().subscribe({
      next: (res) => {
        this.stats.set(res.data);
        this.pieChartData.datasets[0].data = [
          res.data.published_posts,
          res.data.scheduled_posts,
          res.data.draft_posts,
          0,
        ];
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  loadChartData(): void {
    const filters = {
      period: 'daily' as const,
      date_from: new Date(Date.now() - 7 * 86400000).toISOString().split('T')[0],
      date_to: new Date().toISOString().split('T')[0],
    };

    this.analyticsService.getAggregated(filters).subscribe({
      next: (res) => {
        const data = res.data;
        this.lineChartData.labels      = data.map(d => d.period);
        this.lineChartData.datasets[0].data = data.map(d => d.reach);
        this.lineChartData.datasets[1].data = data.map(d => d.impressions);
        this.barChartData.datasets[0].data  = [0, 0, 0, 0, 0]; // Placeholder FB
        this.barChartData.datasets[1].data  = [
          data.reduce((s, d) => s + d.likes, 0),
          data.reduce((s, d) => s + d.comments, 0),
          data.reduce((s, d) => s + d.shares, 0),
          data.reduce((s, d) => s + d.saves, 0),
          data.reduce((s, d) => s + d.clicks, 0),
        ];
        this.chartLoading.set(false);
      },
      error: () => this.chartLoading.set(false),
    });
  }

  formatNumber(n: number): string {
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
    return n?.toString() ?? '0';
  }
}
