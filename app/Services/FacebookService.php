<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function getPagesWithInstagram(string $token): array
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
                            'connected_page' => $page['name'],
                        ]);
                    }
                }
            } else {
                Log::warning('Facebook API failed with status: ' . $response->status());
            }
        } catch (\Throwable $e) {
            Log::error('Error fetching pages: ' . $e->getMessage());
        }

        return [
            'pages' => $pages,
            'instagram_accounts' => $instagramAccounts,
        ];
    }
}
