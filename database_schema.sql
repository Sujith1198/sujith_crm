-- =============================================================================
-- CRM Social Media Management System — Complete Database Script (MySQL)
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- Table: password_reset_tokens
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: jobs
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: job_batches
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: failed_jobs
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: users
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_email_status_index` (`email`,`status`),
  KEY `users_last_login_at_index` (`last_login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: permissions
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `module` varchar(255) DEFAULT NULL COMMENT 'Feature module e.g. posts, users, analytics',
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: roles
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'System roles cannot be deleted',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: model_has_permissions
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: model_has_roles
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: role_has_permissions
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: social_accounts
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `social_accounts`;
CREATE TABLE `social_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `platform` enum('facebook','instagram') NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `page_id` varchar(255) DEFAULT NULL COMMENT 'Facebook Page ID or Instagram Account ID',
  `page_name` varchar(255) DEFAULT NULL,
  `account_id` varchar(255) DEFAULT NULL COMMENT 'Instagram Business Account ID',
  `access_token` text NOT NULL COMMENT 'Encrypted access token',
  `refresh_token` text DEFAULT NULL COMMENT 'Encrypted long-lived token',
  `token_expires_at` timestamp NULL DEFAULT NULL,
  `profile_picture_url` varchar(255) DEFAULT NULL,
  `followers_count` bigint(20) NOT NULL DEFAULT 0,
  `auto_refresh_token` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` json DEFAULT NULL COMMENT 'Extra platform-specific data',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `social_accounts_user_id_platform_page_id_unique` (`user_id`,`platform`,`page_id`),
  KEY `social_accounts_user_id_platform_is_active_index` (`user_id`,`platform`,`is_active`),
  CONSTRAINT `social_accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: posts
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `caption` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `hashtags` text DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `status` enum('draft','scheduled','publishing','published','failed','cancelled') NOT NULL DEFAULT 'draft',
  `publish_at` timestamp NULL DEFAULT NULL COMMENT 'When to publish (UTC stored)',
  `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
  `post_to_facebook` tinyint(1) NOT NULL DEFAULT 0,
  `post_to_instagram` tinyint(1) NOT NULL DEFAULT 0,
  `facebook_post_id` varchar(255) DEFAULT NULL,
  `instagram_media_id` varchar(255) DEFAULT NULL,
  `instagram_container_id` varchar(255) DEFAULT NULL,
  `post_type` enum('text','image','video','carousel','reel') NOT NULL DEFAULT 'text',
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `last_retry_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `posts_status_index` (`status`),
  KEY `posts_user_id_status_index` (`user_id`,`status`),
  KEY `posts_status_publish_at_index` (`status`,`publish_at`),
  KEY `posts_publish_at_index` (`publish_at`),
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: post_media
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `post_media`;
CREATE TABLE `post_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `type` enum('image','video') NOT NULL DEFAULT 'image',
  `file_path` varchar(255) NOT NULL COMMENT 'Relative path in storage',
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL COMMENT 'Size in bytes',
  `disk` varchar(20) NOT NULL DEFAULT 'public' COMMENT 'Storage disk',
  `width` int(11) DEFAULT NULL COMMENT 'Image/video width in pixels',
  `height` int(11) DEFAULT NULL COMMENT 'Image/video height in pixels',
  `duration` int(11) DEFAULT NULL COMMENT 'Video duration in seconds',
  `thumbnail_path` varchar(255) DEFAULT NULL COMMENT 'Video thumbnail',
  `sort_order` int(11) NOT NULL DEFAULT 0 COMMENT 'Order for carousel posts',
  `facebook_media_fbid` varchar(255) DEFAULT NULL COMMENT 'FB media ID after upload',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `post_media_post_id_sort_order_index` (`post_id`,`sort_order`),
  CONSTRAINT `post_media_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: scheduled_posts
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `scheduled_posts`;
CREATE TABLE `scheduled_posts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `social_account_id` bigint(20) unsigned NOT NULL,
  `platform` enum('facebook','instagram') NOT NULL,
  `scheduled_at` timestamp NOT NULL COMMENT 'Exact publish datetime (UTC)',
  `status` enum('pending','processing','published','failed','cancelled') NOT NULL DEFAULT 'pending',
  `platform_post_id` varchar(255) DEFAULT NULL COMMENT 'ID returned by platform after publish',
  `error_message` text DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `last_attempted_at` timestamp NULL DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scheduled_posts_scheduled_at_index` (`scheduled_at`),
  KEY `scheduled_posts_status_index` (`status`),
  KEY `scheduled_posts_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `scheduled_posts_post_id_platform_index` (`post_id`,`platform`),
  CONSTRAINT `scheduled_posts_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheduled_posts_social_account_id_foreign` FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scheduled_posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: analytics
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `analytics`;
CREATE TABLE `analytics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `social_account_id` bigint(20) unsigned NOT NULL,
  `platform` enum('facebook','instagram') NOT NULL,
  `date` date NOT NULL,
  `views` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reach` bigint(20) unsigned NOT NULL DEFAULT 0,
  `impressions` bigint(20) unsigned NOT NULL DEFAULT 0,
  `likes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comments` bigint(20) unsigned NOT NULL DEFAULT 0,
  `shares` bigint(20) unsigned NOT NULL DEFAULT 0,
  `clicks` bigint(20) unsigned NOT NULL DEFAULT 0,
  `saves` bigint(20) unsigned NOT NULL DEFAULT 0,
  `engagement_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `ctr` decimal(8,4) NOT NULL DEFAULT 0.0000 COMMENT 'Click-through rate',
  `followers_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `profile_visits` bigint(20) unsigned NOT NULL DEFAULT 0,
  `website_clicks` bigint(20) unsigned NOT NULL DEFAULT 0,
  `video_views` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `analytics_unique` (`social_account_id`,`post_id`,`platform`,`date`),
  KEY `analytics_platform_index` (`platform`),
  KEY `analytics_date_index` (`date`),
  KEY `analytics_social_account_id_platform_date_index` (`social_account_id`,`platform`,`date`),
  CONSTRAINT `analytics_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `analytics_social_account_id_foreign` FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: facebook_insights
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `facebook_insights`;
CREATE TABLE `facebook_insights` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `social_account_id` bigint(20) unsigned NOT NULL,
  `facebook_post_id` varchar(255) NOT NULL COMMENT 'FB post_id from Graph API',
  `date` date NOT NULL,
  `impressions` bigint(20) unsigned NOT NULL DEFAULT 0,
  `impressions_unique` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Reach',
  `impressions_paid` bigint(20) unsigned NOT NULL DEFAULT 0,
  `impressions_organic` bigint(20) unsigned NOT NULL DEFAULT 0,
  `engaged_users` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_clicks` bigint(20) unsigned NOT NULL DEFAULT 0,
  `post_clicks_unique` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reactions_like_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reactions_love_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reactions_wow_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reactions_haha_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reactions_sorry_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reactions_anger_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comments_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `shares_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `video_views` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Total video views',
  `video_views_10s` bigint(20) unsigned NOT NULL DEFAULT 0,
  `video_avg_time_watched` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Seconds',
  `page_fans` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Total page followers',
  `page_fan_adds` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'New followers today',
  `page_fan_removes` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Unfollows today',
  `page_views_total` bigint(20) unsigned NOT NULL DEFAULT 0,
  `page_impressions` bigint(20) unsigned NOT NULL DEFAULT 0,
  `page_reach` bigint(20) unsigned NOT NULL DEFAULT 0,
  `page_engaged_users` bigint(20) unsigned NOT NULL DEFAULT 0,
  `raw_data` json DEFAULT NULL COMMENT 'Full raw API response',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fb_insights_unique` (`social_account_id`,`facebook_post_id`,`date`),
  KEY `facebook_insights_facebook_post_id_index` (`facebook_post_id`),
  KEY `facebook_insights_date_index` (`date`),
  CONSTRAINT `facebook_insights_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `facebook_insights_social_account_id_foreign` FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: instagram_insights
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `instagram_insights`;
CREATE TABLE `instagram_insights` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `social_account_id` bigint(20) unsigned NOT NULL,
  `instagram_media_id` varchar(255) NOT NULL COMMENT 'IG media_id from Graph API',
  `date` date NOT NULL,
  `impressions` bigint(20) unsigned NOT NULL DEFAULT 0,
  `reach` bigint(20) unsigned NOT NULL DEFAULT 0,
  `engagement` bigint(20) unsigned NOT NULL DEFAULT 0,
  `likes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `comments` bigint(20) unsigned NOT NULL DEFAULT 0,
  `shares` bigint(20) unsigned NOT NULL DEFAULT 0,
  `saved` bigint(20) unsigned NOT NULL DEFAULT 0,
  `plays` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT 'Reel plays',
  `video_views` bigint(20) unsigned NOT NULL DEFAULT 0,
  `engagement_rate` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `exits` bigint(20) unsigned NOT NULL DEFAULT 0,
  `replies` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taps_forward` bigint(20) unsigned NOT NULL DEFAULT 0,
  `taps_back` bigint(20) unsigned NOT NULL DEFAULT 0,
  `profile_views` bigint(20) unsigned NOT NULL DEFAULT 0,
  `website_clicks` bigint(20) unsigned NOT NULL DEFAULT 0,
  `email_contacts` bigint(20) unsigned NOT NULL DEFAULT 0,
  `follower_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `follower_count_change` bigint(20) unsigned NOT NULL DEFAULT 0,
  `raw_data` json DEFAULT NULL COMMENT 'Full raw API response',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ig_insights_unique` (`social_account_id`,`instagram_media_id`,`date`),
  KEY `instagram_insights_instagram_media_id_index` (`instagram_media_id`),
  KEY `instagram_insights_date_index` (`date`),
  CONSTRAINT `instagram_insights_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `instagram_insights_social_account_id_foreign` FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: activity_logs
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL COMMENT 'Model class name',
  `subject_id` bigint(20) unsigned DEFAULT NULL COMMENT 'Model ID',
  `old_values` json DEFAULT NULL COMMENT 'Previous state (for updates)',
  `new_values` json DEFAULT NULL COMMENT 'New state (for creates/updates)',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `method` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_action_index` (`action`),
  KEY `activity_logs_user_id_action_created_at_index` (`user_id`,`action`,`created_at`),
  KEY `activity_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Table: settings
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL COMMENT 'NULL = global setting, set = user setting',
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `type` enum('string','integer','boolean','json','encrypted') NOT NULL DEFAULT 'string',
  `group` varchar(50) DEFAULT NULL COMMENT 'Grouping key e.g. general, social, notifications',
  `label` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_user_id_key_unique` (`user_id`,`key`),
  KEY `settings_key_index` (`key`),
  KEY `settings_group_index` (`group`),
  CONSTRAINT `settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================================
