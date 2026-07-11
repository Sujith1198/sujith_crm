import {
  Component, OnInit, signal, inject, ViewChild, ElementRef
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { PostService, AuthService, ToastService } from '@crm/services';
import { Post, PostType, Platform } from '@crm/models';

/**
 * PostFormComponent — Shared Create / Edit form.
 * Handles: text/image/video/carousel/reel post types, multi-platform selection,
 * drag-and-drop media upload, schedule date/time, and hashtag management.
 */
@Component({
  selector: 'app-post-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  templateUrl: './post-form.component.html',
})
export class PostFormComponent implements OnInit {
  @ViewChild('fileInput') fileInputRef!: ElementRef<HTMLInputElement>;

  private fb          = inject(FormBuilder);
  private postService = inject(PostService);
  private route       = inject(ActivatedRoute);
  private router      = inject(Router);
  private toast       = inject(ToastService);

  form = this.fb.nonNullable.group({
    title:           ['', [Validators.required, Validators.maxLength(255)]],
    caption:         ['', [Validators.maxLength(2200)]],
    description:     [''],
    hashtags:        [''],
    post_type:       ['image' as PostType, Validators.required],
    publish_at:      [''],
    timezone:        [Intl.DateTimeFormat().resolvedOptions().timeZone],
    is_scheduled:    [false],
    post_to_facebook:  [false],
    post_to_instagram: [false],
  });

  editId        = signal<number | null>(null);
  loading       = signal(false);
  submitting    = signal(false);
  isDragging    = signal(false);
  mediaFiles    = signal<File[]>([]);
  mediaPreviews = signal<{ url: string; type: string; name: string }[]>([]);
  existingMedia = signal<any[]>([]);

  readonly postTypes: { value: PostType; label: string; icon: string }[] = [
    { value: 'text',     label: 'Text',     icon: 'bi-type' },
    { value: 'image',    label: 'Image',    icon: 'bi-image' },
    { value: 'video',    label: 'Video',    icon: 'bi-camera-video' },
    { value: 'carousel', label: 'Carousel', icon: 'bi-collection' },
    { value: 'reel',     label: 'Reel',     icon: 'bi-play-circle' },
  ];

  readonly timezones = Intl.supportedValuesOf('timeZone');

  get isEdit()     { return !!this.editId(); }
  get postType()   { return this.form.controls.post_type.value; }
  get isScheduled(){ return this.form.controls.is_scheduled.value; }

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.editId.set(+id);
      this.loadPost(+id);
    }
  }

  loadPost(id: number): void {
    this.loading.set(true);
    this.postService.getById(id).subscribe({
      next: (res) => {
        const post = res.data;
        this.form.patchValue({
          title:            post.title,
          caption:          post.caption ?? '',
          description:      post.description ?? '',
          hashtags:         post.hashtags ?? '',
          post_type:        post.post_type,
          timezone:         post.timezone,
          post_to_facebook: post.post_to_facebook,
          post_to_instagram:post.post_to_instagram,
          is_scheduled:     !!post.publish_at,
          publish_at:       post.publish_at ? post.publish_at.replace('Z','').substring(0,16) : '',
        });
        this.existingMedia.set(post.media);
        this.loading.set(false);
      },
      error: () => {
        this.toast.error('Error', 'Post not found.');
        this.router.navigate(['/posts']);
      },
    });
  }

  // ── Media ──────────────────────────────────

  openFilePicker(): void { this.fileInputRef.nativeElement.click(); }

  onFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    if (input.files) this.addFiles(Array.from(input.files));
  }

  onDragOver(event: DragEvent): void {
    event.preventDefault();
    this.isDragging.set(true);
  }

  onDragLeave(): void { this.isDragging.set(false); }

  onDrop(event: DragEvent): void {
    event.preventDefault();
    this.isDragging.set(false);
    if (event.dataTransfer?.files) this.addFiles(Array.from(event.dataTransfer.files));
  }

  addFiles(files: File[]): void {
    const MAX_SIZE_MB = 50;
    const valid = files.filter(f => {
      if (f.size > MAX_SIZE_MB * 1024 * 1024) {
        this.toast.warning('File too large', `${f.name} exceeds ${MAX_SIZE_MB}MB limit.`);
        return false;
      }
      return true;
    });

    this.mediaFiles.update(prev => [...prev, ...valid]);

    valid.forEach(file => {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.mediaPreviews.update(prev => [
          ...prev,
          { url: e.target!.result as string, type: file.type, name: file.name }
        ]);
      };
      reader.readAsDataURL(file);
    });
  }

  removeMedia(index: number): void {
    this.mediaFiles.update(f => f.filter((_, i) => i !== index));
    this.mediaPreviews.update(p => p.filter((_, i) => i !== index));
  }

  removeExistingMedia(mediaId: number): void {
    this.postService.deleteMedia(mediaId).subscribe({
      next: () => {
        this.existingMedia.update(m => (m as { id: number }[]).filter(x => x.id !== mediaId));
        this.toast.success('Media removed');
      },
    });
  }

  // ── Submit ─────────────────────────────────

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const v = this.form.getRawValue();

    if (!v.post_to_facebook && !v.post_to_instagram) {
      this.toast.warning('Platform Required', 'Select at least one platform (Facebook or Instagram).');
      return;
    }

    const payload = {
      title:       v.title,
      caption:     v.caption,
      description: v.description,
      hashtags:    v.hashtags,
      post_type:   v.post_type,
      timezone:    v.timezone,
      publish_at:  v.is_scheduled && v.publish_at ? new Date(v.publish_at).toISOString() : undefined,
      platforms: {
        facebook:  v.post_to_facebook,
        instagram: v.post_to_instagram,
      },
    };

    this.submitting.set(true);

    const request$ = this.isEdit
      ? this.postService.update(this.editId()!, payload, this.mediaFiles())
      : this.postService.create(payload, this.mediaFiles());

    request$.subscribe({
      next: (res) => {
        this.toast.success(
          this.isEdit ? 'Post Updated' : 'Post Created',
          v.is_scheduled
            ? `Post scheduled for ${new Date(v.publish_at).toLocaleString()}`
            : 'Your post has been saved.',
        );
        this.router.navigate(['/posts']);
      },
      error: (err) => {
        this.submitting.set(false);
        const errors = err.error?.errors;
        if (errors) {
          const messages = Object.values(errors).flat().join(' ');
          this.toast.error('Validation Error', messages as string);
        } else {
          this.toast.error('Error', err.error?.message ?? 'Failed to save post.');
        }
      },
    });
  }
}
