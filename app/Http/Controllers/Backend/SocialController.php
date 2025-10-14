<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\SocialAccount;
use App\Models\AdAccount;
use Exception;

class SocialController extends Controller
{
    private $facebookConfig;
    private $googleConfig;

    public function __construct()
    {
        $this->facebookConfig = [
            'client_id' => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'redirect_uri' => route('social.callback', 'facebook'),
        ];

        $this->googleConfig = [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => route('social.callback', 'google'),
        ];
    }

    /**
     * OAuth Redirect for Social Platforms
     */
    public function redirect($provider)
    {
        $validator = Validator::make(['provider' => $provider], [
            'provider' => 'required|in:facebook,google'
        ]);

        if ($validator->fails()) {
            return redirect()->route('dashboard')->with('error', 'Invalid provider.');
        }

        try {
            if ($provider === 'facebook') {
                $scopes = [
                    'email',
                    'public_profile',
                    'user_posts',
                    'user_photos',
                    'user_videos',
                    'user_likes',
                    'pages_show_list',
                    'pages_read_engagement',
                    'pages_manage_posts',
                    'pages_read_user_content',
                    'instagram_basic',
                    'instagram_content_publish',
                    'instagram_manage_insights',
                    'ads_management',
                    'ads_read',
                    'business_management',
                    'read_insights',
                    'publish_video',
                ];

                $params = [
                    'client_id' => $this->facebookConfig['client_id'],
                    'redirect_uri' => $this->facebookConfig['redirect_uri'],
                    'scope' => implode(',', $scopes),
                    'response_type' => 'code',
                    'auth_type' => 'rerequest',
                    'state' => csrf_token(),
                    'auth_nonce' => uniqid('fb_', true),
                    'display' => 'popup',
                ];

                $url = 'https://www.facebook.com/v24.0/dialog/oauth?' . http_build_query($params);
                //dd($url);
                return redirect($url);
            } elseif ($provider === 'google') {
                $scopes = [
                    'openid',
                    'profile',
                    'email',
                    'https://www.googleapis.com/auth/youtube',
                    'https://www.googleapis.com/auth/youtube.readonly',
                    'https://www.googleapis.com/auth/youtube.upload',
                    'https://www.googleapis.com/auth/youtubepartner',
                    'https://www.googleapis.com/auth/yt-analytics.readonly',
                ];

                $params = [
                    'client_id' => $this->googleConfig['client_id'],
                    'redirect_uri' => $this->googleConfig['redirect_uri'],
                    'scope' => implode(' ', $scopes),
                    'response_type' => 'code',
                    'access_type' => 'offline',
                    'prompt' => 'consent',
                    'state' => csrf_token(),
                ];

                $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
                return redirect($url);
            }
        } catch (Exception $e) {
            Log::error('Social redirect error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to initialize OAuth.');
        }

        return redirect()->route('dashboard')->with('error', 'Invalid provider.');
    }