-- SEED DATA
-- =============================================================================

-- 1. Insert permissions
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `module`, `description`, `created_at`, `updated_at`) VALUES
(1, 'view dashboard', 'api', 'dashboard', 'Access dashboard overview stats', NOW(), NOW()),
(2, 'view users', 'api', 'users', 'List all users in the system', NOW(), NOW()),
(3, 'create users', 'api', 'users', 'Add new users', NOW(), NOW()),
(4, 'edit users', 'api', 'users', 'Update user profiles and details', NOW(), NOW()),
(5, 'delete users', 'api', 'users', 'Soft delete users', NOW(), NOW()),
(6, 'disable users', 'api', 'users', 'Suspend or disable users', NOW(), NOW()),
(7, 'reset user password', 'api', 'users', 'Reset passwords for other users', NOW(), NOW()),
(8, 'assign roles', 'api', 'users', 'Change user roles', NOW(), NOW()),
(9, 'view all posts', 'api', 'posts', 'View all posts from all users', NOW(), NOW()),
(10, 'view own posts', 'api', 'posts', 'View only posts created by self', NOW(), NOW()),
(11, 'create posts', 'api', 'posts', 'Create new posts', NOW(), NOW()),
(12, 'edit posts', 'api', 'posts', 'Modify existing posts', NOW(), NOW()),
(13, 'delete posts', 'api', 'posts', 'Delete own or any posts', NOW(), NOW()),
(14, 'schedule posts', 'api', 'posts', 'Set publish schedules for posts', NOW(), NOW()),
(15, 'publish posts', 'api', 'posts', 'Trigger immediate publishing to platforms', NOW(), NOW()),
(16, 'connect facebook', 'api', 'social', 'Connect Facebook Page to account', NOW(), NOW()),
(17, 'connect instagram', 'api', 'social', 'Connect Instagram Business Account', NOW(), NOW()),
(18, 'disconnect accounts', 'api', 'social', 'Remove connected social accounts', NOW(), NOW()),
(19, 'view analytics', 'api', 'analytics', 'View aggregated system-wide analytics', NOW(), NOW()),
(20, 'view own analytics', 'api', 'analytics', 'View analytics for self-connected accounts', NOW(), NOW()),
(21, 'export reports', 'api', 'reports', 'Download CSV/PDF reports', NOW(), NOW()),
(22, 'view settings', 'api', 'settings', 'View application configuration', NOW(), NOW()),
(23, 'edit settings', 'api', 'settings', 'Update application configuration', NOW(), NOW()),
(24, 'view activity logs', 'api', 'logs', 'View audit logs of actions', NOW(), NOW());

