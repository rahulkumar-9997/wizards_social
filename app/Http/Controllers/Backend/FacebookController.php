<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Models\SocialAccount;
use App\Models\AdAccount;
use Carbon\Carbon;
use Exception;

class FacebookController extends Controller
{
    private $fbConfig;

    public function __construct()
    {
        $this->fbConfig = [
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'graph_version' => 'v19.0',
            'base_url' => 'https://graph.facebook.com/',
        ];
    }

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
                ->whereNull('parent_account_id')
                ->first();

            $connectedPages = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNotNull('parent_account_id')
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
            
            if ($mainAccount) {
                $tokenTest = $this->testToken($mainAccount);
                
                if ($tokenTest['valid']) {
                    $permissions = $this->checkPermissions($mainAccount);
                    $analytics = $this->getComprehensiveAnalytics($mainAccount);
                    $facebookData = $this->getFacebookProfileData($mainAccount);
                } else {
                    Log::error('Facebook token invalid: ' . $tokenTest['error']);
                }
            }

            // Calculate detailed statistics
            $totalPageFans = 0;
            $totalInstagramFollowers = 0;
            $pagesWithInstagram = 0;

            foreach ($connectedPages as $page) {
                $pageMeta = json_decode($page->meta_data, true) ?? [];
                $totalPageFans += $pageMeta['fan_count'] ?? 0;
                
                // Check if this page has Instagram connected
                $hasInstagram = $instagramAccounts->where('parent_account_id', $page->id)->count() > 0;
                if ($hasInstagram) {
                    $pagesWithInstagram++;
                }
            }

            foreach ($instagramAccounts as $ig) {
                $igMeta = json_decode($ig->meta_data, true) ?? [];
                $totalInstagramFollowers += $igMeta['followers_count'] ?? 0;
            }
            //dd($facebookData);
            $stats = [
                'total_pages' => $connectedPages->count(),
                'total_instagram_accounts' => $instagramAccounts->count(),
                'total_ad_accounts' => $adAccounts->count(),
                'total_page_fans' => $totalPageFans,
                'total_instagram_followers' => $totalInstagramFollowers,
                'pages_with_instagram' => $pagesWithInstagram,
                'connected_since' => $mainAccount ? $mainAccount->created_at->format('M d, Y') : null,
                'last_synced' => $mainAccount ? $mainAccount->last_synced_at?->format('M d, Y H:i') : null,
            ];

