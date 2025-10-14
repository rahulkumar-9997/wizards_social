<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;
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
    public function index()
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            $permissions = [];
            $analytics = [];
            $facebookData = [];
            $instagramAccounts = collect();
            $adAccounts = collect();
            if ($mainAccount) {
                $tokenTest = $this->testToken($mainAccount);
                if ($tokenTest['valid']) {
                    $permissions = $this->checkPermissions($mainAccount);
                    $analytics = $this->getComprehensiveAnalytics($mainAccount);
                    $facebookData = $this->getFacebookProfileData($mainAccount);
                    //dd($facebookData);
                    if (!empty($facebookData['pages'])) {
                        foreach ($facebookData['pages'] as $page) {
                            if (!empty($page['instagram_business_account']['id'])) {
                                $igId = $page['instagram_business_account']['id'];
                                $pageToken = $page['access_token'];
                                try {
                                    $igResponse = Http::get("https://graph.facebook.com/v21.0/{$igId}", [
                                        'fields' => 'username,followers_count,profile_picture_url',
                                        'access_token' => $pageToken,
                                    ])->json();

                                    $instagramAccounts->push((object)[
                                        'id' => $igId,
                                        'account_name' => $igResponse['username'] ?? $page['name'] . ' (Instagram)',
                                        'meta_data' => [
                                            'followers_count' => $igResponse['followers_count'] ?? 0,
                                            'profile_picture' => $igResponse['profile_picture_url'] ?? null,
                                        ],
                                    ]);
                                } catch (\Exception $ex) {
                                    Log::warning("IG data fetch failed for {$igId}: " . $ex->getMessage());
                                }
                            }
                        }
                    }
                } else {
                    Log::warning('Invalid Facebook token: ' . $tokenTest['error']);
                }
            }
            $stats = [
                'connected_since' => $mainAccount ? $mainAccount->created_at->format('M d, Y') : null,
                'last_synced'     => $mainAccount ? $mainAccount->last_synced_at?->format('M d, Y H:i') : null,
                'total_pages'     => !empty($facebookData['pages']) ? count($facebookData['pages']) : 0,
                'total_instagram_accounts' => $instagramAccounts->count(),
                'total_instagram_followers' => $instagramAccounts->sum(fn($ig) => $ig->meta_data['followers_count'] ?? 0),
            ];
            //dd($permissions);
            return view('backend.pages.facebook.index', compact(
                'mainAccount',
                'permissions',
                'analytics',
                'facebookData',
                'stats',
                'instagramAccounts',
                'adAccounts'
            ));

        } catch (\Exception $e) {
            Log::error('Facebook index error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to load Facebook data.');
        }
    }
    /**
     * Check Facebook Permissions
     */
    private function checkPermissions($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $data = $this->fbApiGet('me/permissions', ['access_token' => $token]);
            $permissionsList = $data['data'] ?? [];
            return $permissionsList;
        } catch (Exception $e) {
            Log::warning('Permission check failed: ' . $e->getMessage());
            return $this->getDefaultPermissions();
        }
    }

    private function getDefaultPermissions()
    {
        return [
            'email' => false,
            'public_profile' => false,
            'pages_show_list' => false,
            'pages_read_engagement' => false,
            'pages_read_user_content' => false,
            'instagram_basic' => false,
            'instagram_content_publish' => false,
            'ads_read' => false,
            'business_management' => false,
        ];
    }

    /**
     * Get Facebook Analytics
     */
    private function getComprehensiveAnalytics($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $analytics = [];
            /* Posts api*/
            // try {
            //     $data = $this->fbApiGet('me/posts', [
            //         'fields' => 'id,message,created_time,reactions.summary(true),comments.summary(true),attachments',
            //         'limit' => 100,
            //         'access_token' => $token,
            //     ]);
            //     $analytics['posts'] = $data['data'] ?? [];
            // } catch (Exception $e) {
            //     Log::warning('Posts fetch failed: ' . $e->getMessage());
            //     $analytics['posts'] = [];
            // }

            /* Pages api*/
            // try {
            //     $data = $this->fbApiGet('me/accounts', [
            //         'fields' => 'id,name,access_token,category,fan_count',
            //         'limit' => 10,
            //         'access_token' => $token,
            //     ]);
            //     $analytics['pages'] = $data['data'] ?? [];
            // } catch (Exception $e) {
            //     Log::warning('Pages fetch failed: ' . $e->getMessage());
            //     $analytics['pages'] = [];
            // }
            /* Ad Accounts api*/
            try {
                $data = $this->fbApiGet('me/adaccounts', [
                    'fields' => 'id,name,account_status,amount_spent,currency',
                    'limit' => 10,
                    'access_token' => $token,
                ]);
                $analytics['ad_accounts'] = $data['data'] ?? [];
            } catch (Exception $e) {
                Log::warning('Ad accounts fetch failed: ' . $e->getMessage());
                $analytics['ad_accounts'] = [];
            }

            return $analytics;
        } catch (Exception $e) {
            Log::error('Analytics error: ' . $e->getMessage());
            return ['pages' => [], 'ad_accounts' => []];
        }
    }

    /**
     * Get Facebook Profile + Posts + Pages
     */
    private function getFacebookProfileData($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            /**facebook profile data */
            $profile = $this->fbApiGet('me', [
                'fields' => 'id,name,email,picture,first_name,last_name',
                'access_token' => $token,
            ]);

            $posts = [];
            $pages = [];

            /* Recent posts */
            // try {
            //     $data = $this->fbApiGet('me/posts', [
            //         'fields' => 'id,message,created_time,reactions.summary(true),comments.summary(true),attachments',
            //         'limit' => 100,
            //         'access_token' => $token,
            //     ]);
            //     $posts = $data['data'] ?? [];
            // } catch (Exception $e) {
            //     Log::warning('Posts fetch failed: ' . $e->getMessage());
            // }

            /* facebook Pages*/
            try {
                $data = $this->fbApiGet('me/accounts', [
                    'fields' => 'id,name,access_token,instagram_business_account',
                    'limit' => 50,
                    'access_token' => $token,
                ]);
                $pages = $data['data'] ?? [];
            } catch (Exception $e) {
                Log::warning('Pages fetch failed: ' . $e->getMessage());
            }

            return [
                'profile'      => $profile,
                'posts'        => $posts,
                'pages'        => $pages,
                'total_posts'  => count($posts),
                'total_pages'  => count($pages)
            ];
        } catch (Exception $e) {
            Log::error('Profile data fetch failed: ' . $e->getMessage());
            return ['error' => 'Failed to fetch Facebook data: ' . $e->getMessage()];
        }
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
            return $data['token'] ?? $decrypted;
        } catch (Exception $e) {
            throw new Exception('Token decryption failed: ' . $e->getMessage());
        }
    }


    /**
     * Make Facebook Graph API Call via cURL
     */
    private function fbApiGet($endpoint, $params = [])
    {
        $url = $this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . '/' . ltrim($endpoint, '/');
        $url .= '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("cURL Error: $error");
        }

        curl_close($ch);

        $json = json_decode($response, true);
        if ($httpCode >= 400 || isset($json['error'])) {
            $errorMsg = $json['error']['message'] ?? 'Unknown API error';
            Log::warning("Facebook Graph API error on {$endpoint}: {$errorMsg}", $json['error'] ?? []);
            throw new Exception($errorMsg);
        }

        return $json;
    }

    /**
     * Test token validity
     */
    private function testToken($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);
            $data = $this->fbApiGet('me', [
                'fields' => 'id,name',
                'access_token' => $token,
            ]);
            return ['valid' => true, 'user_id' => $data['id'], 'name' => $data['name']];
        } catch (Exception $e) {
            return ['valid' => false, 'error' => $e->getMessage()];
        }
    }

}
