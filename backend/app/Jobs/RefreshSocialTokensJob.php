<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RefreshSocialTokensJob
 *
 * Dispatched by the scheduler daily.
 * Refreshes expiring Facebook (and Instagram via FB token) access tokens.
 * Tokens expiring within 7 days are extended to a new 60-day long-lived token.
 */
class RefreshSocialTokensJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function handle(FacebookService $facebookService): void
    {
        // Find all active accounts with tokens expiring in the next 7 days
        $accounts = SocialAccount::active()
            ->where('auto_refresh_token', true)
            ->where(function ($q) {
                $q->whereNull('token_expires_at')
                  ->orWhere('token_expires_at', '<=', now()->addDays(7));
            })
            ->get();

        foreach ($accounts as $account) {
            try {
                // Facebook tokens (and Instagram tokens which use FB tokens) can be extended
                if ($account->platform === 'facebook') {
                    $newToken = $facebookService->extendPageToken($account->access_token);
                    $account->update([
                        'access_token'     => $newToken,
                        'token_expires_at' => now()->addDays(60),
                    ]);

                    // Also update linked Instagram accounts that use the same token
                    SocialAccount::where('user_id', $account->user_id)
                        ->where('platform', 'instagram')
                        ->where('is_active', true)
                        ->update([
                            'access_token'     => $newToken,
                            'token_expires_at' => now()->addDays(60),
                        ]);

                    Log::info("RefreshSocialTokensJob: Token refreshed for account #{$account->id} ({$account->account_name})");
                }
            } catch (\Exception $e) {
                Log::error("RefreshSocialTokensJob: Failed to refresh token for account #{$account->id}: " . $e->getMessage());
                // Mark token as potentially expired so the user is notified
                $account->update(['is_active' => false]);
            }
        }
    }
}
