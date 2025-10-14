<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Helpers\SocialTokenHelper;
use App\Models\SocialAccount;
use Exception;

class InstagramController extends Controller
{
    /**
     * Instagram Integration Page
     */
   
    public function show($id)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return redirect()->back()->with('error', 'Facebook account not connected');
            }

            $token = SocialTokenHelper::getFacebookToken($mainAccount);

            // Fetch Instagram account details
            $instagram = Http::get("https://graph.facebook.com/v21.0/{$id}", [
                'fields' => 'name,username,biography,followers_count,follows_count,media_count,profile_picture_url',
                'access_token' => $token,
            ])->json();

            // Fetch media (posts & reels)
            $mediaResponse = Http::get("https://graph.facebook.com/v21.0/{$id}/media", [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,insights.metric(impressions,reach,engagement,video_views)',
                'access_token' => $token,
                'limit' => 10
            ])->json();

            $media = $mediaResponse['data'] ?? [];

            // Aggregate stats
            $totalPosts = $instagram['media_count'] ?? count($media);
            $totalLikes = collect($media)->sum(fn($m) => $m['like_count'] ?? 0);
            $totalReels = collect($media)->where('media_type', 'VIDEO')->count();
            $totalImages = collect($media)->where('media_type', 'IMAGE')->count();

            return view('backend.pages.instagram.show', compact(
                'instagram',
                'media',
                'totalPosts',
                'totalReels',
                'totalImages',
                'totalLikes'
            ));

        } catch (\Exception $e) {
            Log::error('Instagram fetch failed: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function likesGraph($id, Request $request)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['error' => 'Facebook account not connected'], 400);
            }

            $token = SocialTokenHelper::getFacebookToken($mainAccount);

            $range = $request->get('range', 'all');
            $days = match ($range) {
                '1M' => 30,
                '6M' => 180,
                '1Y' => 365,
                default => 90,
            };

            $mediaResponse = Http::get("https://graph.facebook.com/v21.0/{$id}/media", [
                'fields' => 'id,caption,media_type,media_url,permalink,timestamp,like_count,comments_count,insights.metric(impressions,reach,engagement,video_views)',
                'access_token' => $token,
                'limit' => 100,
            ])->json();

            $media = $mediaResponse['data'] ?? [];

            if (empty($media)) {
                return response()->json(['dates' => [], 'likes' => [], 'comments' => [], 'views' => []]);
            }

            $data = collect($media)->map(function ($m) {
                $views = 0;
                if(isset($m['insights']['data'])) {
                    foreach($m['insights']['data'] as $insight) {
                        if($insight['name'] === 'video_views') $views = $insight['values'][0]['value'] ?? 0;
                    }
                }
                return [
                    'date' => date('Y-m-d', strtotime($m['timestamp'])),
                    'likes' => $m['like_count'] ?? 0,
                    'comments' => $m['comments_count'] ?? 0,
                    'views' => $views,
                ];
            });

            $grouped = $data->groupBy('date')->map(function ($items) {
                return [
                    'likes' => $items->sum('likes'),
                    'comments' => $items->sum('comments'),
                    'views' => $items->sum('views'),
                ];
            });

            $sorted = $grouped->sortKeys();

            if ($range !== 'all') {
                $cutoffDate = now()->subDays($days)->format('Y-m-d');
                $sorted = $sorted->filter(fn($v, $date) => $date >= $cutoffDate);
            }

            return response()->json([
                'dates' => $sorted->keys()->values(),
                'likes' => $sorted->pluck('likes')->values(),
                'comments' => $sorted->pluck('comments')->values(),
                'views' => $sorted->pluck('views')->values(),
            ]);

        } catch (\Exception $e) {
            Log::error('Instagram likesGraph error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    
    public function insights($id)
    {
        try {
            $user = Auth::user();

            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['error' => 'Facebook account not connected'], 400);
            }

            $token = SocialTokenHelper::getFacebookToken($mainAccount);

            // Fetch Instagram Insights
            $insightsResponse = Http::get("https://graph.facebook.com/v21.0/{$id}/insights", [
                'metric' => 'profile_views,profile_links_taps,impressions,reach,website_clicks',
                'period' => 'day',
                'access_token' => $token,
            ])->json();

            $metrics = [];

            if (isset($insightsResponse['data'])) {
                foreach ($insightsResponse['data'] as $metric) {
                    $name = $metric['name'];
                    $values = $metric['values'] ?? [];
                    foreach ($values as $v) {
                        $metrics[$name]['dates'][] = $v['end_time'] ?? '';
                        $metrics[$name]['values'][] = $v['value'] ?? 0;
                    }
                }
            }

            return response()->json($metrics);

        } catch (\Exception $e) {
            Log::error('Instagram insights fetch failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}