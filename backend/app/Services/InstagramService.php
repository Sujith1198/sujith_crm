<?php

namespace App\Services;

use App\Models\SocialAccount;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * InstagramService
 * Wraps all Instagram Graph API interactions:
 * - Account connection via Facebook OAuth
 * - Image, video, carousel, and reel posts
 * - Scheduling via publish_time parameter
 * - Media insights
 * - Profile insights
 */
class InstagramService
{
    protected Client $http;
    protected string $graphVersion;
    protected string $baseUrl;

    public function __construct()
    {
        $this->graphVersion = config('services.facebook.graph_version', 'v20.0');
        $this->baseUrl      = "https://graph.facebook.com/{$this->graphVersion}";
        $this->http         = new Client(['timeout' => 60]);
    }

    // ──────────────────────────────────────────────
    // Account Connection
    // ──────────────────────────────────────────────

    /**
     * Retrieve the Instagram Business Account linked to a Facebook Page.
     */
    public function getInstagramAccountFromPage(string $pageId, string $pageToken): ?array
    {
        $response = $this->get("/{$pageId}", [
            'fields'       => 'instagram_business_account',
            'access_token' => $pageToken,
        ]);

        return $response['instagram_business_account'] ?? null;
    }

    /**
     * Get basic Instagram account info.
     */
    public function getAccountInfo(string $igAccountId, string $token): array
    {
        return $this->get("/{$igAccountId}", [
            'fields'       => 'id,name,username,profile_picture_url,followers_count,media_count,biography,website',
            'access_token' => $token,
        ]);
    }

    // ──────────────────────────────────────────────
    // Post Creation
    // ──────────────────────────────────────────────

    /**
     * Create an image post on Instagram.
     * Step 1: Create container → Step 2: Publish container
     */
    public function createImagePost(SocialAccount $account, string $caption, string $imageUrl, ?int $scheduledTime = null): array
    {
        // Step 1: Create media container
        $containerParams = [
            'image_url'    => $imageUrl,
            'caption'      => $caption,
            'access_token' => $account->access_token,
        ];

        if ($scheduledTime) {
            $containerParams['media_type']    = 'IMAGE';
            $containerParams['scheduled_publish_time'] = $scheduledTime;
        }

        $container = $this->post("/{$account->account_id}/media", $containerParams);

        if (! isset($container['id'])) {
            throw new \Exception('Failed to create Instagram media container.');
        }

        // Step 2: Publish container (if not scheduled)
        if (! $scheduledTime) {
            return $this->publishContainer($account, $container['id']);
        }

        return $container;
    }

    /**
     * Create a video post (Reels or Video).
     */
    public function createVideoPost(SocialAccount $account, string $caption, string $videoUrl, bool $isReel = false, ?int $scheduledTime = null): array
    {
        $containerParams = [
            'media_type'   => $isReel ? 'REELS' : 'VIDEO',
            'video_url'    => $videoUrl,
            'caption'      => $caption,
            'access_token' => $account->access_token,
        ];

        if ($scheduledTime) {
            $containerParams['scheduled_publish_time'] = $scheduledTime;
        }

        $container = $this->post("/{$account->account_id}/media", $containerParams);

        if (! isset($container['id'])) {
            throw new \Exception('Failed to create Instagram video container.');
        }

        // Wait for video processing status (simplified - in production use webhook or polling)
        if (! $scheduledTime) {
            $this->waitForContainerReady($account, $container['id']);
            return $this->publishContainer($account, $container['id']);
        }

        return $container;
    }

