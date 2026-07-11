<?php

namespace App\Console;

use App\Jobs\RefreshSocialTokensJob;
use App\Jobs\SyncFacebookInsightsJob;
use App\Jobs\SyncInstagramInsightsJob;
use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     * Run: php artisan schedule:run (via cron: * * * * * php artisan schedule:run)
     */
    protected function schedule(Schedule $schedule): void
    {
        // ──────────────────────────────────────────────
        // 1. Publish Scheduled Posts — every minute
        //    Checks for any posts due for publishing and dispatches jobs.
        // ──────────────────────────────────────────────
        $schedule->call(function () {
            $due = ScheduledPost::with(['post', 'socialAccount'])
                ->where('status', 'pending')
                ->where('scheduled_at', '<=', now())
                ->get();

            foreach ($due as $scheduled) {
                \App\Jobs\PublishScheduledPostJob::dispatch($scheduled->id);
                Log::info("Scheduler: Dispatched PublishScheduledPostJob for ScheduledPost #{$scheduled->id}");
            }
        })->everyMinute()->name('publish-scheduled-posts')->withoutOverlapping();

        // ──────────────────────────────────────────────
        // 2. Sync Facebook Insights — every hour
        //    Dispatches a job per active Facebook account.
        // ──────────────────────────────────────────────
        $schedule->call(function () {
            $accounts = SocialAccount::active()->platform('facebook')->get();
            foreach ($accounts as $account) {
                SyncFacebookInsightsJob::dispatch($account->id);
            }
            Log::info("Scheduler: Dispatched SyncFacebookInsightsJob for " . $accounts->count() . " accounts.");
        })->hourly()->name('sync-facebook-insights')->withoutOverlapping();

        // ──────────────────────────────────────────────
        // 3. Sync Instagram Insights — every hour
        // ──────────────────────────────────────────────
        $schedule->call(function () {
            $accounts = SocialAccount::active()->platform('instagram')->get();
            foreach ($accounts as $account) {
                SyncInstagramInsightsJob::dispatch($account->id);
            }
            Log::info("Scheduler: Dispatched SyncInstagramInsightsJob for " . $accounts->count() . " accounts.");
        })->hourly()->name('sync-instagram-insights')->withoutOverlapping();

        // ──────────────────────────────────────────────
        // 4. Refresh Expiring Tokens — daily at 2am
        // ──────────────────────────────────────────────
        $schedule->job(new RefreshSocialTokensJob())
                 ->dailyAt('02:00')
                 ->name('refresh-social-tokens')
                 ->withoutOverlapping();

        // ──────────────────────────────────────────────
        // 5. Clean up old failed jobs — weekly
        // ──────────────────────────────────────────────
        $schedule->command('queue:flush')
                 ->weekly()
                 ->sundays()
                 ->at('03:00');

        // ──────────────────────────────────────────────
        // 6. Prune stale 'publishing' posts (stuck > 1hr)
        // ──────────────────────────────────────────────
        $schedule->call(function () {
            Post::where('status', 'publishing')
                ->where('updated_at', '<=', now()->subHour())
                ->update(['status' => 'failed', 'error_message' => 'Publish timed out.']);
        })->hourly()->name('cleanup-stuck-posts');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
