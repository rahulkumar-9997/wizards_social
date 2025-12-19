<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UserFacebookPage;
use App\Models\UserInstagramPage;
use Carbon\Carbon;

class FacebookService
{
    protected array $fbConfig;
    public function __construct()
    {
        $this->fbConfig = [
            'base_url' => 'https://graph.facebook.com/',
            'graph_version' => 'v24.0/',
        ];
    }

    public function getPagesWithInstagram(string $token, int $userId): array
    {
        $fbPages = UserFacebookPage::where('user_id', $userId)->get();
        $igPages = UserInstagramPage::where('user_id', $userId)->get();

        $lastSync = collect([
            $fbPages->max('last_synced_at'),
            $igPages->max('last_synced_at'),
        ])->filter()->max();

        /** RETURN DB DATA IF SYNCED WITHIN 2 HOURS */
        if ($lastSync && Carbon::now()->diffInHours($lastSync) < 2) {
            return $this->fromDatabase($fbPages, $igPages);
        }

        /** OTHERWISE CALL API */
        return $this->fetchAndStoreFromApi($userId, $token);
    }

    private function fromDatabase($fbPages, $igPages): array
    {
        return [
            'pages' => $fbPages->map(fn($p) => [
                'id' => $p->facebook_page_id,
                'name' => $p->name,
                'category' => $p->category,
                'profile_picture' => $p->profile_picture,
            ])->values(),

            'instagram_accounts' => $igPages->map(fn($ig) => [
                'id' => $ig->instagram_id,
                'account_name' => $ig->account_name,
                'username' => $ig->user_name,
                'profile_picture' => $ig->profile_picture,
                'followers_count' => $ig->followers_count,
                'connected_page' => $ig->connected_page,
            ])->values(),
        ];
    }

    private function fetchAndStoreFromApi(int $userId, string $token): array
    {
        $pages = [];
        $instagramAccounts = [];

        try {
            $response = Http::timeout(15)->get(
                $this->fbConfig['base_url'] . $this->fbConfig['graph_version'] . 'me/accounts',
                [
                    'fields' => 'id,name,category,access_token,
                        picture{url},
                        instagram_business_account{
                            id,
                            username,
                            profile_picture_url,
                            followers_count
                        }',
                    'limit' => 60,
                    'access_token' => $token,
                ]
            );

            if (!$response->successful()) {
                Log::warning('Facebook API failed', ['status' => $response->status()]);
                return ['pages' => [], 'instagram_accounts' => []];
            }

            foreach ($response->json('data', []) as $page) {

                /** FACEBOOK PAGE */
                $fbPage = UserFacebookPage::updateOrCreate(
                    [
                        'user_id' => $userId,
                        'facebook_page_id' => $page['id'],
                    ],
                    [
                        'name' => $page['name'],
                        'category' => $page['category'] ?? null,
                        'access_token' => $page['access_token'] ?? null,
                        'profile_picture' => $page['picture']['data']['url'] ?? null,
                        'last_synced_at' => now(),
                    ]
                );

                $pages[] = [
                    'id' => $fbPage->facebook_page_id,
                    'name' => $fbPage->name,
                    'category' => $fbPage->category,
                    'profile_picture' => $fbPage->profile_picture,
                ];

                /** INSTAGRAM PAGE */
                if (!empty($page['instagram_business_account']['id'])) {
                    $ig = $page['instagram_business_account'];

                    $igPage = UserInstagramPage::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'instagram_id' => $ig['id'],
                        ],
                        [
                            'account_name' => $ig['username'],
                            'user_name' => $ig['username'],
                            'profile_picture' => $ig['profile_picture_url'] ?? null,
                            'followers_count' => $ig['followers_count'] ?? 0,
                            'access_token' => $page['access_token'] ?? null,
                            'connected_page' => $page['name'],
                            'last_synced_at' => now(),
                        ]
                    );

                    $instagramAccounts[] = [
                        'id' => $igPage->instagram_id,
                        'account_name' => $igPage->account_name,
                        'username' => $igPage->user_name,
                        'profile_picture' => $igPage->profile_picture,
                        'followers_count' => $igPage->followers_count,
                        'connected_page' => $igPage->connected_page,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Facebook sync error', ['error' => $e->getMessage()]);
        }

        return [
            'pages' => $pages,
            'instagram_accounts' => $instagramAccounts,
        ];
    }

    
}