-- 2. Insert roles
INSERT INTO `roles` (`id`, `name`, `guard_name`, `description`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'api', 'Full system access', 1, NOW(), NOW()),
(2, 'user', 'api', 'Standard user access', 1, NOW(), NOW());

-- 3. Link permissions to roles
-- admin has all permissions (IDs 1 to 24)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1), (2, 1), (3, 1), (4, 1), (5, 1), (6, 1), (7, 1), (8, 1), (9, 1), (10, 1),
(11, 1), (12, 1), (13, 1), (14, 1), (15, 1), (16, 1), (17, 1), (18, 1), (19, 1),
(20, 1), (21, 1), (22, 1), (23, 1), (24, 1);

-- user has limited permissions (dashboard, own posts, create, edit, delete, schedule, connect, disconnect, own analytics)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 2),   -- view dashboard
(10, 2),  -- view own posts
(11, 2),  -- create posts
(12, 2),  -- edit posts
(13, 2),  -- delete posts
(14, 2),  -- schedule posts
(16, 2),  -- connect facebook
(17, 2),  -- connect instagram
(18, 2),  -- disconnect accounts
(20, 2);  -- view own analytics

-- 4. Insert default users
-- Note: passwords are encrypted using bcrypt.
-- Admin password: Admin@12345 -> $2y$10$U.b/iQpL675bK7wT/E1Pze3mFkWGkS1/Zp.C31YkH7C714h1K9/Wq
-- Demo User password: User@12345 -> $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
INSERT INTO `users` (`id`, `name`, `email`, `password`, `status`, `timezone`, `email_verified_at`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'admin@crm-social.com', '$2y$10$U.b/iQpL675bK7wT/E1Pze3mFkWGkS1/Zp.C31YkH7C714h1K9/Wq', 'active', 'UTC', NOW(), NOW(), NOW()),
(2, 'Demo User', 'user@crm-social.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', 'UTC', NOW(), NOW(), NOW());