    /**
     * OAuth Callback for Social Platforms
     */
    public function callback(Request $request, $provider)
    {
        $validator = Validator::make(['provider' => $provider], [
            'provider' => 'required|in:facebook,google'
        ]);

        if ($validator->fails()) {
            return redirect()->route('dashboard')->with('error', 'Invalid provider.');
        }

        // Verify CSRF token
        if ($request->state !== csrf_token()) {
            return redirect()->route('dashboard')->with('error', 'Invalid state parameter.');
        }

        if (!$request->has('code')) {
            return redirect()->route('dashboard')->with('error', 'Authorization code not received.');
        }

        try {
            if ($provider === 'facebook') {
                return $this->handleFacebookCallback($request);
            } elseif ($provider === 'google') {
                return $this->handleGoogleCallback($request);
            }
        } catch (Exception $e) {
            Log::error("Social callback error for {$provider}: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', ucfirst($provider) . ' connection failed: ' . $e->getMessage());
        }

        return redirect()->route('dashboard')->with('error', 'Invalid provider.');
    }

    /**
     * Handle Facebook OAuth Callback
     */
    private function handleFacebookCallback(Request $request)
    {
        try {
            // Exchange code for access token
            $tokenResponse = Http::asForm()->post('https://graph.facebook.com/v24.0/oauth/access_token', [
                'client_id' => $this->facebookConfig['client_id'],
                'client_secret' => $this->facebookConfig['client_secret'],
                'redirect_uri' => $this->facebookConfig['redirect_uri'],
                'code' => $request->code,
            ]);

            if (!$tokenResponse->successful()) {
                $errorData = $tokenResponse->json();
                throw new Exception($errorData['error']['message'] ?? 'Failed to get access token');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];
            $expiresIn = $tokenData['expires_in'] ?? null;

            // Get user info
            $userResponse = Http::get('https://graph.facebook.com/v24.0/me', [
                'fields' => 'id,name,email,first_name,last_name,picture',
                'access_token' => $accessToken,
            ]);

            if (!$userResponse->successful()) {
                $errorData = $userResponse->json();
                throw new Exception($errorData['error']['message'] ?? 'Failed to get user info');
            }

            $userData = $userResponse->json();
            $user = Auth::user();

            // Save social account
            $sa = SocialAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'facebook',
                    'account_id' => null
                ],
                [
                    'access_token' => Crypt::encryptString(json_encode([
                        'token' => $accessToken,
                        'expires_in' => $expiresIn
                    ])),
                    'token_expires_at' => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
                    'account_name' => $userData['name'] ?? 'Unknown',
                    'account_email' => $userData['email'] ?? null,
                    'avatar' => $userData['picture']['data']['url'] ?? null,
                    'permission_level' => 'full',
                    'meta_data' => json_encode($userData),
                ]
            );

            // Extract Facebook data in background
            dispatch(function () use ($accessToken, $user, $sa) {
                $this->extractFacebookData($accessToken, $user, $sa);
            });

            return redirect()->route('facebook.index')
                ->with('success', '🚀 Facebook connected successfully!')
                ->with('info', 'All accounts and data are being synchronized in the background.');
        } catch (Exception $e) {
            Log::error("Facebook callback error: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Facebook connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle Google OAuth Callback
     */
    private function handleGoogleCallback(Request $request)
    {
        try {
            // Exchange code for access token
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->googleConfig['client_id'],
                'client_secret' => $this->googleConfig['client_secret'],
                'redirect_uri' => $this->googleConfig['redirect_uri'],
                'code' => $request->code,
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                $errorData = $tokenResponse->json();
                throw new Exception($errorData['error_description'] ?? 'Failed to get access token');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? null;
            $expiresIn = $tokenData['expires_in'] ?? null;

            // Get user info
            $userResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v1/userinfo');

            if (!$userResponse->successful()) {
                $errorData = $userResponse->json();
                throw new Exception($errorData['error']['message'] ?? 'Failed to get user info');
            }

            $userData = $userResponse->json();
            $user = Auth::user();

            // Save social account
            $sa = SocialAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'account_id' => null
                ],
                [
                    'access_token' => Crypt::encryptString(json_encode([
                        'token' => $accessToken,
                        'refresh_token' => $refreshToken,
                        'expires_in' => $expiresIn
                    ])),
                    'refresh_token' => $refreshToken ? Crypt::encryptString($refreshToken) : null,
                    'token_expires_at' => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
                    'account_name' => $userData['name'] ?? 'Unknown',
                    'account_email' => $userData['email'] ?? null,
                    'avatar' => $userData['picture'] ?? null,
                    'permission_level' => 'full',
                    'meta_data' => json_encode($userData),
                ]
            );

            // Extract Google data in background
            dispatch(function () use ($accessToken, $user, $sa) {
                $this->extractGoogleData($accessToken, $user, $sa);
            });

            return redirect()->route('youtube.index')
                ->with('success', '🚀 Google connected successfully!')
                ->with('info', 'All accounts and data are being synchronized in the background.');
        } catch (Exception $e) {
            Log::error("Google callback error: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Google connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Social Account
     */
    public function disconnect(Request $request, $provider, $accountId = null)
    {
        try {
            $user = Auth::user();

            if ($accountId) {
                // Disconnect specific account
                $account = SocialAccount::where('user_id', $user->id)
                    ->where('provider', $provider)
                    ->where('account_id', $accountId)
                    ->firstOrFail();

                $accountName = $account->account_name;
                $account->delete();

                return redirect()->back()->with('success', "{$accountName} disconnected successfully.");
            } else {
                // Disconnect all accounts for this provider
                $query = SocialAccount::where('user_id', $user->id)
                    ->where('provider', $provider);

                $accountIds = $query->pluck('id');

                // Delete related ad accounts
                AdAccount::whereIn('social_account_id', $accountIds)->delete();

                // Delete child accounts (pages, Instagram accounts)
                SocialAccount::where('user_id', $user->id)
                    ->where('parent_account_id', $accountIds)
                    ->delete();

                // Delete main accounts
                $query->delete();

                return redirect()->back()->with('success', ucfirst($provider) . ' disconnected successfully.');
            }
        } catch (Exception $e) {
            Log::error("Disconnect error for {$provider}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to disconnect account.');
        }
    }

    /**
     * Extract Facebook Data using Direct API Calls
     */
    /**
 * Extract Facebook Data using Direct API Calls - Enhanced to get ALL pages
 */
private function extractFacebookData($accessToken, $user, $socialAccount)
{
    try {
        $extractedData = [];
        $connectedAssets = [];

        Log::info("🚀 Starting Facebook data extraction for user: " . $user->id);

        // 1. Get User Profile
        try {
            $response = Http::timeout(30)->get('https://graph.facebook.com/v24.0/me', [
                'fields' => 'id,name,email,first_name,last_name,picture,cover,age_range,link,location,gender',
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $extractedData['profile'] = $response->json();
                Log::info("✅ User profile extracted");
            }
        } catch (\Exception $e) {
            Log::warning("Profile extraction failed: " . $e->getMessage());
        }

        // 2. Get ALL Facebook Pages with Pagination
        try {
            $allPages = [];
            $nextUrl = 'https://graph.facebook.com/v24.0/me/accounts?fields=id,name,username,access_token,category,fan_count,cover,link,location,phone,website,emails,instagram_business_account{id,name,username,profile_picture_url,followers_count,media_count,biography,website,follows_count,ig_id}&limit=100&access_token=' . $accessToken;
            
            $pageCount = 0;
            
            // Paginate through all pages
            while ($nextUrl && $pageCount < 500) { // Safety limit of 500 pages
                $response = Http::timeout(60)->get($nextUrl);
                
                if (!$response->successful()) {
                    Log::error("Failed to fetch pages batch: " . $response->body());
                    break;
                }

                $data = $response->json();
                $pages = $data['data'] ?? [];
                $allPages = array_merge($allPages, $pages);
                $pageCount += count($pages);
                
                Log::info("Fetched batch of " . count($pages) . " pages. Total so far: " . count($allPages));
                $nextUrl = $data['paging']['next'] ?? null;
                if (!$nextUrl) {
                    break;
                }
                sleep(1);
            }

            $extractedData['pages'] = $allPages;
            Log::info("🎯 COMPLETED: Extracted " . count($allPages) . " Facebook pages in total");

            $instagramCount = 0;

            foreach ($allPages as $page) {
                // Save Facebook Page with detailed information
                $pageAccount = SocialAccount::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'provider' => 'facebook',
                        'account_id' => $page['id']
                    ],
                    [
                        'account_name' => $page['name'],
                        'account_email' => $page['emails'][0] ?? null,
                        'access_token' => Crypt::encryptString(json_encode(['token' => $page['access_token']])),
                        'parent_account_id' => $socialAccount->id,
                        'meta_data' => json_encode([
                            'username' => $page['username'] ?? null,
                            'category' => $page['category'] ?? null,
                            'fan_count' => $page['fan_count'] ?? 0,
                            'cover_photo' => $page['cover']['source'] ?? null,
                            'link' => $page['link'] ?? null,
                            'location' => $page['location'] ?? null,
                            'phone' => $page['phone'] ?? null,
                            'website' => $page['website'] ?? null,
                            'verified' => $page['is_verified'] ?? false,
                            'emails' => $page['emails'] ?? [],
                            'page_insights' => $this->getPageInsights($page['access_token'], $page['id'])
                        ]),
                        'permission_level' => 'page',
                        'avatar' => $page['cover']['source'] ?? null,
                    ]
                );

                $connectedAssets[] = [
                    'type' => 'facebook_page',
                    'id' => $page['id'],
                    'name' => $page['name'],
                    'category' => $page['category'] ?? 'Unknown',
                    'fans' => $page['fan_count'] ?? 0,
                    'username' => $page['username'] ?? null,
                    'link' => $page['link'] ?? null
                ];

                // Save Instagram Account if connected
                if (isset($page['instagram_business_account']['id'])) {
                    $instagramCount++;
                    $igData = $page['instagram_business_account'];
                    
                    $igAccount = SocialAccount::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'provider' => 'instagram',
                            'account_id' => $igData['id']
                        ],
                        [
                            'account_name' => $igData['username'] ?? 'Instagram Account',
                            'access_token' => Crypt::encryptString(json_encode(['token' => $page['access_token']])),
                            'parent_account_id' => $socialAccount->id,
                            'meta_data' => json_encode([
                                'name' => $igData['name'] ?? null,
                                'username' => $igData['username'] ?? null,
                                'profile_picture_url' => $igData['profile_picture_url'] ?? null,
                                'followers_count' => $igData['followers_count'] ?? 0,
                                'media_count' => $igData['media_count'] ?? 0,
                                'biography' => $igData['biography'] ?? null,
                                'website' => $igData['website'] ?? null,
                                'follows_count' => $igData['follows_count'] ?? 0,
                                'ig_id' => $igData['ig_id'] ?? null,
                                'connected_facebook_page' => $page['name'],
                                'instagram_insights' => $this->getInstagramBasicInsights($page['access_token'], $igData['id'])
                            ]),
                            'permission_level' => 'business',
                            'avatar' => $igData['profile_picture_url'] ?? null,
                        ]
                    );

                    $connectedAssets[] = [
                        'type' => 'instagram_business',
                        'id' => $igData['id'],
                        'name' => $igData['username'] ?? $igData['id'],
                        'username' => $igData['username'] ?? null,
                        'followers' => $igData['followers_count'] ?? 0,
                        'posts' => $igData['media_count'] ?? 0,
                        'connected_to_page' => $page['name']
                    ];

                    Log::info("✅ Connected Instagram: " . ($igData['username'] ?? $igData['id']));
                }
            }

            Log::info("🎯 Total Summary: " . count($allPages) . " Facebook pages, " . $instagramCount . " Instagram accounts connected");

        } catch (\Exception $e) {
            Log::error("Pages extraction failed: " . $e->getMessage());
        }

        // 3. Get User Posts (Optional - can be removed if not needed)
        try {
            $response = Http::timeout(30)->get('https://graph.facebook.com/v24.0/me/posts', [
                'fields' => 'id,message,created_time,permalink_url,attachments,likes.summary(true),comments.summary(true),shares',
                'limit' => 10,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $extractedData['posts'] = $data['data'] ?? [];
                Log::info("✅ Extracted " . count($extractedData['posts']) . " posts");
            }
        } catch (\Exception $e) {
            Log::info("Posts extraction failed: " . $e->getMessage());
        }

        // 4. Get Ad Accounts
        try {
            $response = Http::timeout(30)->get('https://graph.facebook.com/v24.0/me/adaccounts', [
                'fields' => 'id,name,account_status,currency,amount_spent,balance,timezone,business{id,name}',
                'limit' => 100,
                'access_token' => $accessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $adAccounts = $data['data'] ?? [];
                $extractedData['ad_accounts'] = $adAccounts;
                Log::info("✅ Extracted " . count($adAccounts) . " ad accounts");

                foreach ($adAccounts as $adAccount) {
                    AdAccount::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'ad_account_id' => $adAccount['id']
                        ],
                        [
                            'social_account_id' => $socialAccount->id,
                            'provider' => 'facebook',
                            'ad_account_name' => $adAccount['name'],
                            'account_status' => $adAccount['account_status'] ?? 'unknown',
                            'currency' => $adAccount['currency'] ?? 'USD',
                            'amount_spent' => $adAccount['amount_spent'] ?? 0,
                            'balance' => $adAccount['balance'] ?? 0,
                            'meta_data' => json_encode($adAccount),
                        ]
                    );

                    $connectedAssets[] = [
                        'type' => 'ad_account',
                        'id' => $adAccount['id'],
                        'name' => $adAccount['name'],
                        'status' => $adAccount['account_status'] ?? 'unknown',
                        'spent' => $adAccount['amount_spent'] ?? 0
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error("Ad accounts extraction failed: " . $e->getMessage());
        }

        // Update main account with comprehensive data
        $socialAccount->update([
            'meta_data' => json_encode($extractedData),
            'connected_assets' => json_encode($connectedAssets),
            'asset_count' => count($connectedAssets),
            'last_synced_at' => now(),
            'granted_permissions' => json_encode([
                'pages_access' => count($allPages ?? []) > 0,
                'instagram_access' => $instagramCount > 0,
                'ads_access' => count($adAccounts ?? []) > 0,
                'total_pages' => count($allPages ?? []),
                'total_instagram_accounts' => $instagramCount
            ]),
        ]);

        Log::info("🎯 Facebook data extraction COMPLETED. Total Assets: " . count($connectedAssets));
        Log::info("📊 FINAL SUMMARY:");
        Log::info("   - Facebook Pages: " . count($allPages ?? []));
        Log::info("   - Instagram Accounts: " . $instagramCount);
        Log::info("   - Ad Accounts: " . count($adAccounts ?? []));
        Log::info("   - Total Assets: " . count($connectedAssets));

    } catch (Exception $e) {
        Log::error('Facebook data extraction error: ' . $e->getMessage());
    }
}


    private function getInstagramBasicInsights($pageAccessToken, $instagramId)
    {
        try {
            $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$instagramId}/insights", [
                'metric' => 'impressions,reach,engagement,profile_views,follower_count',
                'period' => 'week',
                'access_token' => $pageAccessToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning("Instagram insights failed for {$instagramId}: " . $e->getMessage());
        }
        return [];
    }
    /**
     * Extract Google Data using Direct API Calls
     */
    private function extractGoogleData($accessToken, $user, $socialAccount)
    {
        try {
            $googleData = [];

            // Get Google Profile
            try {
                $response = Http::timeout(30)->withToken($accessToken)
                    ->get('https://www.googleapis.com/oauth2/v1/userinfo');

                if ($response->successful()) {
                    $googleData['profile'] = $response->json();
                    Log::info("✅ Google profile extracted");
                }
            } catch (\Exception $e) {
                Log::warning("Google profile extraction failed: " . $e->getMessage());
            }

            // Get YouTube Channels
            try {
                $response = Http::timeout(30)->withToken($accessToken)
                    ->get('https://www.googleapis.com/youtube/v3/channels', [
                        'part' => 'snippet,statistics,contentDetails',
                        'mine' => 'true',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $googleData['youtube_channels'] = $data['items'] ?? [];
                    Log::info("✅ Extracted " . count($googleData['youtube_channels']) . " YouTube channels");
                }
            } catch (\Exception $e) {
                Log::warning("YouTube channels extraction failed: " . $e->getMessage());
            }

            // Update account with Google data
            $socialAccount->update([
                'meta_data' => json_encode($googleData),
                'last_synced_at' => now(),
            ]);
        } catch (Exception $e) {
            Log::error('Google data extraction error: ' . $e->getMessage());
        }
    }

    /**
     * Decrypt Token
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

    private function getPageInsights($pageAccessToken, $pageId)
    {
        try {
            $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_impressions,page_engaged_users,page_fan_adds,page_views_total',
                'period' => 'day',
                'access_token' => $pageAccessToken,
            ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning("Page insights failed for {$pageId}: " . $e->getMessage());
        }

        return [];
    }

}