            return view('backend.pages.facebook.index', compact(
                'mainAccount',
                'connectedPages',
                'instagramAccounts',
                'adAccounts',
                'permissions',
                'analytics',
                'facebookData',
                'pagePosts',
                'stats'
            ));

        } catch (Exception $e) {
            Log::error('Facebook index error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to load Facebook page.');
        }
    }

    /**
     * Test if token is valid using API call
     */
    private function testToken($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            
            $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me', [
                'fields' => 'id,name',
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $userData = $response->json();
                return [
                    'valid' => true,
                    'user_id' => $userData['id'] ?? null,
                    'name' => $userData['name'] ?? null
                ];
            } else {
                $errorData = $response->json();
                return [
                    'valid' => false,
                    'error' => 'API Error: ' . ($errorData['error']['message'] ?? $response->body())
                ];
            }

        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => 'Connection Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check Facebook Permissions using API call
     */
    private function checkPermissions($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);

            $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/permissions', [
                'access_token' => $token,
            ]);

            if (!$response->successful()) {
                Log::error('Permissions API error: ' . $response->body());
                return $this->getDefaultPermissions();
            }

            $permissionsData = $response->json();
            $permissionsList = $permissionsData['data'] ?? [];

            $permissionStatus = [];
            foreach ($permissionsList as $perm) {
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
     * Get Comprehensive Analytics using API calls
     */
    private function getComprehensiveAnalytics($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $analytics = [];

            // Get user posts
            try {
                $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/posts', [
                    'fields' => 'id,message,created_time,likes.summary(true),comments.summary(true),shares,attachments',
                    'limit' => 10,
                    'access_token' => $token,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $analytics['posts'] = $data['data'] ?? [];
                    Log::info('Fetched ' . count($analytics['posts']) . ' posts');
                }
            } catch (Exception $e) {
                $analytics['posts'] = [];
                Log::warning('Failed to fetch posts: ' . $e->getMessage());
            }

            // Get pages data
            try {
                $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/accounts', [
                    'fields' => 'id,name,access_token,category,fan_count',
                    'limit' => 10,
                    'access_token' => $token,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $analytics['pages'] = $data['data'] ?? [];
                    Log::info('Fetched ' . count($analytics['pages']) . ' pages');
                }
            } catch (Exception $e) {
                $analytics['pages'] = [];
                Log::warning('Failed to fetch pages: ' . $e->getMessage());
            }

            // Get ad accounts
            try {
                $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/adaccounts', [
                    'fields' => 'id,name,account_status,amount_spent,currency',
                    'limit' => 10,
                    'access_token' => $token,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $analytics['ad_accounts'] = $data['data'] ?? [];
                    Log::info('Fetched ' . count($analytics['ad_accounts']) . ' ad accounts');
                }
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
     * Get Facebook Profile Data using API call
     */
    private function getFacebookProfileData($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);

            // Get basic profile info
            $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me', [
                'fields' => 'id,name,email,picture,first_name,last_name',
                'access_token' => $token,
            ]);

            if (!$response->successful()) {
                throw new Exception('Failed to fetch profile: ' . $response->body());
            }

            $profile = $response->json();
            $posts = [];
            $pages = [];

            // Get recent posts
            try {
                $postsResponse = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/posts', [
                    'fields' => 'id,message,created_time,likes.summary(true),comments.summary(true),attachments',
                    'limit' => 5,
                    'access_token' => $token,
                ]);

                if ($postsResponse->successful()) {
                    $postsData = $postsResponse->json();
                    $posts = $postsData['data'] ?? [];
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch user posts: ' . $e->getMessage());
            }

            // Get page accounts
            try {
                $pagesResponse = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/accounts', [
                    'fields' => 'id,name,access_token,instagram_business_account',
                    'limit' => 5,
                    'access_token' => $token,
                ]);

                if ($pagesResponse->successful()) {
                    $pagesData = $pagesResponse->json();
                    $pages = $pagesData['data'] ?? [];
                }
            } catch (Exception $e) {
                Log::warning('Failed to fetch pages: ' . $e->getMessage());
            }

            return [
                'profile' => $profile,
                'posts' => $posts,
                'pages' => $pages,
                'total_posts' => count($posts),
                'total_pages' => count($pages)
            ];

        } catch (Exception $e) {
            Log::error('Facebook data fetch error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch Facebook data: ' . $e->getMessage()];
        }
    }

    /**
     * Get Facebook Page Posts using API call
     */
    private function getFacebookPagePosts($pageAccount)
    {
        try {
            $token = $this->decryptToken($pageAccount->access_token);

            $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/' . $pageAccount->account_id . '/posts', [
                'fields' => 'id,message,created_time,likes.summary(true),comments.summary(true),shares,attachments',
                'limit' => 5,
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $posts = $data['data'] ?? [];
                Log::info('Fetched ' . count($posts) . ' page posts');
                return $posts;
            } else {
                Log::error('Page posts API error: ' . $response->body());
                return [];
            }

        } catch (Exception $e) {
            Log::error('Facebook page posts error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * API: Get Facebook Insights
     */
    public function getInsights(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|string',
            'since' => 'nullable|date',
            'until' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $account = SocialAccount::where('user_id', Auth::id())
                ->where('account_id', $request->account_id)
                ->firstOrFail();

            $token = $this->decryptToken($account->access_token);
            $since = $request->since ?? now()->subDays(7)->format('Y-m-d');
            $until = $request->until ?? now()->format('Y-m-d');

            $response = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/' . $account->account_id . '/insights', [
                'metric' => 'page_impressions,page_engaged_users,page_fans,page_actions_post_reactions_total',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'insights' => $data['data'] ?? [],
                    'period' => ['since' => $since, 'until' => $until]
                ]);
            } else {
                $errorData = $response->json();
                return response()->json([
                    'success' => false,
                    'message' => 'Facebook API Error: ' . ($errorData['error']['message'] ?? 'Unknown error')
                ], 500);
            }

        } catch (Exception $e) {
            Log::error('Facebook insights error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Get Instagram Insights for connected account
     */
    public function getInstagramInsights($socialAccountId)
    {
        try {
            $account = SocialAccount::findOrFail($socialAccountId);
            
            if ($account->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            $token = $this->decryptToken($account->access_token);

            $insights = [];
            $media = [];

            // Get Instagram insights
            try {
                $insightsResponse = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/' . $account->account_id . '/insights', [
                    'metric' => 'impressions,reach,engagement,profile_views,follower_count',
                    'period' => 'day',
                    'access_token' => $token,
                ]);

                if ($insightsResponse->successful()) {
                    $insightsData = $insightsResponse->json();
                    $insights = $insightsData['data'] ?? [];
                }
            } catch (Exception $e) {
                Log::warning('Instagram insights failed: ' . $e->getMessage());
            }

            // Get Instagram media
            try {
                $mediaResponse = Http::timeout(30)->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/' . $account->account_id . '/media', [
                    'fields' => 'id,caption,media_type,media_url,thumbnail_url,like_count,comments_count,timestamp',
                    'limit' => 12,
                    'access_token' => $token,
                ]);

                if ($mediaResponse->successful()) {
                    $mediaData = $mediaResponse->json();
                    $media = $mediaData['data'] ?? [];
                }
            } catch (Exception $e) {
                Log::warning('Instagram media failed: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'insights' => $insights,
                'media' => $media
            ]);

        } catch (Exception $e) {
            Log::error('Instagram insights error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch Instagram insights: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Sync Facebook Account Data
     */
    public function syncAccountData($accountId)
    {
        try {
            $user = Auth::user();
            $account = SocialAccount::where('user_id', $user->id)->findOrFail($accountId);

            if ($account->provider !== 'facebook') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid account type'
                ], 400);
            }

            $tokenTest = $this->testToken($account);
            
            if (!$tokenTest['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token: ' . $tokenTest['error']
                ], 401);
            }

            // Sync all data
            $permissions = $this->checkPermissions($account);
            $analytics = $this->getComprehensiveAnalytics($account);
            $profileData = $this->getFacebookProfileData($account);

            // Update account with sync timestamp
            $account->update([
                'last_synced_at' => now(),
                'meta_data' => json_encode(array_merge(
                    json_decode($account->meta_data, true) ?? [],
                    [
                        'last_sync' => now()->toISOString(),
                        'permissions' => $permissions,
                        'analytics_summary' => [
                            'posts_count' => count($analytics['posts'] ?? []),
                            'pages_count' => count($analytics['pages'] ?? []),
                            'ad_accounts_count' => count($analytics['ad_accounts'] ?? [])
                        ]
                    ]
                )),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Account data synced successfully',
                'data' => [
                    'permissions' => $permissions,
                    'analytics_summary' => [
                        'posts' => count($analytics['posts'] ?? []),
                        'pages' => count($analytics['pages'] ?? []),
                        'ad_accounts' => count($analytics['ad_accounts'] ?? [])
                    ],
                    'profile' => $profileData['profile'] ?? null,
                    'last_synced_at' => $account->last_synced_at
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Sync account data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync account data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Get Account Statistics
     */
    public function getAccountStats($accountId)
    {
        try {
            $user = Auth::user();
            $account = SocialAccount::where('user_id', $user->id)->findOrFail($accountId);

            $stats = [
                'account_id' => $account->id,
                'account_name' => $account->account_name,
                'provider' => $account->provider,
                'permission_level' => $account->permission_level,
                'last_synced_at' => $account->last_synced_at,
                'created_at' => $account->created_at,
                'connected_assets_count' => $account->asset_count ?? 0,
            ];

            // Add real-time stats if token is valid
            $tokenTest = $this->testToken($account);
            if ($tokenTest['valid']) {
                $analytics = $this->getComprehensiveAnalytics($account);
                $stats['real_time_stats'] = [
                    'posts_count' => count($analytics['posts'] ?? []),
                    'pages_count' => count($analytics['pages'] ?? []),
                    'ad_accounts_count' => count($analytics['ad_accounts'] ?? [])
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Get account stats error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch account stats'
            ], 500);
        }
    }

    /**
     * Decrypt token with enhanced error handling
     */
    private function decryptToken($encryptedToken)
    {
        try {
            if (empty($encryptedToken)) {
                throw new Exception('Empty token provided');
            }

            $decrypted = Crypt::decryptString($encryptedToken);
            
            if (empty($decrypted)) {
                throw new Exception('Decrypted token is empty');
            }

            $tokenData = json_decode($decrypted, true);
            $token = $tokenData['token'] ?? $decrypted;

            if (empty($token)) {
                throw new Exception('No token found in decrypted data');
            }

            return $token;

        } catch (Exception $e) {
            Log::error('Token decryption error: ' . $e->getMessage());
            throw new Exception('Failed to decrypt access token: ' . $e->getMessage());
        }
    }

    /**
     * API: Validate Facebook Token
     */
    public function validateToken($accountId)
    {
        try {
            $user = Auth::user();
            $account = SocialAccount::where('user_id', $user->id)->findOrFail($accountId);

            $tokenTest = $this->testToken($account);

            return response()->json([
                'success' => true,
                'data' => $tokenTest
            ]);

        } catch (Exception $e) {
            Log::error('Validate token error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate token'
            ], 500);
        }
    }
}