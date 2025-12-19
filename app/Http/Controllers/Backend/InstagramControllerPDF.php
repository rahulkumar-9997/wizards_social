<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Helpers\SocialTokenHelper;
use Carbon\Carbon;
use App\Models\SocialAccount;
use Exception;

class InstagramControllerPDF extends Controller
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
            /**
             * Fetch Instagram Profile (if connected)
            */
            $instagram = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'name,username,biography,followers_count,follows_count,media_count,profile_picture_url,stories',
                'access_token' => $token,
            ])->json();
            // return view('backend.pages.instagram.show', compact(
            //     'instagram',
            // ));
            return view('backend.pages.instagram.show-dubi', compact(
                'instagram',
            ));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return request()->ajax()
                ? response()->json(['error' => 'No internet connection.'], 503)
                : back()->with('error', 'No internet connection.');
        } catch (\Exception $e) {
            return request()->ajax()
                ? response()->json(['error' => $e->getMessage()], 500)
                : back()->with('error', $e->getMessage());
        }
    }

    public function instaReachPDF($id, Request $request)
    {
        try {
            $accountId = $id;
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();
            
            if (!$mainAccount) {
                if ($request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Facebook account not connected',
                        'error' => 'Facebook account not connected'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Facebook account not connected');
            }
            
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $result = [
                'reach' => [
                    'current' => 0,
                    'previous' => 0,
                    'paid' => 0,
                    'organic' => 0,
                    'followers' => 0,
                    'non_followers' => 0,
                    'percent_change' => 0,
                    'paid_percent' => 0,
                    'organic_percent' => 0,
                    'api_description' => ''
                ],
                'reach_prev' => [
                    'paid' => 0,
                    'organic' => 0,
                    'followers' => 0,
                    'non_followers' => 0,
                ]
            ];
            
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $days = (int) round($start->diffInDays($end));
            if ($days < 28) {
                $days += 1;
            }            
            /* Previous range (same length, directly before current)*/
            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();
            $since = $start->timestamp;
            $until = $end->timestamp;
            $previousSince = $prevStart->timestamp;
            $previousUntil = $prevEnd->timestamp;
            /* Current period data */
            $current_month = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'reach',
                'period' => 'day',
                'breakdown' => 'media_product_type,follow_type',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            if (isset($current_month['data'][0]['total_value']['breakdowns'][0]['results'])) {
                $result['reach']['api_description'] = $current_month['data'][0]['description'] ?? 'No description available';
                
                foreach ($current_month['data'][0]['total_value']['breakdowns'][0]['results'] as $r) {
                    $mediaType = $r['dimension_values'][0] ?? '';
                    $followType = $r['dimension_values'][1] ?? '';
                    $value = $r['value'] ?? 0;
                    
                    if ($mediaType === 'AD') {
                        $result['reach']['paid'] += $value;
                    } else {
                        $result['reach']['organic'] += $value;
                    }
                    
                    if ($followType === 'FOLLOWER') {
                        $result['reach']['followers'] += $value;
                    } elseif ($followType === 'NON_FOLLOWER') {
                        $result['reach']['non_followers'] += $value;
                    }

                    $result['reach']['current'] += $value;
                }
            }
            /* Previous month period data */
            $prevResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'reach',
                'period' => 'day',
                'breakdown' => 'media_product_type,follow_type',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            if (isset($prevResponse['data'][0]['total_value']['breakdowns'][0]['results'])) {
                foreach ($prevResponse['data'][0]['total_value']['breakdowns'][0]['results'] as $r) {
                    $mediaType = $r['dimension_values'][0] ?? '';
                    $followType = $r['dimension_values'][1] ?? '';
                    $value = $r['value'] ?? 0;
                    
                    if ($mediaType === 'AD') {
                        $result['reach_prev']['paid'] += $value;
                    } else {
                        $result['reach_prev']['organic'] += $value;
                    }
                    
                    if ($followType === 'FOLLOWER') {
                        $result['reach_prev']['followers'] += $value;
                    } elseif ($followType === 'NON_FOLLOWER') {
                        $result['reach_prev']['non_followers'] += $value;
                    }

                    $result['reach']['previous'] += $value;
                }
            }

            /* Calculate percentage changes and proportions */
            $prev = $result['reach']['previous'];
            $curr = $result['reach']['current'];
            
            $result['reach']['percent_change'] = $prev > 0
                ? round((($curr - $prev) / $prev) * 100, 2)
                : 0;
            
            $total = max($curr, 1);
            $result['reach']['paid_percent'] = round(($result['reach']['paid'] / $total) * 100, 2);
            $result['reach']['organic_percent'] = round(($result['reach']['organic'] / $total) * 100, 2);            
            $reach = $result['reach'];
            if ($request->ajax()) {                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Reach data loaded successfully.',
                    'reach' => $reach, 
                    'reachContent' => view('backend.pages.instagram.component.reach', compact('reach'))->render(),
                ]);
            }  
            return view('backend.pages.instagram.component.reach', compact('reach'));
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No internet connection.',
                    'error' => 'No internet connection.'
                ], 503);
            }
            return back()->with('error', 'No internet connection.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to load reach data.',
                    'error' => $e->getMessage()
                ], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function instaViewPDF($id, Request $request)
    {
        try {
            $accountId = $id;
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();
            
            if (!$mainAccount) {
                if ($request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Facebook account not connected',
                        'error' => 'Facebook account not connected'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Facebook account not connected');
            }
            
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            $result = [
                'view' => [
                    'current' => 0,
                    'previous' => 0,
                    'followers' => 0,
                    'non_followers' => 0,
                    'unknown' => 0,
                    'percent_change' => 0,
                    'followers_percent' => 0,
                    'non_followers_percent' => 0,
                    'api_description' => ''
                ]
            ];
            
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $days = (int) round($start->diffInDays($end));
            
            if ($days < 28) {
                $days += 1;
            }
            
            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();
            $since = $start->timestamp;
            $until = $end->timestamp;
            $previousSince = $prevStart->timestamp;
            $previousUntil = $prevEnd->timestamp;

            /* Current period data - VIEWS API */
            $current_month = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'views',
                'period' => 'day',
                'breakdown' => 'follow_type',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            // Log::info("Current Month Views Response: " . print_r($current_month, true));
            if (isset($current_month['data'][0]['total_value']['breakdowns'][0]['results'])) {
                $result['view']['api_description'] = $current_month['data'][0]['description'] ?? 'No description available';
                
                foreach ($current_month['data'][0]['total_value']['breakdowns'][0]['results'] as $r) {
                    $followType = $r['dimension_values'][0] ?? '';
                    $value = $r['value'] ?? 0;
                    
                    if ($followType === 'FOLLOWER') {
                        $result['view']['followers'] += $value;
                    } elseif ($followType === 'NON_FOLLOWER') {
                        $result['view']['non_followers'] += $value;
                    } elseif ($followType === 'UNKNOWN') {
                        $result['view']['unknown'] += $value;
                    }

                    $result['view']['current'] += $value;
                }
            }
            /* Previous period data - VIEWS API */
            $prevResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'views',
                'period' => 'day',
                'breakdown' => 'follow_type',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            // Log::info("Previous Month Views Response: " . print_r($prevResponse, true));
            if (isset($prevResponse['data'][0]['total_value']['breakdowns'][0]['results'])) {
                foreach ($prevResponse['data'][0]['total_value']['breakdowns'][0]['results'] as $r) {
                    $followType = $r['dimension_values'][0] ?? '';
                    $value = $r['value'] ?? 0;
                    
                    if ($followType === 'FOLLOWER') {
                        $result['view']['previous'] += $value; 
                    } elseif ($followType === 'NON_FOLLOWER') {
                        $result['view']['previous'] += $value;
                    } elseif ($followType === 'UNKNOWN') {
                        $result['view']['previous'] += $value;
                    }
                }
            }
            /* Calculate percentage changes and proportions */
            $prev = $result['view']['previous'];
            $curr = $result['view']['current'];
            
            $result['view']['percent_change'] = $prev > 0
                ? round((($curr - $prev) / $prev) * 100, 2)
                : 0;
            $view = $result['view'];            
            if ($request->ajax()) {                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Views data loaded successfully.',
                    'view' => $view, 
                    'viewContent' => view('backend.pages.instagram.component.view', compact('view'))->render(),
                ]);
            }  
            
            return view('backend.pages.instagram.component.view', compact('view'));
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No internet connection.',
                    'error' => 'No internet connection.'
                ], 503);
            }
            return back()->with('error', 'No internet connection.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to load views data.',
                    'error' => $e->getMessage()
                ], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function instaProfileReachGraphsPDF($id, Request $request)
    {
        $user = Auth::user();
        $mainAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', 'facebook')
            ->whereNull('parent_account_id')
            ->first();

        if (!$mainAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Facebook account not connected',
            ]);
        }

        $token = SocialTokenHelper::getFacebookToken($mainAccount);
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();
        $since = $start->timestamp;
        $until = $end->timestamp;
        $igId = $id;
        $url = "https://graph.facebook.com/v24.0/{$igId}/insights";

        try {
            $response = Http::timeout(15)->get($url, [
                'metric' => 'reach',
                'metric_type' => 'time_series',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ]);
            $data = $response->json();
            if (!$response->successful() || empty($data['data'][0]['values'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error']['message'] ?? 'Unable to fetch reach data',
                ]);
            }

            $api_description = $data['data'][0]['description'] ?? '';
            $chartData = collect($data['data'][0]['values'])->map(function ($item) {
                return [
                    'date' => date('Y-m-d', strtotime($item['end_time'])),
                    'value' => $item['value'],
                ];
            })->values()->toArray();
            return response()->json([
                'success' => true,
                'api_description' => $api_description,
                'data' => $chartData,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data: ' . $e->getMessage(),
            ]);
        }
    }

    public function instaProfileFollowUnfollowPDF($id, Request $request)
    {
        try {
            $accountId = $id;
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();
            
            if (!$mainAccount) {
                if ($request->ajax()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Facebook account not connected',
                    ], 400);
                }
                return redirect()->back()->with('error', 'Facebook account not connected');
            }
            
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            
            $start = \Carbon\Carbon::parse($startDate)->startOfDay();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay();
            $days = (int) round($start->diffInDays($end)) + 1;
            
            $prevEnd = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();
            
            $since = $start->timestamp;
            $until = $end->timestamp;
            $previousSince = $prevStart->timestamp;
            $previousUntil = $prevEnd->timestamp;
            $result = [
                'follows' => [
                    'current' => 0,
                    'previous' => 0,
                    'percent_change' => 0,
                    'api_description' => ''
                ],
                'unfollows' => [
                    'current' => 0,
                    'previous' => 0,
                    'percent_change' => 0
                ]
            ];
            
            /* ===== Current Period: Follows & Unfollows ===== */
            $current_month_followers = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'follows_and_unfollows',
                'period' => 'day',
                'breakdown' => 'follow_type',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            
            if (isset($current_month_followers['data'][0]['total_value']['breakdowns'][0]['results'])) {
                $result['follows']['api_description'] = $current_month_followers['data'][0]['description'] ?? '';
                
                foreach ($current_month_followers['data'][0]['total_value']['breakdowns'][0]['results'] as $resultItem) {
                    $type = $resultItem['dimension_values'][0] ?? '';
                    $value = $resultItem['value'] ?? 0;
                    
                    if ($type === 'FOLLOWER') {
                        $result['follows']['current'] += $value;
                    } elseif ($type === 'NON_FOLLOWER') {
                        $result['unfollows']['current'] += $value;
                    }
                }
            }
            
            /* ===== Previous Period: Follows & Unfollows ===== */
            $previous_month_followers = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'follows_and_unfollows',
                'period' => 'day',
                'breakdown' => 'follow_type',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            
            if (isset($previous_month_followers['data'][0]['total_value']['breakdowns'][0]['results'])) {
                foreach ($previous_month_followers['data'][0]['total_value']['breakdowns'][0]['results'] as $resultItem) {
                    $type = $resultItem['dimension_values'][0] ?? '';
                    $value = $resultItem['value'] ?? 0;
                    
                    if ($type === 'FOLLOWER') {
                        $result['follows']['previous'] += $value;
                    } elseif ($type === 'NON_FOLLOWER') {
                        $result['unfollows']['previous'] += $value;
                    }
                }
            }
            
            /* Calculate percentage changes
            For follows
            */
            $prevFollows = $result['follows']['previous'];
            $currFollows = $result['follows']['current'];
            $result['follows']['percent_change'] = $prevFollows > 0
                ? round((($currFollows - $prevFollows) / $prevFollows) * 100, 2)
                : 0;
            
            /* For unfollows */
            $prevUnfollows = $result['unfollows']['previous'];
            $currUnfollows = $result['unfollows']['current'];
            $result['unfollows']['percent_change'] = $prevUnfollows > 0
                ? round((($currUnfollows - $prevUnfollows) / $prevUnfollows) * 100, 2)
                : 0;
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Followers/Unfollowers data loaded successfully.',
                    'followersData' => $result,
                    'followersContent' => view('backend.pages.instagram.component.profile-followers', ['followersData' => $result])->render(),
                ]);
            }
            return view('backend.pages.instagram.component.profile-followers', ['followersData' => $result]);
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No internet connection.',
                ], 503);
            }
            return back()->with('error', 'No internet connection.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to load followers data.',
                    'error' => $e->getMessage()
                ], 500);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function instaViewGraphsMediyaTypePDF($id, Request $request)
    {
        $user = Auth::user();
        $mainAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', 'facebook')
            ->whereNull('parent_account_id')
            ->first();

        if (!$mainAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Facebook account not connected',
            ]);
        }

        $token = SocialTokenHelper::getFacebookToken($mainAccount);
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();
        $since = $start->timestamp;
        $until = $end->timestamp;
        $igId = $id;
        $url = "https://graph.facebook.com/v24.0/{$igId}/insights";
        
        try {
            $allResults = [];
            $totalViews = 0;
            $api_description = '';
            $nextUrl = null;
            $attempt = 0;
            $maxAttempts = 2;

            do {
                if ($nextUrl) {
                    $response = Http::timeout(15)->get($nextUrl);
                } else {
                    $response = Http::timeout(15)->get($url, [
                        'metric' => 'views',
                        'metric_type' => 'total_value',
                        'period' => 'day',
                        'breakdown' => 'media_product_type',
                        'since' => $since,
                        'until' => $until,
                        'access_token' => $token,
                    ]);
                }

                $data = $response->json();
                
                if (!$response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => $data['error']['message'] ?? 'Unable to fetch views data',
                    ]);
                }
                if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
                    if (empty($api_description) && isset($data['data'][0]['description'])) {
                        $api_description = $data['data'][0]['description'];
                    }
                    
                    if ($attempt === 0 && isset($data['data'][0]['total_value']['value'])) {
                        $totalViews = $data['data'][0]['total_value']['value'];
                    }
                    if (isset($data['data'][0]['total_value']['breakdowns'][0]['results'])) {
                        $results = $data['data'][0]['total_value']['breakdowns'][0]['results'];
                        if (is_array($results)) {
                            $allResults = array_merge($allResults, $results);
                        }
                    }
                }
                
                $nextUrl = $data['paging']['next'] ?? null;
                $attempt++;
                
            } while ($nextUrl && $attempt < $maxAttempts);
            
            $categories = [];
            $values = [];
            $typeTotals = [];
            
            foreach ($allResults as $item) {
                if (!is_array($item)) continue;
                
                $mediaType = $item['dimension_values'][0] ?? '';
                $value = $item['value'] ?? 0;
                
                if ($value < 1) continue;
                
                if (!isset($typeTotals[$mediaType])) {
                    $typeTotals[$mediaType] = 0;
                }
                $typeTotals[$mediaType] += $value;
            }
            
            foreach ($typeTotals as $mediaType => $totalValue) {
                if ($totalValue < 1) continue;
                
                switch ($mediaType) {
                    case 'POST':
                        $label = 'Posts';
                        break;
                    case 'STORY':
                        $label = 'Stories';
                        break;
                    case 'REEL':
                        $label = 'Reels';
                        break;
                    case 'AD':
                        $label = 'Ads';
                        break;
                    case 'CAROUSEL_CONTAINER':
                        $label = 'Carousels';
                        break;
                    case 'IGTV':
                        $label = 'IGTV';
                        break;
                    case 'DEFAULT_DO_NOT_USE':
                        $label = 'Others';
                        break;
                    default:
                        $label = $mediaType;
                }

                $categories[] = $label;
                $values[] = $totalValue;
            }
            
            if ($totalViews === 0 && !empty($values)) {
                $totalViews = array_sum($values);
            }
            
            return response()->json([
                'success' => true,
                'total_views' => $totalViews,
                'categories' => $categories,
                'values' => $values,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'api_description' => $api_description,
                'pagination_count' => $attempt
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data: ' . $e->getMessage(),
            ]);
        }
    }
}

   


    


    

