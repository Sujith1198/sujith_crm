import { Component, OnInit, signal, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { PostService, AuthService, ToastService } from '@crm/services';
import { Post, PostFilters, PaginationMeta } from '@crm/models';

/**
 * PostsListComponent
 * Paginated, filterable, searchable posts table.
 * Admin sees all posts; Users see only their own.
 */
@Component({
  selector: 'app-posts-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule],
  templateUrl: './posts-list.component.html',
})
export class PostsListComponent implements OnInit {
  private postService  = inject(PostService);
  private authService  = inject(AuthService);
  private toast        = inject(ToastService);

  posts      = signal<Post[]>([]);
  meta       = signal<PaginationMeta | null>(null);
  loading    = signal(true);
  deleting   = signal<number | null>(null);

  filters: PostFilters = {
    search:   '',
    status:   '',
    platform: '',
    per_page: 15,
    page:     1,
  };

  readonly isAdmin     = this.authService.isAdmin();
  readonly statusOpts  = ['draft','scheduled','publishing','published','failed','cancelled'];
  readonly platformOpts = ['facebook','instagram'];

  ngOnInit(): void { this.load(); }

  load(): void {
    this.loading.set(true);
    this.postService.list(this.filters).subscribe({
      next: (res) => {
        this.posts.set(res.data);
        this.meta.set(res.meta);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  onSearch(): void {
    this.filters.page = 1;
    this.load();
  }

  onFilterChange(): void {
    this.filters.page = 1;
    this.load();
  }

  goToPage(page: number): void {
    if (!this.meta()) return;
    if (page < 1 || page > this.meta()!.last_page) return;
    this.filters.page = page;
    this.load();
  }

  confirmDelete(post: Post): void {
    if (!confirm(`Delete post "${post.title}"? This cannot be undone.`)) return;

    this.deleting.set(post.id);
    this.postService.delete(post.id).subscribe({
      next: () => {
        this.toast.success('Deleted', `"${post.title}" has been deleted.`);
        this.load();
        this.deleting.set(null);
      },
      error: (err) => {
        this.toast.error('Error', err.error?.message ?? 'Failed to delete post.');
        this.deleting.set(null);
      },
    });
  }

  clearFilters(): void {
    this.filters = { search: '', status: '', platform: '', per_page: 15, page: 1 };
    this.load();
  }

  getMin(a: number, b: number): number {
    return Math.min(a, b);
  }

  get pages(): number[] {
    const m = this.meta();
    if (!m) return [];
    const total = m.last_page;
    const cur   = m.current_page;
    const pages: number[] = [];
    for (let i = Math.max(1, cur - 2); i <= Math.min(total, cur + 2); i++) {
      pages.push(i);
    }
    return pages;
  }
}

