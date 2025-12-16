<?php

namespace App\Http\View\Composers;

use Illuminate\View\View;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Cache;
use App\Helpers\SocialTokenHelper;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SidebarComposer
{
    protected FacebookService $facebookService;

    public function __construct(FacebookService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    public function compose(View $view): void
    {
        $fbPages = [];
        $fbInstagram = collect();

        try {
            $user = Auth::user();
            if (!$user) {
                Log::info('SidebarComposer: No authenticated user found.');
                $view->with(compact('fbPages', 'fbInstagram'));
                return;
            }
            $user_id = $user->id;
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                Log::info("SidebarComposer: No Facebook account linked for user ID {$user->id}");
                $view->with(compact('fbPages', 'fbInstagram'));
                return;
            }
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            if (empty($token)) {
                Log::warning("SidebarComposer: Facebook token not found for user ID {$user->id}");
                $view->with(compact('fbPages', 'fbInstagram'));
                return;
            }
            $cacheKey = "fb_pages_for_user_{$user->id}_" . md5($token);

            $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($token, $user_id) {
                try {
                    return $this->facebookService->getPagesWithInstagram($token, $user_id);
                } catch (\Throwable $e) {
                    Log::error('SidebarComposer: FacebookService failed — ' . $e->getMessage());
                    return ['pages' => [], 'instagram_accounts' => collect()];
                }
            });
            
            $fbPages = collect($data['pages'] ?? []);
            $fbInstagram = collect($data['instagram_accounts'] ?? []);
            
        } catch (\Throwable $e) {
            Log::error('SidebarComposer: Unexpected error — ' . $e->getMessage());
            $fbPages = [];
            $fbInstagram = collect();
        }
        $view->with(compact('fbPages', 'fbInstagram'));
    }
}