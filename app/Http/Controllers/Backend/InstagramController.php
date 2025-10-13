<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\SocialAccount;
use Exception;

class InstagramController extends Controller
{
    /**
     * Instagram Integration Page
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Get Instagram accounts (connected via Facebook pages)
            $instagramAccounts = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'instagram')
                ->get();

            $facebookConnected = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('account_id')
                ->exists();

            // Get Instagram data if accounts exist
            $instagramData = [];
            if ($instagramAccounts->count() > 0) {
                $instagramData = $this->getInstagramAccountData($instagramAccounts->first());
            }

            return view('backend.pages.instagram.index', compact(
                'instagramAccounts',
                'facebookConnected',
                'instagramData'
            ));

        } catch (Exception $e) {
            Log::error('Instagram index error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to load Instagram page.');
        }
    }

    /**
     * Get Instagram Account Data
     */
    public function getInstagramAccountData($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);

            $fb = new \Facebook\Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v18.0',
            ]);

            // Get Instagram business account info
            $accountInfo = $fb->get("/{$account->account_id}?fields=username,profile_picture_url,biography,website,followers_count,follows_count,media_count", $token);
            $profile = $accountInfo->getGraphNode()->asArray();

            // Get recent media
            $mediaResponse = $fb->get("/{$account->account_id}/media?fields=id,caption,media_type,media_url,thumbnail_url,like_count,comments_count,timestamp,permalink&limit=12", $token);
            $media = $mediaResponse->getGraphEdge()->asArray();

            // Get insights for the last 7 days
            $insightsResponse = $fb->get("/{$account->account_id}/insights?metric=impressions,reach,engagement,profile_views,follower_count&period=day", $token);
            $insights = $insightsResponse->getGraphEdge()->asArray();

            return [
                'profile' => $profile,
                'media' => $media,
                'insights' => $insights,
                'total_posts' => $profile['media_count'] ?? 0,
                'followers' => $profile['followers_count'] ?? 0
            ];

        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            Log::error('Instagram API Error: ' . $e->getMessage());
            return ['error' => 'Instagram API error: ' . $e->getMessage()];
        } catch (Exception $e) {
            Log::error('Instagram data fetch error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch Instagram data.'];
        }
    }

    /**
     * Get Instagram Posts
     */
    public function getPosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        try {
            $account = SocialAccount::where('user_id', Auth::id())
                ->where('account_id', $request->account_id)
                ->where('provider', 'instagram')
                ->firstOrFail();

            $token = $this->decryptToken($account->access_token);

            $fb = new \Facebook\Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v18.0',
            ]);

            $limit = $request->get('limit', 20);
            $mediaResponse = $fb->get("/{$account->account_id}/media?fields=id,caption,media_type,media_url,thumbnail_url,like_count,comments_count,timestamp,permalink,children{media_url,media_type}&limit={$limit}", $token);
            $media = $mediaResponse->getGraphEdge()->asArray();

            return response()->json([
                'success' => true,
                'posts' => $media
            ]);

        } catch (Exception $e) {
            Log::error('Instagram posts error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch posts'], 500);
        }
    }

    /**
     * Get Instagram Insights
     */
    public function getInsights(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|string',
            'metric' => 'nullable|string',
            'period' => 'nullable|in:day,week,month'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        try {
            $account = SocialAccount::where('user_id', Auth::id())
                ->where('account_id', $request->account_id)
                ->where('provider', 'instagram')
                ->firstOrFail();

            $token = $this->decryptToken($account->access_token);

            $fb = new \Facebook\Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v18.0',
            ]);

            $metrics = $request->metric ?? 'impressions,reach,engagement,profile_views,follower_count';
            $period = $request->period ?? 'day';

            $insightsResponse = $fb->get("/{$account->account_id}/insights?metric={$metrics}&period={$period}", $token);
            $insights = $insightsResponse->getGraphEdge()->asArray();

            return response()->json([
                'success' => true,
                'insights' => $insights
            ]);

        } catch (Exception $e) {
            Log::error('Instagram insights error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch insights'], 500);
        }
    }

    /**
     * Decrypt token
     */
    private function decryptToken($encryptedToken)
    {
        try {
            $decrypted = Crypt::decryptString($encryptedToken);
            $tokenData = json_decode($decrypted, true);
            return $tokenData['token'] ?? $decrypted;
        } catch (Exception $e) {
            Log::error('Token decryption error: ' . $e->getMessage());
            throw new Exception('Failed to decrypt access token.');
        }
    }
}