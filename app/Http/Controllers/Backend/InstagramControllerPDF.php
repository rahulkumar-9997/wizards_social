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

    public function instaPostReelPDF($id, Request $request)
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
        $accountId = $id;
        
        /* ===== Current Media ===== */
        $mediaResponseCurrent = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/media", [
            'fields' => 'media_type,media_product_type,like_count,comments_count,timestamp',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        $posts = $stories = $reels = $totalInteractions = 0;
        if (isset($mediaResponseCurrent['data'])) {
            foreach ($mediaResponseCurrent['data'] as $media) {
                $mediaType = $media['media_type'] ?? '';
                $productType = $media['media_product_type'] ?? '';
                
                if ($productType === 'STORIES') {
                    $stories++;
                } elseif ($productType === 'REELS') {
                    $reels++;
                } elseif ($mediaType === 'CAROUSEL_ALBUM' || $mediaType === 'IMAGE' || $mediaType === 'VIDEO' && $productType === 'FEED') {
                    $posts++;
                }
                $likes = $media['like_count'] ?? 0;
                $comments = $media['comments_count'] ?? 0;
                $totalInteractions += $likes + $comments;
            }
        }

        /* ===== Previous Media ===== */
        $mediaResponsePrevious = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/media", [
            'fields' => 'media_type,media_product_type,like_count,comments_count,timestamp',
            'since' => $previousSince,
            'until' => $previousUntil,
            'access_token' => $token,
        ])->json();

        $pre_posts = $pre_stories = $pre_reels = $pre_totalInteractions = 0;
        if (isset($mediaResponsePrevious['data'])) {
            foreach ($mediaResponsePrevious['data'] as $mediaPrev) {
                $preMediaType = $mediaPrev['media_type'] ?? '';
                $preProductType = $mediaPrev['media_product_type'] ?? '';

                if ($preProductType === 'STORIES'){ 
                    $pre_stories++;
                }
                elseif ($preProductType === 'REELS'){
                    $pre_reels++;
                }
                elseif ($preMediaType === 'CAROUSEL_ALBUM' || $preMediaType === 'IMAGE' || $preMediaType === 'VIDEO' && $preProductType === 'FEED'){
                    $pre_posts++;
                }

                $pre_totalInteractions += ($mediaPrev['like_count'] ?? 0) + ($mediaPrev['comments_count'] ?? 0);
            }
        }
        $postsChange = $pre_posts > 0 ? (($posts - $pre_posts) / $pre_posts) * 100 : 0;
        $reelsChange = $pre_reels > 0 ? (($reels - $pre_reels) / $pre_reels) * 100 : 0;
        /** ===== Final Combined Data ===== */
        $data = [            
            'posts' => [
                'previous' => $pre_posts, 
                'current' => $posts,
                'change' => round($postsChange, 1),
                'change_type' => $postsChange >= 0 ? 'up' : 'down'
            ],
            'reels' => [
                'previous' => $pre_reels, 
                'current' => $reels,
                'change' => round($reelsChange, 1),
                'change_type' => $reelsChange >= 0 ? 'up' : 'down'
            ],
        ];
        
        return response()->json([
            'status' => 'success',
            'message' => 'Post, Reels data loaded successfully.',
            'data' => $data, 
            'postReelsContent' => view('backend.pages.instagram.component.post-reels', compact('data'))->render(),
        ]);
    }

    public function instaTotalInteractionsPDF($id, Request $request)
    {
        try {
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
            $accountId = $id;
            $result = [
                'interactions' => [
                    'total_interactions_current' => 0,
                    'total_interactions_previous' => 0,
                    'total_interactions_percent_change' => 0,
                    'interactions_api_description' => ''
                ]
            ];
            
            /* CURRENT MONTH DATA */
            $currentInteractionsResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'total_interactions',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();            
            // Log::info('Current Month Interactions Response: ' . print_r($currentInteractionsResponse, true));

            if (!empty($currentInteractionsResponse['data'])) {
                foreach ($currentInteractionsResponse['data'] as $metric) {
                    $metricName = $metric['name'] ?? '';
                    $value = $metric['total_value']['value'] ?? 0;
                    
                    if ($metricName === 'total_interactions') {
                        $result['interactions']['total_interactions_current'] = (int) $value;
                        $result['interactions']['interactions_api_description'] = $metric['description'] ?? '';
                        break;
                    }
                }
            }
            
            /* PREVIOUS MONTH DATA */
            $prevInteractionsResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'total_interactions',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            
            // Log::info('Previous Month Interactions Response: ' . print_r($prevInteractionsResponse, true));

            if (!empty($prevInteractionsResponse['data'])) {
                foreach ($prevInteractionsResponse['data'] as $metric) {
                    $metricName = $metric['name'] ?? '';
                    $value = $metric['total_value']['value'] ?? 0;

                    if ($metricName === 'total_interactions') {
                        $result['interactions']['total_interactions_previous'] = (int) $value;
                        break; 
                    }
                }
            }      

            $prevInteractions = $result['interactions']['total_interactions_previous'];
            $currInteractions = $result['interactions']['total_interactions_current'];
            
            if ($prevInteractions > 0) {
                $change = (($currInteractions - $prevInteractions) / $prevInteractions) * 100;
                $result['interactions']['total_interactions_percent_change'] = round($change, 1);
            } else {
                $result['interactions']['total_interactions_percent_change'] = $currInteractions > 0 ? 100 : 0;
            }
            $data = [
                'total_interactions' => [
                    'previous' => $result['interactions']['total_interactions_previous'],
                    'current' => $result['interactions']['total_interactions_current'],
                    'change' => abs($result['interactions']['total_interactions_percent_change']),
                    'change_type' => $result['interactions']['total_interactions_percent_change'] >= 0 ? 'up' : 'down',
                    'description' => $result['interactions']['interactions_api_description']
                ]
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Total Interactions data loaded successfully.',
                'data' => $data, 
                'totalInteractionContent' => view('backend.pages.instagram.component.total-interactions', compact('data'))->render(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Total Interactions API Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load total interactions data: ' . $e->getMessage(),
                'data' => [],
                'totalInteractionContent' => view('backend.pages.instagram.component.total-interactions', [
                    'data' => [
                        'total_interactions' => [
                            'previous' => 0,
                            'current' => 0,
                            'change' => 0,
                            'change_type' => 'down',
                            'description' => 'Failed to load data'
                        ]
                    ]
                ])->render(),
            ]);
        }
    }

    public function instaTotalInteractionsLikeCommentsPDF($id, Request $request)
    {
        try {
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
            $accountId = $id;

            /* ===== Interactions (Current) ===== */
            $currentInteractions = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'likes,comments,saves,shares,reposts',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();            
            //Log::info('Current month total Interactions: ' . print_r($currentInteractions, true));
            
            $likesInteractionCurrent = $commentsInteractionCurrent = $savesInteractionCurrent = $sharesInteractionCurrent = $repostsInteractionCurrent = 0;
            $likes_desc_current_int = $comments_desc_current_int = $saves_desc_current_int = $shares_desc_current_int = $reposts_desc_current_int = '';

            if (isset($currentInteractions['data'])) {
                foreach ($currentInteractions['data'] as $metric) {
                    $name = $metric['name'] ?? '';
                    $totalValueIntCurrent = $metric['total_value']['value'] ?? 0;
                    $currentInterDesc = $metric['description'] ?? '';
                    
                    switch ($name) {
                        case 'likes': 
                            $likesInteractionCurrent = (int) $totalValueIntCurrent; 
                            $likes_desc_current_int = $currentInterDesc; 
                            break;
                        case 'comments': 
                            $commentsInteractionCurrent = (int) $totalValueIntCurrent; 
                            $comments_desc_current_int = $currentInterDesc; 
                            break;
                        case 'saves': 
                            $savesInteractionCurrent = (int) $totalValueIntCurrent; 
                            $saves_desc_current_int = $currentInterDesc; 
                            break;
                        case 'shares': 
                            $sharesInteractionCurrent = (int) $totalValueIntCurrent; 
                            $shares_desc_current_int = $currentInterDesc; 
                            break;
                        case 'reposts': 
                            $repostsInteractionCurrent = (int) $totalValueIntCurrent; 
                            $reposts_desc_current_int = $currentInterDesc; 
                            break;
                    }
                }
            }            
            //Log::info("Final Current Interaction Counts - Likes: {$likesInteractionCurrent}, Comments: {$commentsInteractionCurrent}, Saves: {$savesInteractionCurrent}, Shares: {$sharesInteractionCurrent}, Reposts: {$repostsInteractionCurrent}");
            
            /* ===== Interactions (Previous) ===== */
            $previousInteractions = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'likes,comments,saves,shares,reposts',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();            
            //Log::info('Previous period total Interactions: ' . print_r($previousInteractions, true));

            $likesInteractionPrevious = $commentsInteractionPrevious = $savesInteractionPrevious = $sharesInteractionPrevious = $repostsInteractionPrevious = 0;

            if (isset($previousInteractions['data'])) {
                foreach ($previousInteractions['data'] as $metric) {
                    $name = $metric['name'] ?? '';
                    $totalValueIntPrev = $metric['total_value']['value'] ?? 0;
                    
                    switch ($name) {
                        case 'likes':
                            $likesInteractionPrevious = (int) $totalValueIntPrev;
                            break;
                        case 'comments':
                            $commentsInteractionPrevious = (int) $totalValueIntPrev;
                            break;
                        case 'saves':
                            $savesInteractionPrevious = (int) $totalValueIntPrev;
                            break;
                        case 'shares':
                            $sharesInteractionPrevious = (int) $totalValueIntPrev;
                            break;
                        case 'reposts':
                            $repostsInteractionPrevious = (int) $totalValueIntPrev;
                            break;
                    }
                }
            }
            $likesChange = $likesInteractionPrevious > 0 
                ? (($likesInteractionCurrent - $likesInteractionPrevious) / $likesInteractionPrevious) * 100 
                : ($likesInteractionCurrent > 0 ? 100 : 0);
            
            $commentsChange = $commentsInteractionPrevious > 0 
                ? (($commentsInteractionCurrent - $commentsInteractionPrevious) / $commentsInteractionPrevious) * 100 
                : ($commentsInteractionCurrent > 0 ? 100 : 0);
            
            $savesChange = $savesInteractionPrevious > 0 
                ? (($savesInteractionCurrent - $savesInteractionPrevious) / $savesInteractionPrevious) * 100 
                : ($savesInteractionCurrent > 0 ? 100 : 0);
            
            $sharesChange = $sharesInteractionPrevious > 0 
                ? (($sharesInteractionCurrent - $sharesInteractionPrevious) / $sharesInteractionPrevious) * 100 
                : ($sharesInteractionCurrent > 0 ? 100 : 0);
            
            $repostsChange = $repostsInteractionPrevious > 0 
                ? (($repostsInteractionCurrent - $repostsInteractionPrevious) / $repostsInteractionPrevious) * 100 
                : ($repostsInteractionCurrent > 0 ? 100 : 0);
            
            $data = [            
                'likes' => [
                    'api_description' => $likes_desc_current_int,
                    'previous' => $likesInteractionPrevious,
                    'current' => $likesInteractionCurrent,
                    'change' => round($likesChange, 1),
                    'change_type' => $likesChange >= 0 ? 'up' : 'down'
                ],
                'comments' => [
                    'api_description' => $comments_desc_current_int, 
                    'previous' => $commentsInteractionPrevious, 
                    'current' => $commentsInteractionCurrent,
                    'change' => round($commentsChange, 1),
                    'change_type' => $commentsChange >= 0 ? 'up' : 'down'
                ],
                'saves' => [
                    'api_description' => $saves_desc_current_int,
                    'previous' => $savesInteractionPrevious,
                    'current' => $savesInteractionCurrent,
                    'change' => round($savesChange, 1),
                    'change_type' => $savesChange >= 0 ? 'up' : 'down'
                ],
                'shares' => [
                    'api_description' => $shares_desc_current_int,
                    'previous' => $sharesInteractionPrevious, 
                    'current' => $sharesInteractionCurrent,
                    'change' => round($sharesChange, 1),
                    'change_type' => $sharesChange >= 0 ? 'up' : 'down'
                ],
                'reposts' => [
                    'api_description' => $reposts_desc_current_int,
                    'previous' => $repostsInteractionPrevious, 
                    'current' => $repostsInteractionCurrent,
                    'change' => round($repostsChange, 1),
                    'change_type' => $repostsChange >= 0 ? 'up' : 'down'
                ],
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Total Interactions by Likes, Comments, Saves, Shares, Reposts data loaded successfully.',
                'data' => $data, 
                'totalInteractionLikeContent' => view('backend.pages.instagram.component.total-interactions-by-l-c-save-share-reposts', compact('data'))->render(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Total Interactions Like/Comments API Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
            $defaultData = [
                'likes' => [
                    'api_description' => 'The number of likes on your posts, reels and videos.',
                    'previous' => 0,
                    'current' => 0,
                    'change' => 0,
                    'change_type' => 'down'
                ],
                'comments' => [
                    'api_description' => 'The number of comments on your posts, reels, videos and live videos.',
                    'previous' => 0,
                    'current' => 0,
                    'change' => 0,
                    'change_type' => 'down'
                ],
                'saves' => [
                    'api_description' => 'The number of saves of your posts, reels and videos.',
                    'previous' => 0,
                    'current' => 0,
                    'change' => 0,
                    'change_type' => 'down'
                ],
                'shares' => [
                    'api_description' => 'The number of shares of your posts, stories, reels, videos and live videos.',
                    'previous' => 0,
                    'current' => 0,
                    'change' => 0,
                    'change_type' => 'down'
                ],
                'reposts' => [
                    'api_description' => 'The total number of times that your content was reposted.',
                    'previous' => 0,
                    'current' => 0,
                    'change' => 0,
                    'change_type' => 'down'
                ]
            ];
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load interactions data: ' . $e->getMessage(),
                'data' => $defaultData, 
                'totalInteractionLikeContent' => view('backend.pages.instagram.component.total-interactions-by-l-c-save-share-reposts', [
                    'data' => $defaultData
                ])->render(),
            ]);
        }
    }

    public function instaTotalInteractionsMediaTypePDF($id, Request $request)
    {
        try {
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
            $accountId = $id;

            /* ===== Total Interactions by Media Type ===== */
            $mediaTypes = ['POST' => 0, 'AD' => 0, 'REEL' => 0, 'STORY' => 0];
            
            /* ===== Current Period ===== */
            $currentRes = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'total_interactions',
                'period' => 'day',
                'metric_type' => 'total_value',
                'breakdown' => 'media_product_type',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();

            //Log::info('Current : Total Interactions by Media Type: ' . print_r($currentRes, true));
            
            $totalInteractionsDesc = $currentRes['data'][0]['description'] ?? 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.';
            $currentByType = $mediaTypes;
            
            if (isset($currentRes['data'][0]['total_value']['breakdowns'][0]['results']) && is_array($currentRes['data'][0]['total_value']['breakdowns'][0]['results'])) {
                foreach ($currentRes['data'][0]['total_value']['breakdowns'][0]['results'] as $item) {
                    $type = strtoupper($item['dimension_values'][0] ?? '');
                    $value = (int) ($item['value'] ?? 0);
                    if (isset($currentByType[$type])) {
                        $currentByType[$type] += $value;
                    }
                }
            }

            /* ===== Previous Period ===== */
            $previousRes = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'total_interactions',
                'period' => 'day',
                'metric_type' => 'total_value',
                'breakdown' => 'media_product_type',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            
            //Log::info('Previous : Total Interactions by Media Type: ' . print_r($previousRes, true));
            
            $previousByType = $mediaTypes;
            if (isset($previousRes['data'][0]['total_value']['breakdowns'][0]['results']) && is_array($previousRes['data'][0]['total_value']['breakdowns'][0]['results'])) {
                foreach ($previousRes['data'][0]['total_value']['breakdowns'][0]['results'] as $previous_item) {
                    $previous_media_type = strtoupper($previous_item['dimension_values'][0] ?? '');
                    $previous_media_type_value = (int) ($previous_item['value'] ?? 0);
                    if (isset($previousByType[$previous_media_type])) {
                        $previousByType[$previous_media_type] += $previous_media_type_value;
                    }
                }
            }
            
            /* ===== Combine ===== */
            $combinedInteractions = [];
            foreach ($mediaTypes as $type => $_) {
                $prev = $previousByType[$type] ?? 0;
                $curr = $currentByType[$type] ?? 0;
                if ($prev == 0) {
                    $percent = $curr > 0 ? 100 : 0;
                } else {
                    $percent = round((($curr - $prev) / $prev) * 100, 1);
                }
                
                $change_type = $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'equal');
                $status = $percent > 0 ? '↑' : ($percent < 0 ? '↓' : '-');

                $combinedInteractions[$type] = [
                    'api_description' => $totalInteractionsDesc,
                    'previous' => $prev,
                    'current' => $curr,
                    'percent' => abs($percent),
                    'change' => $percent,
                    'change_type' => $change_type,
                    'status' => $status,
                ];
            }
            $data = [
                'post' => $combinedInteractions['POST'] ?? [
                    'api_description' => $totalInteractionsDesc,
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
                'ad' => $combinedInteractions['AD'] ?? [
                    'api_description' => $totalInteractionsDesc,
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
                'reel' => $combinedInteractions['REEL'] ?? [
                    'api_description' => $totalInteractionsDesc,
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
                'story' => $combinedInteractions['STORY'] ?? [
                    'api_description' => $totalInteractionsDesc,
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Total Interactions by Media Type data loaded successfully.',
                'data' => $data, 
                'totalInteractionMediaTypeContent' => view('backend.pages.instagram.component.total-interactions-by-media-type', compact('data'))->render(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Total Interactions by Media Type API Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
            $defaultData = [
                'post' => [
                    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
                'ad' => [
                    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
                'reel' => [
                    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
                'story' => [
                    'api_description' => 'The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.',
                    'previous' => 0,
                    'current' => 0,
                    'percent' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'status' => '-'
                ],
            ];
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load interactions by media type data: ' . $e->getMessage(),
                'data' => $defaultData, 
                'totalInteractionMediaTypeContent' => view('backend.pages.instagram.component.total-interactions-by-media-type', [
                    'data' => $defaultData
                ])->render(),
            ]);
        }
    }

    public function instaProfileVisitPDF($id, Request $request)
    {
        try {
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
            $accountId = $id;
            $result = [
                'profile_visits' => [
                    'current_profile' => 0,
                    'previous_profile' => 0,
                    'percent_change' => 0,
                    'api_description' => 'The number of times that your profile was visited.'
                ]
            ];
            /* Current Month PROFILE VISITS */
            $profileResponseCurrent = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_views',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();            
            //Log::info('Current : Profile Visits: ' . print_r($profileResponseCurrent, true));            
            if (isset($profileResponseCurrent['data'][0]['total_value'])) {
                $currentProfile_values = $profileResponseCurrent['data'][0]['total_value']['value'] ?? 0;
                $result['profile_visits']['current_profile'] = (int) $currentProfile_values;
                $result['profile_visits']['api_description'] = $profileResponseCurrent['data'][0]['description'] ?? 'The number of times that your profile was visited.';
            }
            /* Previous Month PROFILE VISITS */
            $prevProfile = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_views',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();            
            //Log::info('Previous : Profile Visits: ' . print_r($prevProfile, true));            
            if (isset($prevProfile['data'][0]['total_value'])) {
                $preProfile_values = $prevProfile['data'][0]['total_value']['value'] ?? 0;
                $result['profile_visits']['previous_profile'] = (int) $preProfile_values;
            }
            $prevProfileVisits = $result['profile_visits']['previous_profile'];
            $currProfileVisits = $result['profile_visits']['current_profile'];
            
            if ($prevProfileVisits > 0) {
                $percentChange = (($currProfileVisits - $prevProfileVisits) / $prevProfileVisits) * 100;
                $result['profile_visits']['percent_change'] = round($percentChange, 1);
            } else {
                $result['profile_visits']['percent_change'] = $currProfileVisits > 0 ? 100 : 0;
            }

            $data = [
                'profile_visits' => [
                    'previous' => $result['profile_visits']['previous_profile'],
                    'current' => $result['profile_visits']['current_profile'],
                    'change' => abs($result['profile_visits']['percent_change']),
                    'change_type' => $result['profile_visits']['percent_change'] >= 0 ? 'up' : 'down',
                    'description' => $result['profile_visits']['api_description']
                ]
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Profile visits data loaded successfully.',
                'data' => $data, 
                'profileVisitsContent' => view('backend.pages.instagram.component.profile-visits', compact('data'))->render(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Profile Visits API Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());
            $defaultData = [
                'profile_visits' => [
                    'previous' => 0,
                    'current' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'description' => 'The number of times that your profile was visited.'
                ]
            ];
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load profile visits data: ' . $e->getMessage(),
                'data' => $defaultData, 
                'profileVisitsContent' => view('backend.pages.instagram.component.profile-visits', [
                    'data' => $defaultData
                ])->render(),
            ]);
        }
    }

    public function instaEngagementPDF($id, Request $request)
    {
        try {
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
            $accountId = $id;
            $result = [
                'accounts_engaged' => [
                    'current' => 0,
                    'previous' => 0,
                    'description' => '',
                    'percent_change' => 0
                ]
            ];            
            /* CURRENT MONTH DATA - ACCOUNTS ENGAGED */
            $currentEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'accounts_engaged',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json(); 
            // Log::info('Current Month Accounts Engaged Response: ' . print_r($currentEngagementResponse, true));
            if (!empty($currentEngagementResponse['data'])) {
                foreach ($currentEngagementResponse['data'] as $metric) {
                    $metricName = $metric['name'] ?? '';
                    $value = $metric['total_value']['value'] ?? 0;
                    $description = $metric['description'] ?? 0;
                    
                    if ($metricName === 'accounts_engaged') {
                        $result['accounts_engaged']['current'] = (int) $value;
                        $result['accounts_engaged']['description'] = $description;
                        break;
                    }
                }
            }
            
            /* PREVIOUS MONTH DATA - ACCOUNTS ENGAGED */
            $prevEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'accounts_engaged',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();            
            // Log::info('Previous Month Accounts Engaged Response: ' . print_r($prevEngagementResponse, true));
            if (!empty($prevEngagementResponse['data'])) {
                foreach ($prevEngagementResponse['data'] as $metric) {
                    $metricName = $metric['name'] ?? '';
                    $value = $metric['total_value']['value'] ?? 0;

                    if ($metricName === 'accounts_engaged') {
                        $result['accounts_engaged']['previous'] = (int) $value;
                        break; 
                    }
                }
            }  
            $prevEngagement = $result['accounts_engaged']['previous'];
            $currEngagement = $result['accounts_engaged']['current'];            
            if ($prevEngagement > 0) {
                $change = (($currEngagement - $prevEngagement) / $prevEngagement) * 100;
                $result['accounts_engaged']['percent_change'] = round($change, 1);
            } else {
                $result['accounts_engaged']['percent_change'] = $currEngagement > 0 ? 100 : 0;
            }
            $data = [
                'accounts_engaged' => [
                    'previous' => $result['accounts_engaged']['previous'],
                    'current' => $result['accounts_engaged']['current'],
                    'change' => abs($result['accounts_engaged']['percent_change']),
                    'change_type' => $result['accounts_engaged']['percent_change'] >= 0 ? 'up' : 'down',
                    'description' => $result['accounts_engaged']['description']
                ]
            ];
            return response()->json([
                'status' => 'success',
                'message' => 'Accounts Engaged data loaded successfully.',
                'data' => $data, 
                'totalAccountsEngagedContent' => view('backend.pages.instagram.component.profile-engagement', compact('data'))->render(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Accounts Engaged API Error: ' . $e->getMessage());
            Log::error('Stack Trace: ' . $e->getTraceAsString());  
            $defaultData = [
                'accounts_engaged' => [
                    'previous' => 0,
                    'current' => 0,
                    'change' => 0,
                    'change_type' => 'down',
                    'description' => 'The number of accounts that have interacted with your content, including in ads. Content includes posts, stories, reels, videos and live videos. Interactions can include actions such as likes, saves, comments, shares or replies.'
                ]
            ];
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load accounts engaged data: ' . $e->getMessage(),
                'data' => $defaultData,
                'totalAccountsEngagedContent' => view('backend.pages.instagram.component.profile-engagement', [
                    'data' => $defaultData
                ])->render(),
            ]);
        }
    }

    public function instaCityAudiencePDF($id, Request $request)
    {
        $timeframe = $request->get('timeframe', 'this_month');
        $user = Auth::user();
        $mainAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', 'facebook')
            ->whereNull('parent_account_id')
            ->first();
        if (!$mainAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Facebook account not connected.'
            ], 400);
        }
        $instagramId = $id;
        $token = SocialTokenHelper::getFacebookToken($mainAccount);
        $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$instagramId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'city',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();
        //Log::info('City Audience Response: ' . print_r($response, true));
        //Log::info('City Audience URL: ' . "https://graph.facebook.com/v24.0/{$instagramId}/insights?metric=engaged_audience_demographics&period=lifetime&metric_type=total_value&breakdown=city&timeframe={$timeframe}&access_token={$token}");
        if (isset($response['error'])) {
            return response()->json([
                'success' => false,
                'message' => $response['error']['message']
            ]);
        }
        $results = data_get($response, 'data.0.total_value.breakdowns.0.results', []);
        $api_description = $response['data'][0]['description'] ?? '';
        $topCities = collect($results)
            ->sortByDesc('value')
            ->take(10)
            ->values();
        $total = $topCities->sum('value');

        $locations = $topCities->map(function ($item) use ($total) {
            $cityName = $item['dimension_values'][0] ?? 'Unknown';
            $value = $item['value'];
            $percentage = $total ? round(($value / $total) * 100, 2) : 0;

            return [
                'name' => $cityName,
                'value' => $value,
                'percentage' => $percentage
            ];
        });

        return response()->json([
            'success' => true,
            'locations' => $locations,
            'total' => $total,
            'api_description' => $api_description
        ]);
    }

    public function instaAudienceByAgeGroupPDF($id, Request $request)
    {
        $timeframe = $request->get('timeframe', 'this_month');
        $user = Auth::user();
        $mainAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', 'facebook')
            ->whereNull('parent_account_id')
            ->first();

        if (!$mainAccount) {
            return response()->json(['success' => false, 'message' => 'Facebook account not connected']);
        }

        $token = SocialTokenHelper::getFacebookToken($mainAccount);
        $instagramId = $id;
        $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$instagramId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'age,gender',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();

        if (isset($response['error'])) {
            return response()->json(['success' => false, 'message' => $response['error']['message']]);
        }

        $results = $response['data'][0]['total_value']['breakdowns'][0]['results'] ?? [];
        $api_description = $response['data'][0]['description'] ?? '';
        $ageGroups = [];
        foreach ($results as $r) {
            $age = $r['dimension_values'][0] ?? 'Unknown';
            $gender = $r['dimension_values'][1] ?? 'U';
            $value = $r['value'] ?? 0;

            if (!isset($ageGroups[$age])) {
                $ageGroups[$age] = ['M' => 0, 'F' => 0, 'U' => 0];
            }
            $ageGroups[$age][$gender] += $value;
        }
        ksort($ageGroups);
        $labels = array_keys($ageGroups);
        $male = array_column($ageGroups, 'M');
        $female = array_column($ageGroups, 'F');
        $unknown = array_column($ageGroups, 'U');
        return response()->json([
            'success' => true,
            'labels' => $labels,
            'male' => $male,
            'female' => $female,
            'unknown' => $unknown,
            'api_description' => $api_description
        ]);
    }

    public function instaPostDataPDF($id, Request $request){
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['success' => false, 'error' => 'Facebook account not connected']);
            }
            $instagramId = $id;
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            $since = $start->timestamp;
            $until = $end->timestamp;
            $limit = $request->get('limit', 12);
            $after = $request->get('after');
            $before = $request->get('before');
            $sortField = $request->get('sort', 'timestamp');
            $sortOrder = $request->get('order', 'desc');
            $mediaTypeFilter = $request->get('media_type', '');
            $searchFilter = $request->get('search', '');
            $params = [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,media_product_type,boost_ads_list{ad_id,ad_status}',
                'access_token' => $token,
                'since' => $since,
                'until' => $until,
            ];
            if ($after) $params['after'] = $after;
            if ($before) $params['before'] = $before;
            $mediaResponse = Http::timeout(10)
                ->get("https://graph.facebook.com/v24.0/{$instagramId}/media", $params)
                ->json();
            $media = $mediaResponse['data'] ?? [];
            $paging = $mediaResponse['paging'] ?? [];
            $filteredMedia = $this->applyFilters($media, $mediaTypeFilter, $searchFilter);
            $sortedMedia = $this->applySorting($filteredMedia, $sortField, $sortOrder);
            $currentPage = $request->get('page', 1);
            $perPage = $limit;
            $offset = ($currentPage - 1) * $perPage;
            $paginatedMedia = array_slice($sortedMedia, $offset, $perPage);
            $totalMedia = count($sortedMedia);
            $totalPages = ceil($totalMedia / $perPage);

            $formattedStart = $start->format('d M Y');
            $formattedEnd = $end->format('d M Y');

            $mediaTableHtml = view('backend.pages.instagram.partials.instagram-media-table', [
                'media' => $paginatedMedia,
                'paging' => $paging,
                'startDate' => $formattedStart,
                'endDate' => $formattedEnd,
                'instagram' => ['id' => $instagramId],
                'currentSort' => [
                    'field' => $sortField,
                    'order' => $sortOrder
                ],
                'currentFilters' => [
                    'media_type' => $mediaTypeFilter,
                    'search' => $searchFilter
                ],
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => $totalMedia,
                    'total_pages' => $totalPages,
                    'has_previous' => $currentPage > 1,
                    'has_next' => $currentPage < $totalPages,
                    'previous_page_url' => $this->buildPageUrl($request, $currentPage - 1),
                    'next_page_url' => $this->buildPageUrl($request, $currentPage + 1),
                ]
            ])->render();

            $html = '                
                <div id="instagram-media-table">
                    ' . $mediaTableHtml . '
                </div>
            ';

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function applyFilters($media, $mediaTypeFilter, $searchFilter)
    {
        return collect($media)->filter(function ($post) use ($mediaTypeFilter, $searchFilter) {
            $matchesMediaType = true;
            $matchesSearch = true;
            if (!empty($mediaTypeFilter)) {
                $matchesMediaType = isset($post['media_type']) &&
                    strtolower($post['media_type']) === strtolower($mediaTypeFilter);
            }
            if (!empty($searchFilter)) {
                $searchTerm = strtolower($searchFilter);
                $caption = isset($post['caption']) ? strtolower($post['caption']) : '';
                $postId = isset($post['id']) ? strtolower($post['id']) : '';

                $matchesSearch = str_contains($caption, $searchTerm) ||
                    str_contains($postId, $searchTerm);
            }

            return $matchesMediaType && $matchesSearch;
        })->values()->toArray();
    }

    private function applySorting($media, $sortField, $sortOrder)
    {
        usort($media, function ($a, $b) use ($sortField, $sortOrder) {
            $aValue = $a[$sortField] ?? '';
            $bValue = $b[$sortField] ?? '';
            switch ($sortField) {
                case 'timestamp':
                    $aValue = strtotime($aValue);
                    $bValue = strtotime($bValue);
                    $result = $aValue - $bValue;
                    break;

                case 'like_count':
                case 'comments_count':
                    $aValue = intval($aValue);
                    $bValue = intval($bValue);
                    $result = $aValue - $bValue;
                    break;

                case 'media_type':
                    $aValue = strtolower($aValue);
                    $bValue = strtolower($bValue);
                    $result = strcmp($aValue, $bValue);
                    break;

                default:
                    $result = strcmp($aValue, $bValue);
            }

            return $sortOrder === 'asc' ? $result : -$result;
        });

        return $media;
    }

    private function buildPageUrl($request, $page)
    {
        if ($page < 1) return null;

        $currentUrl = $request->fullUrl();
        $url = preg_replace('/[?&]page=\d+/', '', $currentUrl);

        if (str_contains($url, '?')) {
            return $url . '&page=' . $page;
        } else {
            return $url . '?page=' . $page;
        }
    }

}

   


    


    

