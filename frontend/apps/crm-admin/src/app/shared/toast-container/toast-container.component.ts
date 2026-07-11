import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ToastService } from '@crm/services';
import { Toast } from '@crm/models';

/**
 * ToastContainerComponent
 * Renders all active toasts from ToastService using Angular Signals.
 * Auto-positioned bottom-right, auto-dismisses via service timer.
 */
@Component({
  selector: 'app-toast-container',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="toast-container-wrapper">
      @for (toast of toastService.toasts(); track toast.id) {
        <div class="toast-item toast-{{ toast.type }}"
             role="alert"
             aria-live="assertive">
          <i class="bi toast-icon"
             [class.bi-check-circle-fill]="toast.type === 'success'"
             [class.bi-x-circle-fill]="toast.type === 'error'"
             [class.bi-exclamation-triangle-fill]="toast.type === 'warning'"
             [class.bi-info-circle-fill]="toast.type === 'info'">
          </i>
          <div class="flex-1">
            <div class="toast-title">{{ toast.title }}</div>
            @if (toast.message) {
              <div class="toast-message">{{ toast.message }}</div>
            }
          </div>
          <button class="toast-close" (click)="toastService.dismiss(toast.id)" aria-label="Dismiss">
            <i class="bi bi-x"></i>
          </button>
        </div>
      }
    </div>
  `,
})
export class ToastContainerComponent {
  toastService = inject(ToastService);
}
