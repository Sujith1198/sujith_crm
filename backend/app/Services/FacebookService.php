<?php

namespace App\Services;

use App\Models\SocialAccount;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * FacebookService
 * Wraps all Facebook Graph API interactions:
 * - Page connection/disconnection
 * - Post creation (text, image, video, carousel)
 * - Post scheduling
 * - Insights retrieval
 * - Token management
 */
class FacebookService
{
    protected Client $http;
    protected string $graphVersion;
    protected string $baseUrl;

    public function __construct()
    {
        $this->graphVersion = config('services.facebook.graph_version', 'v20.0');
        $this->baseUrl      = "https://graph.facebook.com/{$this->graphVersion}";
        $this->http         = new Client([
            'base_uri' => $this->baseUrl . '/',
            'timeout'  => 30,
        ]);
    }

    // ──────────────────────────────────────────────
    // OAuth / Connection
    // ──────────────────────────────────────────────

    /**
     * Exchange a short-lived code for a long-lived page access token.
     */
    public function exchangeCodeForToken(string $code): array
    {
        $response = $this->get('/oauth/access_token', [
            'client_id'     => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'redirect_uri'  => config('services.facebook.redirect'),
            'code'          => $code,
        ]);

        // Exchange for long-lived token
        $longLived = $this->get('/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.facebook.client_id'),
            'client_secret'     => config('services.facebook.client_secret'),
            'fb_exchange_token' => $response['access_token'],
        ]);

        return $longLived;
    }

    /**
     * Get pages managed by the authenticated user.
     */
    public function getUserPages(string $userToken): array
    {
        return $this->get('/me/accounts', ['access_token' => $userToken]);
    }

    /**
     * Extend a page access token to a long-lived one.
     */
    public function extendPageToken(string $pageToken): string
    {
        $response = $this->get('/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => config('services.facebook.client_id'),
            'client_secret'     => config('services.facebook.client_secret'),
            'fb_exchange_token' => $pageToken,
        ]);

        return $response['access_token'];
    }

    // ──────────────────────────────────────────────
    // Post Creation
    // ──────────────────────────────────────────────

    /**
     * Create a text post on a Facebook Page.
     */
    public function createTextPost(SocialAccount $account, string $message, ?int $scheduledTime = null): array
    {
        $params = [
            'message'      => $message,
            'access_token' => $account->access_token,
        ];

        if ($scheduledTime) {
            $params['published']       = false;
            $params['scheduled_publish_time'] = $scheduledTime;
        }

        return $this->post("/{$account->page_id}/feed", $params);
    }

    /**
     * Upload a photo and create an image post.
     */
    public function createImagePost(SocialAccount $account, string $message, string $imageUrl, ?int $scheduledTime = null): array
    {
        $params = [
            'message'      => $message,
            'url'          => $imageUrl,
            'access_token' => $account->access_token,
        ];

        if ($scheduledTime) {
            $params['published']       = false;
            $params['scheduled_publish_time'] = $scheduledTime;
        }

        return $this->post("/{$account->page_id}/photos", $params);
    }

    /**
     * Create a carousel (multi-image) post.
     * Step 1: Upload each image as unpublished photo → get photo IDs
     * Step 2: Create feed post with attached_media
     */
    public function createCarouselPost(SocialAccount $account, string $message, array $imageUrls, ?int $scheduledTime = null): array
    {
        $mediaFbids = [];

        foreach ($imageUrls as $url) {
            $response     = $this->post("/{$account->page_id}/photos", [
                'url'          => $url,
                'published'    => false,
                'access_token' => $account->access_token,
            ]);
            $mediaFbids[] = ['media_fbid' => $response['id']];
        }

        $params = [
            'message'        => $message,
            'attached_media' => json_encode($mediaFbids),
            'access_token'   => $account->access_token,
        ];

        if ($scheduledTime) {
            $params['published']       = false;
            $params['scheduled_publish_time'] = $scheduledTime;
        }

        return $this->post("/{$account->page_id}/feed", $params);
    }

    /**
     * Initiate a video upload and create a video post.
     */
    public function createVideoPost(SocialAccount $account, string $message, string $videoPath, ?int $scheduledTime = null): array
    {
        $params = [
            'description'  => $message,
            'access_token' => $account->access_token,
            'source'       => new \GuzzleHttp\Psr7\Utils::tryFopen($videoPath, 'r'),
        ];

        if ($scheduledTime) {
            $params['published']       = false;
            $params['scheduled_publish_time'] = $scheduledTime;
        }

        return $this->postMultipart("/{$account->page_id}/videos", $params);
    }

    /**
     * Delete a post from a Facebook Page.
     */
    public function deletePost(SocialAccount $account, string $postId): bool
    {
        $response = $this->delete("/{$postId}", ['access_token' => $account->access_token]);
        return $response['success'] ?? false;
    }

    // ──────────────────────────────────────────────
    // Insights
    // ──────────────────────────────────────────────

    /**
     * Get post-level insights from the Graph API.
     */
    public function getPostInsights(SocialAccount $account, string $postId): array
    {
        $metrics = implode(',', [
            'post_impressions',
            'post_impressions_unique',
            'post_impressions_paid',
            'post_impressions_organic',
            'post_engaged_users',
            'post_clicks',
            'post_clicks_unique',
            'post_reactions_like_total',
            'post_reactions_love_total',
            'post_reactions_wow_total',
            'post_reactions_haha_total',
            'post_reactions_sorry_total',
            'post_reactions_anger_total',
            'post_video_views',
            'post_video_views_10s',
            'post_video_avg_time_watched',
        ]);

        return $this->get("/{$postId}/insights", [
            'metric'       => $metrics,
            'access_token' => $account->access_token,
        ]);
    }

    /**
     * Get page-level daily insights.
     */
    public function getPageInsights(SocialAccount $account, string $since, string $until): array
    {
        $metrics = implode(',', [
            'page_fans',
            'page_fan_adds_unique',
            'page_fan_removes_unique',
            'page_views_total',
            'page_impressions',
            'page_reach',
            'page_engaged_users',
        ]);

        return $this->get("/{$account->page_id}/insights", [
            'metric'       => $metrics,
            'period'       => 'day',
            'since'        => $since,
            'until'        => $until,
            'access_token' => $account->access_token,
        ]);
    }

    /**
     * Get total page fans (follower count).
     */
    public function getPageFans(SocialAccount $account): int
    {
        $response = $this->get("/{$account->page_id}", [
            'fields'       => 'fan_count',
            'access_token' => $account->access_token,
        ]);
        return $response['fan_count'] ?? 0;
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
            Log::error('Facebook API GET error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \Exception('Facebook API error: ' . $e->getMessage(), $e->getCode());
        }
    }

    protected function post(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->http->post($this->baseUrl . $endpoint, ['form_params' => $params]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Facebook API POST error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \Exception('Facebook API error: ' . $e->getMessage(), $e->getCode());
        }
    }

    protected function postMultipart(string $endpoint, array $params = []): array
    {
        try {
            $multipart = [];
            foreach ($params as $key => $value) {
                $multipart[] = ['name' => $key, 'contents' => $value];
            }
            $response = $this->http->post($this->baseUrl . $endpoint, ['multipart' => $multipart]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Facebook API multipart error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \Exception('Facebook API error: ' . $e->getMessage(), $e->getCode());
        }
    }

    protected function delete(string $endpoint, array $params = []): array
    {
        try {
            $response = $this->http->delete($this->baseUrl . $endpoint, ['query' => $params]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            Log::error('Facebook API DELETE error', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            throw new \Exception('Facebook API error: ' . $e->getMessage(), $e->getCode());
        }
    }
}
