<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\SocialAccount;
use App\Models\AdAccount;
use Exception;
use Illuminate\Support\Facades\Http;

class SocialController extends Controller
{
    /**
     * Full Business Access - With Valid Permissions Only
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
                return Socialite::driver('facebook')
                    ->scopes([
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
                    ])
                    ->with([
                        'auth_type' => 'rerequest',
                        'response_type' => 'code',
                    ])
                    ->redirect();
            }

            if ($provider === 'google') {
                return Socialite::driver('google')
                    ->scopes([
                        'openid',
                        'profile',
                        'email',
                        'https://www.googleapis.com/auth/youtube',
                        'https://www.googleapis.com/auth/youtube.readonly',
                        'https://www.googleapis.com/auth/youtube.upload',
                        'https://www.googleapis.com/auth/youtubepartner',
                        'https://www.googleapis.com/auth/yt-analytics.readonly',
                    ])
                    ->with(['access_type' => 'offline', 'prompt' => 'consent'])
                    ->redirect();
            }
        } catch (Exception $e) {
            Log::error('Socialite redirect error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to initialize OAuth.');
        }

        abort(404);
    }

    /**
     * Handle callback with full business data extraction
     */
    public function callback(Request $request, $provider)
    {
        $validator = Validator::make(['provider' => $provider], [
            'provider' => 'required|in:facebook,google'
        ]);

        if ($validator->fails()) {
            return redirect()->route('dashboard')->with('error', 'Invalid provider.');
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
            //dd($socialUser);
        } catch (Exception $e) {
            Log::error("Socialite callback error: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Authentication failed: ' . $e->getMessage());
        }

        $user = Auth::user();

        try {
            // Save the provider token
            $sa = SocialAccount::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'account_id' => null
                ],
                [
                    'access_token' => Crypt::encryptString(json_encode([
                        'token' => $socialUser->token,
                        'scopes' => $request->get('granted_scopes', '')
                    ])),
                    'refresh_token' => $socialUser->refreshToken ? Crypt::encryptString($socialUser->refreshToken) : null,
                    'token_expires_at' => $socialUser->expiresIn ? Carbon::now()->addSeconds($socialUser->expiresIn) : null,
                    'account_name' => $socialUser->name ?? 'Unknown',
                    'account_email' => $socialUser->email ?? null,
                    'avatar' => $socialUser->avatar ?? null,
                    'permission_level' => 'full',
                ]
            );

            // Extract data based on provider
            if ($provider === 'facebook') {
                $this->extractFacebookData($socialUser->token, $user, $sa);
            } elseif ($provider === 'google') {
                $this->extractGoogleData($socialUser->token, $user, $sa);
            }

            return redirect()->route('facebook.index')
                ->with('success', '🚀 Connected successfully!')
                ->with('info', 'All accounts and data have been synchronized.');

        } catch (Exception $e) {
            Log::error("Error saving account: " . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to save account: ' . $e->getMessage());
        }
    }

    /**
     * Extract Facebook Data with Proper API Calls
     */
    private function extractFacebookData($accessToken, $user, $socialAccount)
    {
        try {
            $fb = new \Facebook\Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v19.0',
                'default_access_token' => $accessToken,
            ]);

            $extractedData = [];
            $connectedAssets = [];

            Log::info("🚀 Starting Facebook data extraction for user: " . $user->id);

            // 1. Get User Profile
            try {
                $response = $fb->get('/me?fields=id,name,email,first_name,last_name,picture,cover,age_range,link,location,gender');
                $extractedData['profile'] = $response->getGraphNode()->asArray();
                Log::info("✅ User profile extracted");
            } catch (\Exception $e) {
                Log::warning("Profile extraction failed: " . $e->getMessage());
            }

            // 2. Get User Posts
            try {
                $response = $fb->get('/me/posts?fields=id,message,created_time,permalink_url,attachments,likes.summary(true),comments.summary(true),shares&limit=25');
                $extractedData['posts'] = $response->getGraphEdge()->asArray();
                Log::info("✅ Extracted " . count($extractedData['posts']) . " posts");
            } catch (\Exception $e) {
                Log::info("Posts extraction failed: " . $e->getMessage());
            }

            // 3. Get User Photos
            try {
                $response = $fb->get('/me/photos?fields=id,images,created_time,link,name,album&limit=20');
                $extractedData['photos'] = $response->getGraphEdge()->asArray();
                Log::info("✅ Extracted " . count($extractedData['photos']) . " photos");
            } catch (\Exception $e) {
                Log::info("Photos extraction failed");
            }

