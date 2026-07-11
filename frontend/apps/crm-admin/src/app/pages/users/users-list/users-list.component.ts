import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { UserService, ToastService } from '@crm/services';
import { User, UserFilters, PaginationMeta } from '@crm/models';

/**
 * UsersListComponent
 * Admin-only paginated user management table with search, filters, toggle status,
 * and quick delete/reset password actions.
 */
@Component({
  selector: 'app-users-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './users-list.component.html',
})
export class UsersListComponent implements OnInit {
  private userService = inject(UserService);
  private toast       = inject(ToastService);

  users     = signal<User[]>([]);
  meta      = signal<PaginationMeta | null>(null);
  loading   = signal(true);
  toggling  = signal<number | null>(null);
  deleting  = signal<number | null>(null);

  filters: UserFilters = {
    search:   '',
    status:   '',
    role:     '',
    per_page: 15,
    page:     1,
  };

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.userService.list(this.filters).subscribe({
      next: (res) => {
        this.users.set(res.data);
        this.meta.set(res.meta);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onSearch(): void { this.filters.page = 1; this.load(); }
  onFilterChange(): void { this.filters.page = 1; this.load(); }

  toggleStatus(user: User): void {
    this.toggling.set(user.id);
    this.userService.toggleStatus(user.id).subscribe({
      next: (res) => {
        this.users.update(list => list.map(u => u.id === user.id ? res.data : u));
        this.toast.success('Updated', `${user.name} is now ${res.data.status}.`);
        this.toggling.set(null);
      },
      error: () => this.toggling.set(null),
    });
  }

  confirmDelete(user: User): void {
    if (!confirm(`Delete user "${user.name}"? This cannot be undone.`)) return;
    this.deleting.set(user.id);
    this.userService.delete(user.id).subscribe({
      next: () => {
        this.toast.success('Deleted', `${user.name} has been deleted.`);
        this.load();
        this.deleting.set(null);
      },
      error: (err) => {
        this.toast.error('Error', err.error?.message ?? 'Cannot delete this user.');
        this.deleting.set(null);
      },
    });
  }

  clearFilters(): void {
    this.filters = { search: '', status: '', role: '', per_page: 15, page: 1 };
    this.load();
  }

  goToPage(page: number): void {
    if (!this.meta()) return;
    if (page < 1 || page > this.meta()!.last_page) return;
    this.filters.page = page;
    this.load();
  }

  get pages(): number[] {
    const m = this.meta();
    if (!m) return [];
    const cur = m.current_page;
    const total = m.last_page;
    const pages: number[] = [];
    for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) pages.push(i);
    return pages;
  }
}
