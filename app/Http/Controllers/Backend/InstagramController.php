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
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp, like_count,comments_count',
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

   public function metricsGraph($id, Request $request)
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
            $metrics = 'reach,likes,comments,views';
            $period = $request->get('period', 'week');
            $url = "https://graph.facebook.com/v24.0/{$id}/media";
            $response = Http::get($url, [
                'fields' => "timestamp,media_product_type,insights.metric({$metrics}).period({$period})",
                'access_token' => $token,
            ])->json();
            if (isset($response['error'])) {
                return response()->json(['error' => $response['error']['message']], 400);
            }
            $dates = [];
            $dataSets = [
                'reach' => [],
                'likes' => [],
                'comments' => [],
                'views' => [],
            ];
            foreach ($response['data'] ?? [] as $media) {
                $timestamp = $media['timestamp'] ?? null;
                if (!$timestamp) continue;

                $date = Carbon::parse($timestamp)->format('Y-m-d');
                if (!in_array($date, $dates)) $dates[] = $date;

                $reach = $likes = $comments = $views = 0;

                foreach ($media['insights']['data'] ?? [] as $metric) {
                    $value = $metric['values'][0]['value'] ?? 0;
                    switch ($metric['name']) {
                        case 'reach': $reach = $value; break;
                        case 'likes': $likes = $value; break;
                        case 'comments': $comments = $value; break;
                        case 'views': $views = $value; break;
                    }
                }

                // Aggregate per date
                $dataSets['reach'][$date] = ($dataSets['reach'][$date] ?? 0) + $reach;
                $dataSets['likes'][$date] = ($dataSets['likes'][$date] ?? 0) + $likes;
                $dataSets['comments'][$date] = ($dataSets['comments'][$date] ?? 0) + $comments;
                $dataSets['views'][$date] = ($dataSets['views'][$date] ?? 0) + $views;
            }

            sort($dates);

            $final = [
                'dates' => $dates,
                'reach' => array_map(fn($d) => $dataSets['reach'][$d] ?? 0, $dates),
                'likes' => array_map(fn($d) => $dataSets['likes'][$d] ?? 0, $dates),
                'comments' => array_map(fn($d) => $dataSets['comments'][$d] ?? 0, $dates),
                'views' => array_map(fn($d) => $dataSets['views'][$d] ?? 0, $dates),
            ];

            return response()->json($final);

        } catch (\Exception $e) {
            Log::error('Instagram metricsGraph error: ' . $e->getMessage());
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