            // 4. Get Pages and Instagram Accounts
            try {
                $response = $fb->get('/me/accounts?fields=id,name,access_token,category,fan_count,cover,link,location,instagram_business_account{id,name,username,profile_picture_url,followers_count,media_count,biography}&limit=100');
                $pages = $response->getGraphEdge()->asArray();
                $extractedData['pages'] = $pages;
                Log::info("✅ Extracted " . count($pages) . " pages");

                foreach ($pages as $page) {
                    // Save Facebook Page
                    $pageAccount = SocialAccount::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'provider' => 'facebook',
                            'account_id' => $page['id']
                        ],
                        [
                            'account_name' => $page['name'],
                            'access_token' => Crypt::encryptString(json_encode(['token' => $page['access_token']])),
                            'parent_account_id' => $socialAccount->id,
                            'meta_data' => json_encode($page),
                            'permission_level' => 'page',
                        ]
                    );

                    $connectedAssets[] = [
                        'type' => 'facebook_page',
                        'id' => $page['id'],
                        'name' => $page['name']
                    ];

                    // Save Instagram Account if connected
                    if (isset($page['instagram_business_account']['id'])) {
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
                                'meta_data' => json_encode($igData),
                                'permission_level' => 'business',
                            ]
                        );

                        $connectedAssets[] = [
                            'type' => 'instagram_business',
                            'id' => $igData['id'],
                            'name' => $igData['username'] ?? $igData['id']
                        ];

                        // Extract Instagram insights
                        $this->extractInstagramInsights($page['access_token'], $igData['id'], $igAccount);
                    }
                }
            } catch (\Exception $e) {
                Log::error("Pages extraction failed: " . $e->getMessage());
            }

            // 5. Get Ad Accounts
            try {
                $response = $fb->get('/me/adaccounts?fields=id,name,account_status,currency,amount_spent,balance,timezone&limit=50');
                $adAccounts = $response->getGraphEdge()->asArray();
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
                        'name' => $adAccount['name']
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Ad accounts extraction failed: " . $e->getMessage());
            }

            // 6. Get Business Accounts
            try {
                $response = $fb->get('/me/businesses?fields=id,name,vertical,timezone,created_time,owned_pages{id,name}&limit=20');
                $businesses = $response->getGraphEdge()->asArray();
                $extractedData['businesses'] = $businesses;
                Log::info("✅ Extracted " . count($businesses) . " businesses");
            } catch (\Exception $e) {
                Log::info("Businesses extraction failed: " . $e->getMessage());
            }

            // Update main account
            $socialAccount->update([
                'meta_data' => json_encode($extractedData),
                'connected_assets' => json_encode($connectedAssets),
                'asset_count' => count($connectedAssets),
                'last_synced_at' => now(),
            ]);

            Log::info("🎯 Facebook data extraction completed. Assets: " . count($connectedAssets));

        } catch (Exception $e) {
            Log::error('Facebook data extraction error: ' . $e->getMessage());
        }
    }

    /**
     * Extract Instagram Insights
     */
    private function extractInstagramInsights($pageAccessToken, $instagramId, $igAccount)
    {
        try {
            $fb = new \Facebook\Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v19.0',
                'default_access_token' => $pageAccessToken,
            ]);

            $instagramData = [];

            // Get Instagram profile insights
            try {
                $response = $fb->get("/{$instagramId}/insights?metric=impressions,reach,engagement,profile_views,follower_count&period=day&limit=7");
                $instagramData['insights'] = $response->getGraphEdge()->asArray();
            } catch (\Exception $e) {
                Log::warning("Instagram insights failed: " . $e->getMessage());
            }

            // Get Instagram media
            try {
                $response = $fb->get("/{$instagramId}/media?fields=id,caption,media_type,media_url,thumbnail_url,like_count,comments_count,timestamp,permalink&limit=12");
                $instagramData['media'] = $response->getGraphEdge()->asArray();
            } catch (\Exception $e) {
                Log::warning("Instagram media failed: " . $e->getMessage());
            }

            // Update Instagram account
            $igAccount->update([
                'meta_data' => json_encode(array_merge(
                    json_decode($igAccount->meta_data, true) ?? [],
                    $instagramData
                )),
                'last_synced_at' => now(),
            ]);

            Log::info("✅ Instagram insights extracted for: " . $igAccount->account_name);

        } catch (\Exception $e) {
            Log::warning("Instagram insights extraction failed: " . $e->getMessage());
        }
    }

    /**
     * Extract Google Data
     */
    private function extractGoogleData($accessToken, $user, $socialAccount)
    {
        try {
            $googleData = [];

            // Get Google Profile
            try {
                $response = Http::withToken($accessToken)
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
                $response = Http::withToken($accessToken)
                    ->get('https://www.googleapis.com/youtube/v3/channels', [
                        'part' => 'snippet,statistics,contentDetails',
                        'mine' => 'true',
                    ]);

                if ($response->successful()) {
                    $googleData['youtube_channels'] = $response->json()['items'] ?? [];
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
     * API: Get Social Accounts
     */
    public function getAccounts(Request $request)
    {
        try {
            $user = Auth::user();
            $provider = $request->get('provider');

            $query = SocialAccount::where('user_id', $user->id);
            
            if ($provider) {
                $query->where('provider', $provider);
            }

            $accounts = $query->get()->map(function ($account) {
                return [
                    'id' => $account->id,
                    'provider' => $account->provider,
                    'account_name' => $account->account_name,
                    'account_email' => $account->account_email,
                    'permission_level' => $account->permission_level,
                    'avatar' => $account->avatar,
                    'asset_count' => $account->asset_count,
                    'last_synced_at' => $account->last_synced_at,
                    'created_at' => $account->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $accounts,
                'count' => $accounts->count()
            ]);

        } catch (Exception $e) {
            Log::error('Get accounts API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch accounts'
            ], 500);
        }
    }

    /**
     * API: Get Account Details
     */
    public function getAccountDetails($id)
    {
        try {
            $user = Auth::user();
            $account = SocialAccount::where('user_id', $user->id)->findOrFail($id);

            $data = [
                'id' => $account->id,
                'provider' => $account->provider,
                'account_name' => $account->account_name,
                'account_email' => $account->account_email,
                'permission_level' => $account->permission_level,
                'avatar' => $account->avatar,
                'asset_count' => $account->asset_count,
                'last_synced_at' => $account->last_synced_at,
                'created_at' => $account->created_at,
                'meta_data' => json_decode($account->meta_data, true) ?? [],
                'connected_assets' => json_decode($account->connected_assets, true) ?? [],
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (Exception $e) {
            Log::error('Get account details API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Account not found'
            ], 404);
        }
    }

    /**
     * API: Sync Account Data
     */
    public function syncAccount($id)
    {
        try {
            $user = Auth::user();
            $account = SocialAccount::where('user_id', $user->id)->findOrFail($id);

            $token = $this->decryptToken($account->access_token);

            if ($account->provider === 'facebook') {
                $this->extractFacebookData($token, $user, $account);
            } elseif ($account->provider === 'google') {
                $this->extractGoogleData($token, $user, $account);
            }

            return response()->json([
                'success' => true,
                'message' => 'Account data synced successfully',
                'last_synced_at' => $account->fresh()->last_synced_at
            ]);

        } catch (Exception $e) {
            Log::error('Sync account API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync account data'
            ], 500);
        }
    }

    /**
     * API: Get Connected Assets
     */
    public function getConnectedAssets($accountId)
    {
        try {
            $user = Auth::user();
            $account = SocialAccount::where('user_id', $user->id)->findOrFail($accountId);

            $assets = [];

            if ($account->provider === 'facebook') {
                // Get Facebook pages
                $pages = SocialAccount::where('user_id', $user->id)
                    ->where('provider', 'facebook')
                    ->where('parent_account_id', $account->id)
                    ->get();

                // Get Instagram accounts
                $instagramAccounts = SocialAccount::where('user_id', $user->id)
                    ->where('provider', 'instagram')
                    ->where('parent_account_id', $account->id)
                    ->get();

                // Get Ad accounts
                $adAccounts = AdAccount::where('user_id', $user->id)
                    ->where('social_account_id', $account->id)
                    ->get();

                $assets = [
                    'facebook_pages' => $pages,
                    'instagram_accounts' => $instagramAccounts,
                    'ad_accounts' => $adAccounts,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $assets
            ]);

        } catch (Exception $e) {
            Log::error('Get connected assets API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch connected assets'
            ], 500);
        }
    }

    /**
     * API: Disconnect Account
     */
    public function disconnectAccount(Request $request, $id)
    {
        try {
            $user = Auth::user();
            $account = SocialAccount::where('user_id', $user->id)->findOrFail($id);

            // Delete connected assets
            if ($account->provider === 'facebook') {
                SocialAccount::where('user_id', $user->id)
                    ->where('parent_account_id', $account->id)
                    ->delete();

                AdAccount::where('user_id', $user->id)
                    ->where('social_account_id', $account->id)
                    ->delete();
            }

            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account disconnected successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Disconnect account API error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect account'
            ], 500);
        }
    }

    /**
     * Basic Connection Only (No Business Permissions)
     */
    public function redirectBasic($provider)
    {
        $validator = Validator::make(['provider' => $provider], [
            'provider' => 'required|in:facebook,google'
        ]);

        if ($validator->fails()) {
            return redirect()->route('dashboard')->with('error', 'Invalid provider.');
        }

        try {
            if ($provider === 'facebook') {
                return Socialite::driver('facebook')
                    ->scopes([
                        'email',
                        'public_profile',
                        'user_posts',
                        'user_photos',
                    ])
                    ->with(['response_type' => 'code'])
                    ->redirect();
            }

            return $this->redirect($provider);

        } catch (Exception $e) {
            Log::error('Socialite redirect error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to initialize OAuth.');
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
            throw new Exception('Failed to decrypt token.');
        }
    }
}