    /**
     * Create a carousel post (multiple images).
     */
    public function createCarouselPost(SocialAccount $account, string $caption, array $imageUrls, ?int $scheduledTime = null): array
    {
        // Step 1: Create child containers for each image
        $childIds = [];
        foreach ($imageUrls as $imageUrl) {
            $child      = $this->post("/{$account->account_id}/media", [
                'image_url'    => $imageUrl,
                'is_carousel_item' => true,
                'access_token' => $account->access_token,
            ]);
            $childIds[] = $child['id'];
        }

        // Step 2: Create carousel container
        $carouselParams = [
            'media_type'   => 'CAROUSEL',
            'children'     => implode(',', $childIds),
            'caption'      => $caption,
            'access_token' => $account->access_token,
        ];

        if ($scheduledTime) {
            $carouselParams['scheduled_publish_time'] = $scheduledTime;
        }

        $container = $this->post("/{$account->account_id}/media", $carouselParams);

        // Step 3: Publish
        if (! $scheduledTime) {
            return $this->publishContainer($account, $container['id']);
        }

        return $container;
    }

    /**
     * Publish a media container.
     */
    public function publishContainer(SocialAccount $account, string $containerId): array
    {
        return $this->post("/{$account->account_id}/media_publish", [
            'creation_id'  => $containerId,
            'access_token' => $account->access_token,
        ]);
    }

    /**
     * Delete an Instagram media post.
     */
    public function deleteMedia(SocialAccount $account, string $mediaId): bool
    {
        $response = $this->delete("/{$mediaId}", ['access_token' => $account->access_token]);
        return $response['success'] ?? false;
    }

    // ──────────────────────────────────────────────
    // Insights
    // ──────────────────────────────────────────────

    /**
     * Get insights for a specific media post.
     */
    public function getMediaInsights(SocialAccount $account, string $mediaId): array
    {
        $metrics = 'impressions,reach,engagement,likes,comments,shares,saved,plays,video_views';

        return $this->get("/{$mediaId}/insights", [
            'metric'       => $metrics,
            'access_token' => $account->access_token,
        ]);
    }

    /**
     * Get account-level insights.
     */
    public function getAccountInsights(SocialAccount $account, string $since, string $until): array
    {
        $metrics = 'profile_views,website_clicks,email_contacts,follower_count';

        return $this->get("/{$account->account_id}/insights", [
            'metric'       => $metrics,
            'period'       => 'day',
            'since'        => $since,
            'until'        => $until,
            'access_token' => $account->access_token,
        ]);
    }

    /**
     * Get total follower count.
     */
    public function getFollowerCount(SocialAccount $account): int
    {
        $response = $this->get("/{$account->account_id}", [
            'fields'       => 'followers_count',
            'access_token' => $account->access_token,
        ]);
        return $response['followers_count'] ?? 0;
    }

    // ──────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────

    /**
     * Poll container status until it's FINISHED or times out.
     * For production, use webhooks instead.
     */
    protected function waitForContainerReady(SocialAccount $account, string $containerId, int $maxAttempts = 12): void
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            sleep(5);
            $status = $this->get("/{$containerId}", [
                'fields'       => 'status_code',
                'access_token' => $account->access_token,
            ]);
            if (($status['status_code'] ?? '') === 'FINISHED') return;
            if (($status['status_code'] ?? '') === 'ERROR') {
                throw new \Exception('Instagram video processing failed.');
            }
        }
        throw new \Exception('Instagram video processing timed out.');
    }

    // ──────────────────────────────────────────────
    // HTTP Helpers
    // ──────────────────────────────────────────────

    protected function get(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->http->get($this->baseUrl . $endpoint, ['query' => $params]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Instagram API GET error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \Exception('Instagram API error: ' . $e->getMessage(), $e->getCode());
        }
    }

    protected function post(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->http->post($this->baseUrl . $endpoint, ['form_params' => $params]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Instagram API POST error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \Exception('Instagram API error: ' . $e->getMessage(), $e->getCode());
        }
    }

    protected function delete(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->http->delete($this->baseUrl . $endpoint, ['query' => $params]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Instagram API DELETE error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \Exception('Instagram API error: ' . $e->getMessage(), $e->getCode());
        }
    }
}