-- 5. Assign roles to users (model_id refers to user ID, model_type is App\Models\User)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2);

-- 6. Insert settings
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `label`, `description`, `created_at`, `updated_at`) VALUES
('app_name', 'CRM Social Media', 'string', 'general', 'Application Name', 'The name displayed in the application layouts', NOW(), NOW()),
('app_timezone', 'UTC', 'string', 'general', 'Default Timezone', 'Global default system timezone', NOW(), NOW()),
('date_format', 'Y-m-d', 'string', 'general', 'Date Format', 'Standard date format throughout the dashboard', NOW(), NOW()),
('max_media_files', '10', 'integer', 'posts', 'Max Media Files per Post', 'Number of files allowed for carousel posts', NOW(), NOW()),
('max_file_size_mb', '50', 'integer', 'posts', 'Max Upload Size (MB)', 'Maximum allowed file upload size', NOW(), NOW()),
('default_timezone', 'UTC', 'string', 'posts', 'Default Post Timezone', 'Post scheduling default timezone', NOW(), NOW()),
('analytics_days', '30', 'integer', 'analytics', 'Default Analytics Period (days)', 'Default dashboard chart duration', NOW(), NOW()),
('notify_on_publish', '1', 'boolean', 'notifications', 'Notify on Post Publish', 'Send confirmation alert on platform publish success', NOW(), NOW()),
('notify_on_fail', '1', 'boolean', 'notifications', 'Notify on Post Failure', 'Send critical alerts when scheduled posts fail', NOW(), NOW());
