<?php

namespace App\Livewire\Backend\Facebook;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\SocialAccount;
use Exception;

#[Layout('backend.pages.layouts.master')]
class Index extends Component
{
    public $mainAccount = null;
    public $dashboardData = [];
    public $stats = [];
    public $loading = true;
    public $error = null;
    public $success = null;
    public $tokenStatus = 'valid';
    public $tokenMessage = 'Token Valid';
    public $badgeClass = 'bg-success';
    public $iconClass = 'fa-check-circle';

    public function mount()
    {
        $this->loadFacebookData();
    }

    public function loadFacebookData()
    {
        try {
            $this->loading = true;
            $this->resetMessages();

            $user = Auth::user();
            $this->mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$this->mainAccount) {
                $this->setEmptyData();
                return;
            }

            if (!$this->checkAndRefreshToken()) {
                $this->error = 'Facebook connection expired. Please reconnect your account.';
                $this->setEmptyData();
                return;
            }

            $this->loadDashboardData();
            $this->updateTokenStatus();

        } catch (Exception $e) {
            $this->handleError($e, 'Failed to load Facebook data');
        } finally {
            $this->loading = false;
        }
    }

    private function checkAndRefreshToken()
    {
        try {
            $token = $this->decryptToken($this->mainAccount->access_token);
            
            if ($this->isTokenValid($token)) {
                return true;
            }

            Log::info('Facebook token expired, attempting to refresh...');
            
            if ($this->refreshFacebookToken()) {
                $this->success = 'Facebook token refreshed successfully!';
                return true;
            }

            $this->markAccountAsDisconnected();
            return false;

        } catch (Exception $e) {
            Log::error('Token check error: ' . $e->getMessage());
            return false;
        }
    }

    private function refreshFacebookToken()
    {
        try {
            $currentToken = $this->decryptToken($this->mainAccount->access_token);
            
            $response = Http::get('https://graph.facebook.com/v24.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => config('services.facebook.client_id'),
                'client_secret' => config('services.facebook.client_secret'),
                'fb_exchange_token' => $currentToken,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $newToken = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 5184000;

                $this->mainAccount->update([
                    'access_token' => Crypt::encryptString(json_encode([
                        'token' => $newToken,
                        'expires_at' => now()->addSeconds($expiresIn)->toDateTimeString()
                    ])),
                    'token_expires_at' => now()->addSeconds($expiresIn),
                ]);

                Cache::forget('fb_dashboard_' . $this->mainAccount->id);
                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Token refresh error: ' . $e->getMessage());
            return false;
        }
    }

    private function loadDashboardData()
    {
        $cacheKey = 'fb_dashboard_' . $this->mainAccount->id;
        
        $this->dashboardData = Cache::remember($cacheKey, now()->addMinutes(10), function () {
            $token = $this->decryptToken($this->mainAccount->access_token);
            
            if (!$this->isTokenValid($token)) {
                throw new Exception('Facebook token is invalid or expired');
            }

            return [
                'profile' => $this->getBasicProfile($token),
                'pages' => $this->getPagesWithInstagram($token)['pages'],
                'instagram_accounts' => $this->getPagesWithInstagram($token)['instagram_accounts'],
                'permissions' => $this->getEssentialPermissions($token),
                'analytics' => $this->getQuickAnalytics($token),
            ];
        });

        $this->stats = $this->calculateStats($this->dashboardData);
    }

    private function getBasicProfile($token)
    {
        try {
            $response = Http::timeout(8)->get('https://graph.facebook.com/v24.0/me', [
                'fields' => 'id,name,email,picture.width(200).height(200)',
                'access_token' => $token,
            ]);

            return $response->successful() ? $response->json() : ['name' => 'Unknown', 'id' => 'N/A'];
        } catch (Exception $e) {
            Log::warning('Profile fetch failed: ' . $e->getMessage());
            return ['name' => 'Unknown', 'id' => 'N/A'];
        }
    }

    private function getPagesWithInstagram($token)
    {
        $pages = [];
        $instagramAccounts = collect();

        try {
            $response = Http::timeout(10)->get('https://graph.facebook.com/v24.0/me/accounts', [
                'fields' => 'id,name,category,access_token,instagram_business_account{id,name,username,profile_picture_url,followers_count}',
                'limit' => 60,
                'access_token' => $token,
            ]);

            if ($response->successful()) {
                foreach ($response->json()['data'] ?? [] as $page) {
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
            }
        } catch (Exception $e) {
            Log::warning('Pages fetch failed: ' . $e->getMessage());
        }

        return compact('pages', 'instagramAccounts');
    }

    private function getEssentialPermissions($token)
    {
        try {
            $response = Http::timeout(5)->get('https://graph.facebook.com/v24.0/me/permissions', [
                'access_token' => $token,
            ]);

            return $response->successful() ? $response->json()['data'] ?? [] : [];
        } catch (Exception $e) {
            Log::warning('Permissions fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    private function getQuickAnalytics($token)
    {
        try {
            $response = Http::timeout(8)->get('https://graph.facebook.com/v24.0/me/adaccounts', [
                'fields' => 'id,name,account_status,amount_spent,currency',
                'limit' => 5,
                'access_token' => $token,
            ]);

            return $response->successful() ? ['ad_accounts' => $response->json()['data'] ?? []] : [];
        } catch (Exception $e) {
            Log::warning('Analytics fetch failed: ' . $e->getMessage());
            return [];
        }
    }

    private function isTokenValid($token)
    {
        try {
            $response = Http::timeout(5)->get('https://graph.facebook.com/v24.0/me', [
                'fields' => 'id',
                'access_token' => $token,
            ]);

            return $response->successful() && !empty($response->json()['id']);
        } catch (Exception $e) {
            Log::warning('Token validation error: ' . $e->getMessage());
            return false;
        }
    }

    private function calculateStats($dashboardData)
    {
        $instagramAccounts = $dashboardData['instagram_accounts'] ?? collect();
        
        return [
            'total_pages' => count($dashboardData['pages'] ?? []),
            'total_instagram_accounts' => $instagramAccounts->count(),
            'total_instagram_followers' => $instagramAccounts->sum('followers_count'),
            'total_ad_accounts' => count($dashboardData['analytics']['ad_accounts'] ?? []),
            'total_permissions_granted' => collect($dashboardData['permissions'] ?? [])
                ->where('status', 'granted')->count(),
        ];
    }

    private function decryptToken($encryptedToken)
    {
        if (empty($encryptedToken)) {
            throw new Exception('Empty token');
        }

        $decrypted = Crypt::decryptString($encryptedToken);
        $data = json_decode($decrypted, true);
        
        return is_array($data) && isset($data['token']) ? $data['token'] : $decrypted;
    }

    private function updateTokenStatus()
    {
        if (!$this->mainAccount?->token_expires_at) {
            $this->setTokenStatus('valid', 'Token Valid', 'bg-success', 'fa-check-circle');
            return;
        }

        if ($this->mainAccount->isTokenExpired()) {
            $this->setTokenStatus('expired', 'Token Expired', 'bg-danger', 'fa-exclamation-triangle');
        } elseif ($this->mainAccount->isTokenExpiringSoon()) {
            $this->setTokenStatus('expiring_soon', 'Expiring Soon', 'bg-warning', 'fa-clock');
        } else {
            $this->setTokenStatus('valid', 'Token Valid', 'bg-success', 'fa-check-circle');
        }
    }

    private function setTokenStatus($status, $message, $badgeClass, $iconClass)
    {
        $this->tokenStatus = $status;
        $this->tokenMessage = $message;
        $this->badgeClass = $badgeClass;
        $this->iconClass = $iconClass;
    }

    public function refreshToken()
    {
        try {
            if (!$this->mainAccount) {
                $this->error = 'No Facebook account connected.';
                return;
            }

            if ($this->refreshFacebookToken()) {
                $this->success = 'Facebook token refreshed successfully!';
                $this->loadFacebookData();
            } else {
                $this->error = 'Failed to refresh token. Please reconnect your Facebook account.';
            }

        } catch (Exception $e) {
            $this->handleError($e, 'Token refresh failed');
        }
    }

    public function disconnectAccount()
    {
        try {
            if (!$this->mainAccount) {
                $this->error = 'No Facebook account to disconnect.';
                return;
            }

            $this->mainAccount->disconnect();
            $this->setEmptyData();
            $this->success = 'Facebook account disconnected successfully!';
            Cache::flush();

        } catch (Exception $e) {
            $this->handleError($e, 'Failed to disconnect account');
        }
    }

    private function setEmptyData()
    {
        $this->mainAccount = null;
        $this->dashboardData = [];
        $this->stats = [
            'total_pages' => 0,
            'total_instagram_accounts' => 0,
            'total_instagram_followers' => 0,
            'total_ad_accounts' => 0,
            'total_permissions_granted' => 0,
        ];
    }

    private function resetMessages()
    {
        $this->error = null;
        $this->success = null;
    }

    private function handleError(Exception $e, $message)
    {
        Log::error('Facebook Livewire error: ' . $e->getMessage());
        $this->error = $message . ': ' . $e->getMessage();
    }

    public function dismissAlert()
    {
        $this->resetMessages();
    }

    public function render()
    {
        return view('livewire.backend.facebook.index')
            ->layout('backend.pages.layouts.master', [
                'title' => 'Facebook Integration',
            ]);
    }
}