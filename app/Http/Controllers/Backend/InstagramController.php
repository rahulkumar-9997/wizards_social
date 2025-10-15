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
use Carbon\Carbon;
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
                if (request()->ajax()) {
                    return response()->json(['error' => 'Facebook account not connected'], 400);
                }
                return redirect()->back()->with('error', 'Facebook account not connected');
            }            
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            /* Fetch Instagram account details */
            $instagram = Http::get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'name,username,biography,followers_count,follows_count,media_count,profile_picture_url',
                'access_token' => $token,
            ])->json();            
            /* Fetch media with pagination */
            $page = request()->get('page', 1);
            $limit = 12; 
            $params = [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
                'access_token' => $token,
                'limit' => $limit,
            ];
            /*Handle Facebook cursor-based pagination */
            if (request()->has('after')) {
                $params['after'] = request()->get('after');
            } elseif (request()->has('before')) {
                $params['before'] = request()->get('before');
            }
            $mediaResponse = Http::get("https://graph.facebook.com/v24.0/{$id}/media", $params)->json();
            $media = $mediaResponse['data'] ?? [];
            $pagination = $mediaResponse['paging'] ?? [];
            $totalPosts = $instagram['media_count'] ?? 0;
            $totalLikes = collect($media)->sum(fn($m) => $m['like_count'] ?? 0);
            $totalReels = collect($media)->where('media_type', 'VIDEO')->count();
            $totalImages = collect($media)->where('media_type', 'IMAGE')->count();
            $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $mediaCollection = collect($media);            
            $paginatedMedia = new \Illuminate\Pagination\LengthAwarePaginator(
                $mediaCollection,
                $totalPosts,
                $limit,
                $currentPage,
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => view('backend.pages.instagram.partials.instagram-media-table', [
                        'media' => $paginatedMedia,
                        'paginatedMedia' => $paginatedMedia
                    ])->render(),
                    'pagination' => $pagination
                ]);
            }

            return view('backend.pages.instagram.show', compact(
                'instagram',
                'paginatedMedia',
                'pagination',
                'totalPosts',
                'totalReels',
                'totalImages',
                'totalLikes'
            ));

        } catch (\Exception $e) {
            Log::error('Instagram fetch failed: ' . $e->getMessage());            
            if (request()->ajax()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
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
        $range = $request->get('range', '7d');

        $days = match ($range) {
            '1M' => 30,
            '6M' => 180,
            '1Y' => 365,
            default => 7,
        };

        $since = now()->subDays($days)->startOfDay()->timestamp;
        $until = now()->endOfDay()->timestamp;

        // Metrics you want to display in graph
        $metrics = 'reach,likes,comments,views';

        // Fetch data from Instagram Graph API
        $response = Http::get("https://graph.facebook.com/v24.0/{$id}/insights", [
            'metric' => $metrics,
            'metric_type' => 'total_value',
            'period' => 'day',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        // If Graph API returns error
        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 400);
        }

        // Initialize arrays
        $dates = [];
        $likes = [];
        $comments = [];
        $reach = [];
        $views = [];

        // Process each metric data
        foreach ($response['data'] ?? [] as $metric) {
            $name = $metric['name'] ?? '';
            foreach ($metric['values'] ?? [] as $v) {
                if (isset($v['end_time'])) {
                    $date = Carbon::parse($v['end_time'])->subDay()->format('Y-m-d');
                    $value = $v['value'] ?? 0;
                    $dates[$date] = $date;

                    switch ($name) {
                        case 'likes':
                            $likes[$date] = $value;
                            break;
                        case 'comments':
                            $comments[$date] = $value;
                            break;
                        case 'reach':
                            $reach[$date] = $value;
                            break;
                        case 'views':
                            $views[$date] = $value;
                            break;
                    }
                }
            }
        }

        // Generate full date range (to fill missing dates)
        $filledDates = collect(
            collect(range(0, $days - 1))
                ->map(fn($i) => now()->subDays($days - 1 - $i)->format('Y-m-d'))
        );

        $result = [
            'dates' => [],
            'likes' => [],
            'comments' => [],
            'reach' => [],
            'views' => [],
        ];

        // Fill graph data with 0 for missing days
        foreach ($filledDates as $d) {
            $result['dates'][] = $d;
            $result['likes'][] = $likes[$d] ?? 0;
            $result['comments'][] = $comments[$d] ?? 0;
            $result['reach'][] = $reach[$d] ?? 0;
            $result['views'][] = $views[$d] ?? 0;
        }

        return response()->json($result);

    } catch (\Exception $e) {
        Log::error('Instagram Graph Error: ' . $e->getMessage());
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