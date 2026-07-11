// ──────────────────────────────────────────────────────────────────────────
// @crm/models — All TypeScript interfaces and type definitions
// ──────────────────────────────────────────────────────────────────────────

// ──────────────────────────────────────
// Auth
// ──────────────────────────────────────
export interface LoginRequest {
  email: string;
  password: string;
}

export interface AuthResponse {
  success: boolean;
  message?: string;
  data: {
    token: string;
    token_type: string;
    expires_in: number;
    user: User;
  };
}

export interface RefreshResponse {
  success: boolean;
  data: {
    token: string;
    token_type: string;
    expires_in: number;
  };
}

// ──────────────────────────────────────
// User
// ──────────────────────────────────────
export type UserStatus = 'active' | 'inactive' | 'suspended';
export type UserRole = 'admin' | 'user';

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  avatar: string | null;
  avatar_url: string;
  status: UserStatus;
  timezone: string;
  last_login_at: string | null;
  last_login_ip: string | null;
  created_at: string;
  updated_at: string;
  roles: string[];
  permissions: string[];
  social_accounts?: SocialAccount[];
}

export interface CreateUserPayload {
  name: string;
  email: string;
  phone?: string;
  password: string;
  role: UserRole;
  status?: UserStatus;
  timezone?: string;
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  phone?: string;
  password?: string;
  password_confirmation?: string;
  role?: UserRole;
  status?: UserStatus;
  timezone?: string;
}

// ──────────────────────────────────────
// Social Account
// ──────────────────────────────────────
export type Platform = 'facebook' | 'instagram';

export interface SocialAccount {
  id: number;
  platform: Platform;
  account_name: string;
  page_id: string | null;
  page_name: string | null;
  account_id: string | null;
  profile_picture_url: string | null;
  followers_count: number;
  auto_refresh_token: boolean;
  is_active: boolean;
  token_expires_at: string | null;
  token_expired: boolean;
  token_expiring_soon: boolean;
  last_synced_at: string | null;
  metadata: Record<string, unknown> | null;
  created_at: string;
}

// ──────────────────────────────────────
// Post
// ──────────────────────────────────────
export type PostStatus = 'draft' | 'scheduled' | 'publishing' | 'published' | 'failed' | 'cancelled';
export type PostType = 'text' | 'image' | 'video' | 'carousel' | 'reel';

export interface PostMedia {
  id: number;
  type: 'image' | 'video';
  url: string;
  thumbnail_url: string | null;
  file_name: string;
  mime_type: string;
  file_size: number;
  file_size_human: string;
  width: number | null;
  height: number | null;
  duration: number | null;
  sort_order: number;
}

export interface Post {
  id: number;
  title: string;
  caption: string | null;
  description: string | null;
  hashtags: string | null;
  hashtags_array: string[];
  thumbnail_url: string | null;
  status: PostStatus;
  post_type: PostType;
  publish_at: string | null;
  timezone: string;
  platforms: Platform[];
  post_to_facebook: boolean;
  post_to_instagram: boolean;
  facebook_post_id: string | null;
  instagram_media_id: string | null;
  error_message: string | null;
  retry_count: number;
  created_at: string;
  updated_at: string;
  user?: User;
  media: PostMedia[];
}

export interface CreatePostPayload {
  title: string;
  caption?: string;
  description?: string;
  hashtags?: string;
  post_type: PostType;
  publish_at?: string;
  timezone?: string;
  platforms: {
    facebook?: boolean;
    instagram?: boolean;
  };
}

// ──────────────────────────────────────
// Analytics
// ──────────────────────────────────────
export type AnalyticsPeriod = 'daily' | 'weekly' | 'monthly' | 'yearly';

export interface AnalyticsData {
  period: string;
  views: number;
  reach: number;
  impressions: number;
  likes: number;
  comments: number;
  shares: number;
  clicks: number;
  saves: number;
  engagement_rate: number;
  ctr: number;
  profile_visits: number;
  website_clicks: number;
  followers_count: number;
}

export interface AnalyticsSummary {
  today_reach: number;
  today_views: number;
  today_likes: number;
  today_comments: number;
  today_shares: number;
  total_reach: number;
  total_views: number;
  total_impressions: number;
  total_likes: number;
  total_comments: number;
  total_shares: number;
  facebook_followers: number;
  instagram_followers: number;
}

export interface DashboardStats extends AnalyticsSummary {
  total_posts: number;
  published_posts: number;
  scheduled_posts: number;
  draft_posts: number;
  top_performing_posts: Post[];
}

// ──────────────────────────────────────
// API Response Wrappers
// ──────────────────────────────────────
export interface ApiResponse<T = unknown> {
  success: boolean;
  message?: string;
  data: T;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  meta: PaginationMeta;
}

export interface PaginationMeta {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
}

// ──────────────────────────────────────
// Filters
// ──────────────────────────────────────
export interface PostFilters {
  search?: string;
  status?: PostStatus | '';
  platform?: Platform | '';
  post_type?: PostType | '';
  date_from?: string;
  date_to?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface UserFilters {
  search?: string;
  status?: UserStatus | '';
  role?: UserRole | '';
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
}

export interface AnalyticsFilters {
  period?: AnalyticsPeriod;
  platform?: Platform | '';
  date_from?: string;
  date_to?: string;
}

// ──────────────────────────────────────
// Activity Log
// ──────────────────────────────────────
export interface ActivityLog {
  id: number;
  user_id: number | null;
  action: string;
  description: string;
  subject_type: string | null;
  subject_id: number | null;
  old_values: Record<string, unknown> | null;
  new_values: Record<string, unknown> | null;
  ip_address: string | null;
  url: string | null;
  method: string | null;
  created_at: string;
  user?: User;
}

// ──────────────────────────────────────
// Toast Notification
// ──────────────────────────────────────
export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: string;
  type: ToastType;
  title: string;
  message?: string;
  duration?: number;
}
