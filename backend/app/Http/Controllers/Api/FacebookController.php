<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\FacebookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FacebookController
 * Handles Facebook Page OAuth connection/disconnection and webhook callbacks.
 */
class FacebookController extends Controller
{
    public function __construct(protected FacebookService $facebookService)
    {
        $this->middleware('auth:api')->except(['callback']);
    }

    /**
     * Return the Facebook OAuth dialog URL for the frontend to redirect to.
     */
    public function redirectUrl(): JsonResponse
    {
        $params = http_build_query([
            'client_id'    => config('services.facebook.client_id'),
            'redirect_uri' => config('services.facebook.redirect'),
            'scope'        => 'pages_manage_posts,pages_read_engagement,pages_show_list,instagram_basic,instagram_content_publish,instagram_manage_insights,read_insights',
            'response_type'=> 'code',
            'state'        => csrf_token(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => ['url' => "https://www.facebook.com/dialog/oauth?{$params}"],
        ]);
    }

    /**
     * Handle the OAuth callback from Facebook.
     * Exchanges code for token, fetches pages, stores SocialAccount records.
     */
    public function callback(Request $request): JsonResponse
    {
        $request->validate(['code' => 'required|string']);

        try {
            $tokenData = $this->facebookService->exchangeCodeForToken($request->code);
            $userToken = $tokenData['access_token'];
            $pages     = $this->facebookService->getUserPages($userToken);

            $connected = [];
            foreach ($pages['data'] as $page) {
                // Extend page token to long-lived
                $longToken = $this->facebookService->extendPageToken($page['access_token']);

                $account = SocialAccount::updateOrCreate(
                    [
                        'user_id'  => auth()->id(),
                        'platform' => 'facebook',
                        'page_id'  => $page['id'],
                    ],
                    [
                        'account_name'     => $page['name'],
                        'page_name'        => $page['name'],
                        'access_token'     => $longToken,
                        'token_expires_at' => now()->addDays(60),
                        'is_active'        => true,
                        'followers_count'  => $this->facebookService->getPageFans(
                            new \App\Models\SocialAccount(['page_id' => $page['id'], 'access_token' => $longToken])
                        ),
                    ]
                );

                $connected[] = $account;
            }

            return response()->json([
                'success' => true,
                'message' => count($connected) . ' Facebook Page(s) connected successfully.',
                'data'    => $connected,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function accounts(): JsonResponse
    {
        $accounts = SocialAccount::where('user_id', auth()->id())
            ->where('platform', 'facebook')
            ->get();

        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function disconnect(int $id): JsonResponse
    {
        $account = SocialAccount::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('platform', 'facebook')
            ->firstOrFail();

        $account->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Facebook account disconnected.']);
    }

    public function reconnect(int $id): JsonResponse
    {
        $account = SocialAccount::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        try {
            $longToken = $this->facebookService->extendPageToken($account->access_token);
            $account->update([
                'access_token'     => $longToken,
                'token_expires_at' => now()->addDays(60),
                'is_active'        => true,
            ]);

            return response()->json(['success' => true, 'message' => 'Token refreshed successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
