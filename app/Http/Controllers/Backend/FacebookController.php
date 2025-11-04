<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\SocialAccount;
use Exception;
use Carbon\Carbon;

class FacebookController extends Controller
{
    private $fbConfig;

    public function __construct()
    {
        $this->fbConfig = [
            'app_id'        => config('services.facebook.client_id'),
            'app_secret'    => config('services.facebook.client_secret'),
            'graph_version' => 'v24.0',
            'base_url'      => 'https://graph.facebook.com/',
        ];
    }

    /**
     * Main index page - Optimized version
     */
    public function index()
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            $checkRefreshToken = false;
            $dashboardData = [];            
            if ($mainAccount) {
                $checkRefreshToken = $this->checkAndRefreshToken($mainAccount);
            }

            return view('backend.pages.facebook.index', [
                'mainAccount' => $mainAccount,
                'dashboardData' => $dashboardData,
                'stats' => $this->getEmptyStats(),
                'checkRefreshToken' => $checkRefreshToken
            ]);
        } catch (\Exception $e) {
            Log::error('Facebook index error: ' . $e->getMessage());
            return redirect()->route('facebook')->with('error', 'Failed to load Facebook data: ' . $e->getMessage());
        }
    }

    /**
     * Check and refresh Facebook token if expired
     */
    private function checkAndRefreshToken($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            if ($this->isTokenValid($token)) {
                return true; 
            }
            Log::info('Facebook token expired, attempting to refresh...');
            $refreshed = $this->refreshFacebookToken($account);
            if ($refreshed) {
                Log::info('Facebook token refreshed successfully');
                return true;
            }
            Log::warning('Facebook token refresh failed, disconnecting account');
            $this->markAccountAsDisconnected($account);
            return false;

        } catch (\Exception $e) {
            Log::error('Token check error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Refresh Facebook access token
     */
    private function refreshFacebookToken($account)
    {
        try {
            $currentToken = $this->decryptToken($account->access_token);
            $response = Http::get($this->fbConfig['base_url'] . 'oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->fbConfig['app_id'],
                'client_secret' => $this->fbConfig['app_secret'],
                'fb_exchange_token' => $currentToken,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $newToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 5184000; /* 60 days default */
                $encryptedToken = Crypt::encryptString(json_encode([
                    'token' => $newToken,
                    'expires_at' => now()->addSeconds($expiresIn)->toDateTimeString()
                ]));

                $account->update([
                    'access_token' => $encryptedToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'updated_at' => now(),
                ]);
                Cache::forget('fb_dashboard_' . $account->id);
                return true;
            }
            Log::warning('Token refresh failed: ' . $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark account as disconnected
     */
    private function markAccountAsDisconnected($account)
    {
        try {
            $account->update([
                'access_token' => null,
                'token_expires_at' => null,
                'updated_at' => now(),
            ]);
            Cache::forget('fb_dashboard_' . $account->id);            
        } catch (\Exception $e) {
            Log::error('Mark account disconnected error: ' . $e->getMessage());
        }
    }

    /**
     * Get all dashboard data with caching and parallel processing
     */
    private function getDashboardData($account)
    {
        $token = $this->decryptToken($account->access_token);            
        if (!$this->isTokenValid($token)) {
            throw new Exception('Facebook token is invalid or expired');
        }
        /* Get basic profile data */
        $profile = $this->getBasicProfile($token);            
        
        /* Get pages and Instagram accounts */
        $pagesData = $this->getPagesWithInstagram($token);            
        //return response()->json($pagesData);
        /* Get permissions */
        $permissions = $this->getEssentialPermissions($token);            
        
        /* Get analytics data*/
        $analytics = $this->getQuickAnalytics($token);
        //dd($analytics);
        return [
            'profile' => $profile,
            'pages' => $pagesData['pages'],
            'instagram_accounts' => $pagesData['instagram_accounts'],
            'permissions' => $permissions,
            'analytics' => $analytics,
        ];
    }

    /**
     * Get basic profile information
     */
    private function getBasicProfile($token)
    {
        try {
            $response = Http::timeout(8)
                ->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me', [
                    'fields' => 'id,name,email,picture.width(200).height(200)',
                    'access_token' => $token,
                ]);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::warning('Profile fetch failed with status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::warning('Profile fetch failed: ' . $e->getMessage());
        }

        return ['name' => 'Unknown', 'id' => 'N/A'];
    }

    /**
     * Get pages with Instagram accounts in optimized way
     */
    private function getPagesWithInstagram($token)
    {
        $pages = [];
        $instagramAccounts = collect();

        try {
            $response = Http::timeout(10)
                ->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/accounts', [
                    'fields' => 'id,name,category,access_token,instagram_business_account{id,name,username,profile_picture_url,followers_count}',
                    'limit' => 60,
                    'access_token' => $token,
                ]);
            
            if ($response->successful()) {
                $pagesData = $response->json()['data'] ?? [];

                foreach ($pagesData as $page) {
                    $pages[] = [
                        'id' => $page['id'],
                        'name' => $page['name'],
                        'category' => $page['category'] ?? 'N/A',
                        'access_token' => $page['access_token'] ?? null,
                    ];

                    if (isset($page['instagram_business_account']['id'])) {
                        $igData = $page['instagram_business_account'];
                        $instagramAccounts->push([
                            'id' => $igData['id'],
                            'account_name' => $igData['username'] ?? $page['name'] . ' (Instagram)',
                            'username' => $igData['username'] ?? null,
                            'profile_picture' => $igData['profile_picture_url'] ?? null,
                            'followers_count' => $igData['followers_count'] ?? 0,
                            'connected_page' => $page['name']
                        ]);
                    }
                }
            } else {
                Log::warning('Pages fetch failed with status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::warning('Pages fetch failed: ' . $e->getMessage());
        }

        return [
            'pages' => $pages,
            'instagram_accounts' => $instagramAccounts
        ];
    }

    /**
     * Get only essential permissions
     */
    private function getEssentialPermissions($token)
    {
        try {
            $response = Http::timeout(5)
                ->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/permissions', [
                    'access_token' => $token,
                ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            } else {
                Log::warning('Permissions fetch failed with status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::warning('Permissions fetch failed: ' . $e->getMessage());
        }

        return [];
    }
    
    /**
     * Get quick analytics data
     */
    private function getQuickAnalytics($token)
    {
        $analytics = [];
        try {
            $response = Http::timeout(8)
                ->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me/adaccounts', [
                    'fields' => 'id,name,account_status,amount_spent,currency',
                    'limit' => 20,
                    'access_token' => $token,
                ]);
             Log::info('Get adds account details: ' . print_r($response, true));           
            if ($response->successful()) {
                $analytics['ad_accounts'] = $response->json()['data'] ?? [];
            } else {
                Log::warning('Analytics fetch failed with status: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::warning('Analytics fetch failed: ' . $e->getMessage());
        }

        return $analytics;
    }

    /**
     * Check if token is valid
     */
    private function isTokenValid($token)
    {
        try {
            $response = Http::timeout(5)
                ->get($this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/me', [
                    'fields' => 'id',
                    'access_token' => $token,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return isset($data['id']) && !empty($data['id']);
            }
            if ($response->status() === 400) {
                $error = $response->json();
                Log::warning('Token validation failed: ' . ($error['error']['message'] ?? 'Unknown error'));
            }
            return false;

        } catch (\Exception $e) {
            Log::warning('Token validation error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate statistics for dashboard
     */
    private function calculateStats($dashboardData)
    {
        $instagramAccounts = $dashboardData['instagram_accounts'] ?? collect();
        
        return [
            'total_pages' => count($dashboardData['pages'] ?? []),
            'total_instagram_accounts' => $instagramAccounts->count(),
            'total_instagram_followers' => $instagramAccounts->sum('followers_count'),
            'total_ad_accounts' => count($dashboardData['analytics']['ad_accounts'] ?? []),
            'total_permissions_granted' => collect($dashboardData['permissions'] ?? [])
                ->where('status', 'granted')
                ->count(),
        ];
    }

    private function getEmptyStats()
    {
        return [
            'total_pages' => 0,
            'total_instagram_accounts' => 0,
            'total_instagram_followers' => 0,
            'total_ad_accounts' => 0,
            'total_permissions_granted' => 0,
        ];
    }

    /**
     * Decrypt token safely
     */
    private function decryptToken($encryptedToken)
    {
        try {
            if (empty($encryptedToken)) {
                throw new Exception('Empty token');
            }

            $decrypted = Crypt::decryptString($encryptedToken);
            $data = json_decode($decrypted, true);
            if (is_array($data) && isset($data['token'])) {
                return $data['token'];
            }            
            return $decrypted; 
        } catch (Exception $e) {
            Log::error('Token decryption failed: ' . $e->getMessage());
            throw new Exception('Token decryption failed: ' . $e->getMessage());
        }
    }

    /**
     * Manual token refresh endpoint (optional)
     */
    public function refreshToken()
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return redirect()->route('facebook.index')
                    ->with('error', 'No Facebook account connected.');
            }
            $success = $this->refreshFacebookToken($mainAccount);
            if ($success) {
                return redirect()->route('facebook.index')
                    ->with('success', 'Facebook token refreshed successfully!');
            } else {
                return redirect()->route('facebook.index')
                    ->with('error', 'Failed to refresh token. Please reconnect your Facebook account.');
            }

        } catch (\Exception $e) {
            Log::error('Manual token refresh error: ' . $e->getMessage());
            return redirect()->route('facebook.index')
                ->with('error', 'Token refresh failed: ' . $e->getMessage());
        }
    }

    public function fbUserProfileDataHtml(Request $request)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json([
                    'status' => 'error',
                    'html' => '<div class="alert alert-warning">No Facebook account connected.</div>'
                ]);
            }

            if (!$this->checkAndRefreshToken($mainAccount)) {
                return response()->json([
                    'status' => 'error',
                    'html' => '<div class="alert alert-danger">Facebook token expired. Please reconnect.</div>'
                ]);
            }

            $dashboardData = $this->getDashboardData($mainAccount);
            $stats = $this->calculateStats($dashboardData);

            $html = view('backend.pages.facebook.partials.profile-data', [
                'mainAccount' => $mainAccount,
                'dashboardData' => $dashboardData,
                'stats' => $stats
            ])->render();

            return response()->json(['status' => 'success', 'html' => $html]);
        } catch (\Exception $e) {
            Log::error('fbUserProfileDataHtml error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'html' => '<div class="alert alert-danger">Error loading Facebook data.</div>'
            ]);
        }
    }

}