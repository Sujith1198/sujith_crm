<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * SettingsSeeder
 * Seeds default global application settings.
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'app_name',        'value' => 'CRM Social Media',    'type' => 'string',  'group' => 'general', 'label' => 'Application Name'],
            ['key' => 'app_timezone',     'value' => 'UTC',                 'type' => 'string',  'group' => 'general', 'label' => 'Default Timezone'],
            ['key' => 'date_format',      'value' => 'Y-m-d',               'type' => 'string',  'group' => 'general', 'label' => 'Date Format'],

            // Posts
            ['key' => 'max_media_files',  'value' => '10',                  'type' => 'integer', 'group' => 'posts',   'label' => 'Max Media Files per Post'],
            ['key' => 'max_file_size_mb', 'value' => '50',                  'type' => 'integer', 'group' => 'posts',   'label' => 'Max Upload Size (MB)'],
            ['key' => 'default_timezone', 'value' => 'UTC',                 'type' => 'string',  'group' => 'posts',   'label' => 'Default Post Timezone'],

            // Analytics
            ['key' => 'analytics_days',   'value' => '30',                  'type' => 'integer', 'group' => 'analytics', 'label' => 'Default Analytics Period (days)'],

            // Notifications
            ['key' => 'notify_on_publish','value' => '1',                   'type' => 'boolean', 'group' => 'notifications', 'label' => 'Notify on Post Publish'],
            ['key' => 'notify_on_fail',   'value' => '1',                   'type' => 'boolean', 'group' => 'notifications', 'label' => 'Notify on Post Failure'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key'], 'user_id' => null],
                $setting
            );
        }

        $this->command->info('✓ Default settings seeded.');
    }
}
