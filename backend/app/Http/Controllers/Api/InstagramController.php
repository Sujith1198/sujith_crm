<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Services\InstagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * InstagramController
 * Handles Instagram Business Account connection via Facebook OAuth.
 * Instagram Graph API requires a connected Facebook Page.
 */
class InstagramController extends Controller
{
    public function __construct(protected InstagramService $instagramService)
    {
        $this->middleware('auth:api');
    }

    /**
     * Connect an Instagram Business Account linked to a Facebook Page.
     * Requires the Facebook Page to already be connected.
     */
    public function connect(Request $request): JsonResponse
    {
        $request->validate([
            'facebook_account_id' => 'required|exists:social_accounts,id',
        ]);

        $fbAccount = SocialAccount::where('id', $request->facebook_account_id)
            ->where('user_id', auth()->id())
            ->where('platform', 'facebook')
            ->firstOrFail();

        try {
            // Get Instagram account linked to this Facebook Page
            $igAccount = $this->instagramService->getInstagramAccountFromPage(
                $fbAccount->page_id,
                $fbAccount->access_token,
            );

            if (! $igAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Instagram Business Account found linked to this Facebook Page. Please link one in Facebook Business Manager.',
                ], 422);
            }

            $igInfo = $this->instagramService->getAccountInfo(
                $igAccount['id'],
                $fbAccount->access_token,
            );

            $account = SocialAccount::updateOrCreate(
                [
                    'user_id'    => auth()->id(),
                    'platform'   => 'instagram',
                    'account_id' => $igAccount['id'],
                ],
                [
                    'account_name'       => $igInfo['name'] ?? $igInfo['username'] ?? 'Instagram Account',
                    'page_name'          => $igInfo['username'] ?? null,
                    'access_token'       => $fbAccount->access_token,
                    'token_expires_at'   => $fbAccount->token_expires_at,
                    'profile_picture_url'=> $igInfo['profile_picture_url'] ?? null,
                    'followers_count'    => $igInfo['followers_count'] ?? 0,
                    'is_active'          => true,
                    'metadata'           => [
                        'media_count' => $igInfo['media_count'] ?? 0,
                        'biography'   => $igInfo['biography'] ?? null,
                        'website'     => $igInfo['website'] ?? null,
                    ],
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Instagram account connected successfully.',
                'data'    => $account,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function accounts(): JsonResponse
    {
        $accounts = SocialAccount::where('user_id', auth()->id())
            ->where('platform', 'instagram')
            ->get();

        return response()->json(['success' => true, 'data' => $accounts]);
    }

    public function disconnect(int $id): JsonResponse
    {
        $account = SocialAccount::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('platform', 'instagram')
            ->firstOrFail();

        $account->update(['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Instagram account disconnected.']);
    }
}
