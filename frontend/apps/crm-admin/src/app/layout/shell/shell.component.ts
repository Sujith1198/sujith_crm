import { Component, OnInit, signal, computed, HostListener } from '@angular/core';
import { RouterOutlet, RouterLink, RouterLinkActive, Router } from '@angular/router';
import { CommonModule } from '@angular/common';
import { AuthService, ToastService } from '@crm/services';
import { User } from '@crm/models';

interface NavItem {
  label: string;
  icon: string;
  route: string;
  adminOnly?: boolean;
  badge?: string;
}

interface NavSection {
  title: string;
  items: NavItem[];
  adminOnly?: boolean;
}

/**
 * ShellComponent
 * Main application layout: Sidebar + Topbar + RouterOutlet.
 * Handles: collapsible sidebar, dark mode toggle, responsive mobile menu.
 */
@Component({
  selector: 'app-shell',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, CommonModule],
  templateUrl: './shell.component.html',
})
export class ShellComponent implements OnInit {
  sidebarCollapsed = signal(false);
  mobileSidebarOpen = signal(false);
  darkMode          = signal(false);
  currentUser       = signal<User | null>(null);

  readonly isAdmin = computed(() => this.authService.isAdmin());

  readonly navSections: NavSection[] = [
    {
      title: 'Main',
      items: [
        { label: 'Dashboard',       icon: 'bi-grid-1x2-fill',  route: '/dashboard' },
        { label: 'Posts',           icon: 'bi-file-post-fill', route: '/posts' },
        { label: 'Social Accounts', icon: 'bi-share-fill',     route: '/social' },
        { label: 'Analytics',       icon: 'bi-bar-chart-fill', route: '/analytics' },
        { label: 'Reports',         icon: 'bi-download',       route: '/reports' },
      ],
    },
    {
      title: 'Admin',
      adminOnly: true,
      items: [
        { label: 'Users',    icon: 'bi-people-fill',  route: '/users' },
        { label: 'Settings', icon: 'bi-gear-fill',    route: '/settings' },
      ],
    },
    {
      title: 'Account',
      items: [
        { label: 'My Profile', icon: 'bi-person-fill', route: '/profile' },
      ],
    },
  ];

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router,
  ) {}

  ngOnInit(): void {
    this.currentUser.set(this.authService.getUser());
    const savedTheme = localStorage.getItem('crm_theme');
    const savedCollapse = localStorage.getItem('crm_sidebar_collapsed');
    if (savedTheme === 'dark') this.setDarkMode(true);
    if (savedCollapse === 'true') this.sidebarCollapsed.set(true);
  }

  toggleSidebar(): void {
    if (window.innerWidth <= 992) {
      this.mobileSidebarOpen.update(v => !v);
    } else {
      this.sidebarCollapsed.update(v => {
        localStorage.setItem('crm_sidebar_collapsed', String(!v));
        return !v;
      });
    }
  }

  closeMobileSidebar(): void {
    this.mobileSidebarOpen.set(false);
  }

  toggleDarkMode(): void {
    this.setDarkMode(!this.darkMode());
  }

  setDarkMode(dark: boolean): void {
    this.darkMode.set(dark);
    document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
    localStorage.setItem('crm_theme', dark ? 'dark' : 'light');
  }

  logout(): void {
    this.authService.logout().subscribe({
      next: () => {
        this.authService.removeToken();
        this.router.navigate(['/auth/login']);
        this.toastService.success('Logged Out', 'See you next time!');
      },
      error: () => {
        // Even if API call fails, clear local storage
        this.authService.removeToken();
        this.router.navigate(['/auth/login']);
      },
    });
  }

  isNavVisible(section: NavSection): boolean {
    return !section.adminOnly || this.isAdmin();
  }

  isItemVisible(item: NavItem): boolean {
    return !item.adminOnly || this.isAdmin();
  }

  @HostListener('window:resize')
  onResize(): void {
    if (window.innerWidth > 992) {
      this.mobileSidebarOpen.set(false);
    }
  }
}
