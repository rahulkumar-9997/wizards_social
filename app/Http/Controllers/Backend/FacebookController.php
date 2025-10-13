<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\SocialAccount;
use App\Models\AdAccount;
use Carbon\Carbon;
use Exception;

class FacebookController extends Controller
{
    /**
     * Facebook Integration Page
     */
     public function index()
    {
        try {
            $user = Auth::user();
            
            // Get main Facebook account and connected pages
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('account_id')
                ->first();

            $connectedPages = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNotNull('account_id')
                ->get();

            $instagramAccounts = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'instagram')
                ->get();

            $adAccounts = AdAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->get();

            $permissions = [];
            $analytics = [];
            $facebookData = [];
            $pagePosts = [];
            $debugInfo = [];
            
            if ($mainAccount) {
                $debugInfo['main_account'] = $mainAccount->toArray();
                $debugInfo['token_exists'] = !empty($mainAccount->access_token);
                
                // Test token first
                $tokenTest = $this->testToken($mainAccount);
                $debugInfo['token_test'] = $tokenTest;
                
                if ($tokenTest['valid']) {
                    $permissions = $this->checkPermissions($mainAccount);
                    $analytics = $this->getComprehensiveAnalytics($mainAccount);
                    $facebookData = $this->getFacebookProfileData($mainAccount);
                    
                    if ($connectedPages->count() > 0) {
                        $pagePosts = $this->getFacebookPagePosts($connectedPages->first());
                    }
                } else {
                    Log::error('Facebook token invalid: ' . $tokenTest['error']);
                }
                
                $debugInfo['permissions_result'] = $permissions;
            }
            //dd($permissions);
            dd($connectedPages);
            // Remove the dd() and return view instead
            return view('backend.pages.facebook.index', compact(
                'mainAccount',
                'connectedPages',
                'instagramAccounts',
                'adAccounts',
                'permissions',
                'analytics',
                'facebookData',
                'pagePosts',
                'debugInfo' // Remove this in production
            ));

        } catch (Exception $e) {
            Log::error('Facebook index error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to load Facebook page.');
        }
    }

    /**
     * Test if token is valid
     */
    private function testToken($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $fb = $this->createFacebookInstance();

            // Simple API call to test token
            $response = $fb->get('/me?fields=id,name', $token);
            $user = $response->getGraphNode()->asArray();
            
            return [
                'valid' => true,
                'user_id' => $user['id'] ?? null,
                'name' => $user['name'] ?? null
            ];

        } catch (\Facebook\Exceptions\FacebookAuthenticationException $e) {
            return [
                'valid' => false,
                'error' => 'Token expired or invalid: ' . $e->getMessage()
            ];
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            return [
                'valid' => false,
                'error' => 'Facebook API Error: ' . $e->getMessage()
            ];
        } catch (\Facebook\Exceptions\FacebookSDKException $e) {
            return [
                'valid' => false,
                'error' => 'Facebook SDK Error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'General Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create Facebook instance with SSL verification disabled for local
     */
    private function createFacebookInstance()
    {
        $config = [
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v18.0',
        ];

        // Disable SSL verification in local environment
        if (app()->environment('local')) {
            $config['http_client_handler'] = 'curl';
            $config['curl_options'] = [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_VERBOSE => true, // Enable verbose logging
            ];
        }

        return new \Facebook\Facebook($config);
    }

    /**
     * Check Facebook Permissions
     */
    private function checkPermissions($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $fb = $this->createFacebookInstance();

            Log::info('Checking Facebook permissions for token: ' . substr($token, 0, 20) . '...');

            // Get permissions from Facebook
            $permissionsResponse = $fb->get('/me/permissions', $token);
            $permissionsData = $permissionsResponse->getGraphEdge()->asArray();

            Log::info('Raw permissions data:', $permissionsData);

            $permissionStatus = [];
            foreach ($permissionsData as $perm) {
                $permissionStatus[$perm['permission']] = $perm['status'] === 'granted';
            }

            // Define expected permissions
            $expectedPermissions = [
                'email', 'public_profile', 'user_posts', 'user_photos', 'user_videos',
                'pages_show_list', 'pages_read_engagement', 'pages_read_user_content',
                'instagram_basic', 'instagram_content_publish', 'ads_read', 'business_management'
            ];

            $result = [];
            foreach ($expectedPermissions as $perm) {
                $result[$perm] = $permissionStatus[$perm] ?? false;
            }

            Log::info('Processed permissions:', $result);
            return $result;

        } catch (\Facebook\Exceptions\FacebookAuthenticationException $e) {
            Log::error('Facebook authentication error in permissions: ' . $e->getMessage());
            return $this->getDefaultPermissions();
        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            Log::error('Facebook API response error in permissions: ' . $e->getMessage());
            return $this->getDefaultPermissions();
        } catch (Exception $e) {
            Log::error('Facebook permissions check error: ' . $e->getMessage());
            return $this->getDefaultPermissions();
        }
    }

    /**
     * Get default permissions structure
     */
    private function getDefaultPermissions()
    {
        return [
            'email' => false,
            'public_profile' => false,
            'user_posts' => false,
            'user_photos' => false,
            'user_videos' => false,
            'pages_show_list' => false,
            'pages_read_engagement' => false,
            'pages_read_user_content' => false,
            'instagram_basic' => false,
            'instagram_content_publish' => false,
            'ads_read' => false,
            'business_management' => false
        ];
    }

    /**
     * Get Comprehensive Analytics
     */
    private function getComprehensiveAnalytics($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $fb = $this->createFacebookInstance();

            $analytics = [];

            // Get user posts
            try {
                Log::info('Fetching user posts...');
                $postsResponse = $fb->get('/me/posts?fields=id,message,created_time,likes.summary(true),comments.summary(true),shares,attachments&limit=5', $token);
                $analytics['posts'] = $postsResponse->getGraphEdge()->asArray();
                Log::info('Fetched ' . count($analytics['posts']) . ' posts');
            } catch (Exception $e) {
                $analytics['posts'] = [];
                Log::warning('Failed to fetch posts: ' . $e->getMessage());
            }

            // Get pages data
            try {
                Log::info('Fetching pages...');
                $pagesResponse = $fb->get('/me/accounts?fields=id,name,access_token,category,fan_count&limit=5', $token);
                $analytics['pages'] = $pagesResponse->getGraphEdge()->asArray();
                Log::info('Fetched ' . count($analytics['pages']) . ' pages');
            } catch (Exception $e) {
                $analytics['pages'] = [];
                Log::warning('Failed to fetch pages: ' . $e->getMessage());
            }

            // Get ad accounts
            try {
                Log::info('Fetching ad accounts...');
                $adAccountsResponse = $fb->get('/me/adaccounts?fields=id,name,account_status,amount_spent,currency&limit=5', $token);
                $analytics['ad_accounts'] = $adAccountsResponse->getGraphEdge()->asArray();
                Log::info('Fetched ' . count($analytics['ad_accounts']) . ' ad accounts');
            } catch (Exception $e) {
                $analytics['ad_accounts'] = [];
                Log::warning('Failed to fetch ad accounts: ' . $e->getMessage());
            }

            return $analytics;

        } catch (Exception $e) {
            Log::error('Facebook analytics error: ' . $e->getMessage());
            return [
                'posts' => [],
                'pages' => [],
                'ad_accounts' => []
            ];
        }
    }

    /**
     * Get Facebook Profile Data
     */
    public function getFacebookProfileData($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $fb = $this->createFacebookInstance();

            Log::info('Fetching Facebook profile data...');

            // Get basic profile info
            $profileResponse = $fb->get('/me?fields=id,name,email,picture', $token);
            $profile = $profileResponse->getGraphNode()->asArray();

            Log::info('Profile data fetched:', $profile);

            // Get recent posts
            try {
                $postsResponse = $fb->get('/me/posts?fields=id,message,created_time,likes.summary(true),comments.summary(true),attachments&limit=5', $token);
                $posts = $postsResponse->getGraphEdge()->asArray();
                Log::info('Fetched ' . count($posts) . ' user posts');
            } catch (Exception $e) {
                $posts = [];
                Log::warning('Failed to fetch user posts: ' . $e->getMessage());
            }

            // Get page accounts
            try {
                $pagesResponse = $fb->get('/me/accounts?fields=id,name,access_token,instagram_business_account&limit=5', $token);
                $pages = $pagesResponse->getGraphEdge()->asArray();
                Log::info('Fetched ' . count($pages) . ' pages');
            } catch (Exception $e) {
                $pages = [];
                Log::warning('Failed to fetch pages: ' . $e->getMessage());
            }

            return [
                'profile' => $profile,
                'posts' => $posts,
                'pages' => $pages,
                'total_posts' => count($posts),
                'total_pages' => count($pages)
            ];

        } catch (\Facebook\Exceptions\FacebookResponseException $e) {
            Log::error('Facebook API Error: ' . $e->getMessage());
            return ['error' => 'Facebook API error: ' . $e->getMessage()];
        } catch (Exception $e) {
            Log::error('Facebook data fetch error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch Facebook data.'];
        }
    }

    /**
     * Get Facebook Page Posts
     */
    public function getFacebookPagePosts($pageAccount)
    {
        try {
            $token = $this->decryptToken($pageAccount->access_token);
            $fb = $this->createFacebookInstance();

            Log::info('Fetching page posts for account: ' . $pageAccount->account_id);

            $response = $fb->get("/{$pageAccount->account_id}/posts?fields=id,message,created_time,likes.summary(true),comments.summary(true),shares,attachments&limit=5", $token);
            $posts = $response->getGraphEdge()->asArray();

            Log::info('Fetched ' . count($posts) . ' page posts');
            return $posts;

        } catch (Exception $e) {
            Log::error('Facebook page posts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Facebook Insights
     */
    public function getInsights(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|string',
            'since' => 'nullable|date',
            'until' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        try {
            $account = SocialAccount::where('user_id', Auth::id())
                ->where('account_id', $request->account_id)
                ->firstOrFail();

            $token = $this->decryptToken($account->access_token);

            $fb = new \Facebook\Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v18.0',
            ]);

            $since = $request->since ?? now()->subDays(7)->format('Y-m-d');
            $until = $request->until ?? now()->format('Y-m-d');

            // Get page insights
            $insightsResponse = $fb->get("/{$account->account_id}/insights?metric=page_impressions,page_engaged_users,page_fans,page_actions_post_reactions_total&period=day&since={$since}&until={$until}", $token);
            $insights = $insightsResponse->getGraphEdge()->asArray();

            return response()->json([
                'success' => true,
                'insights' => $insights,
                'period' => ['since' => $since, 'until' => $until]
            ]);

        } catch (Exception $e) {
            Log::error('Facebook insights error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch insights'], 500);
        }
    }

    /**
     * Get Instagram Insights for connected account
     */
    public function getInstagramInsights($socialAccountId)
    {
        try {
            $account = SocialAccount::findOrFail($socialAccountId);
            
            if ($account->user_id !== Auth::id()) {
                abort(403, 'Unauthorized');
            }

            $token = $this->decryptToken($account->access_token);

            $fb = new \Facebook\Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v18.0',
            ]);

            // Get Instagram business account insights
            $insightsResponse = $fb->get("/{$account->account_id}/insights?metric=impressions,reach,engagement,profile_views,follower_count&period=day", $token);
            $insights = $insightsResponse->getGraphEdge()->asArray();

            // Get recent media
            $mediaResponse = $fb->get("/{$account->account_id}/media?fields=id,caption,media_type,media_url,thumbnail_url,like_count,comments_count,timestamp&limit=12", $token);
            $media = $mediaResponse->getGraphEdge()->asArray();

            return response()->json([
                'success' => true,
                'insights' => $insights,
                'media' => $media
            ]);

        } catch (Exception $e) {
            Log::error('Instagram insights error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch Instagram insights'], 500);
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
            
            Log::info('Token decrypted successfully, length: ' . strlen($decrypted));
            
            return $tokenData['token'] ?? $decrypted;
        } catch (Exception $e) {
            Log::error('Token decryption error: ' . $e->getMessage());
            throw new Exception('Failed to decrypt access token.');
        }
    }
}