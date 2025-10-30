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
            /* Fetch Instagram profile */
            $instagram = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'name,username,biography,followers_count,follows_count,media_count,profile_picture_url',
                'access_token' => $token,
            ])->json();
            /* Fetch media with pagination */
            $limit = 12;
            $after = request()->get('after');
            $before = request()->get('before');
            $params = [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
                'access_token' => $token,
                'limit' => $limit,
            ];
            if ($after) $params['after'] = $after;
            if ($before) $params['before'] = $before;
            $mediaResponse = Http::timeout(10)
                ->get("https://graph.facebook.com/v24.0/{$id}/media", $params)
                ->json();
            //dd($mediaResponse);
            $media = $mediaResponse['data'] ?? [];
            $paging = $mediaResponse['paging'] ?? [];
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'html' => view('backend.pages.instagram.partials.instagram-media-table', compact('media', 'paging', 'instagram'))->render(),
                ]);
            }
            return view('backend.pages.instagram.show', compact(
                'instagram',
                'media',
                'paging',
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

    public function fetchHtml($id, Request $request)
    {
        try {
            $instagramId = $id;
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['error' => 'Facebook account not connected'], 400);
            }

            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            //Log::info("Instagram fetchHtml Date Range: {$startDate} to {$endDate}");
            $performanceData = $this->fetchPerformanceData($id, $token, $startDate, $endDate);

            /* Fetch media data for the table with date range */
            $mediaResponse = Http::timeout(10)
                ->get("https://graph.facebook.com/v24.0/{$id}/media", [
                    'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count',
                    'access_token' => $token,
                    'since' => $startDate,
                    'until' => $endDate,
                    'limit' => 12,
                ])->json();
            //Log::info('Instagram fetchHtml Media Response: ' . print_r($mediaResponse, true));
            $media = $mediaResponse['data'] ?? [];
            $paging = $mediaResponse['paging'] ?? [];
            $html = $this->renderDashboardHtml($media, $paging, $instagramId, $performanceData);
            return response()->json([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            Log::error('Instagram fetchHtml error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function fetchPerformanceData($accountId, $token, $startDate, $endDate)
    {
        /* Fetch account insights with date range */
        $insightsData = $this->fetchAccountInsights($accountId, $token, $startDate, $endDate);

        /* Fetch media insights with date range */
        $mediaData = $this->fetchMediaInsights($accountId, $token, $startDate, $endDate);

        $performanceData = array_merge($insightsData, $mediaData);

        $performanceData['date_range'] = [
            'start' => $startDate,
            'end' => $endDate,
            'display' => Carbon::parse($startDate)->format('d F Y') . ' - ' . Carbon::parse($endDate)->format('d F Y')
        ];
        return $performanceData;
    }

    private function fetchAccountInsights($accountId, $token, $startDate, $endDate)
    {
        try {
            $instagramReach = $this->fetchInstagramReachSummary($accountId, $token, $startDate, $endDate);
            $result['instagramReach'] = $instagramReach->getData();

            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $prevStart = $start->copy()->subDays(30);
            $prevEnd = $start->copy()->subDay();
            $result['profile_visits'] = [
                'current_profile' => 0,
                'previous_profile' => 0,
                'percent_change' => 0
            ];

            $result['profile_link'] = [
                'current' => 0,
                'previous' => 0,
                'percent_change' => 0
            ];

            $result['engagement'] = [
                'accounts_engaged' => 0,
                'total_interactions' => 0,
            ];

            /* Current Month PROFILE VISITS*/
            $profileResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_views',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $start,
                'until' => $end,
                'access_token' => $token,
            ])->json();

            if (isset($profileResponse['data'][0]['total_value'])) {
                $currentProfile_values = $profileResponse['data'][0]['total_value']['value'];
                $result['profile_visits']['current_profile'] = $currentProfile_values;
            }

            /* Previous Month PROFILE VISITS*/
            $prevProfile = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_views',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $prevStart->toDateString(),
                'until' => $prevEnd->toDateString(),
                'access_token' => $token,
            ])->json();

            if (isset($prevProfile['data'][0]['total_value'])) {
                $preProfile_values = $prevProfile['data'][0]['total_value']['value'];
                $result['profile_visits']['previous_profile'] = $preProfile_values;
            }


            $result['profile_visits']['percent_change'] = $result['profile_visits']['previous_profile'] > 0
                ? round((($result['profile_visits']['current_profile'] - $result['profile_visits']['previous_profile']) / $result['profile_visits']['previous_profile']) * 100, 2)
                : 0;

            $currentStart = Carbon::parse($startDate);
            $currentEnd = Carbon::parse($endDate);
            $originalDays = $currentStart->diffInDays($currentEnd);
            if ($originalDays > 30) {
                $currentEnd = now();
                $currentStart = $currentEnd->copy()->subDays(28);
                $result['profile_link']['message'] = "There cannot be more than 30 days between since and until.";
                $result['engagement']['message'] = "There cannot be more than 30 days between since and until.";
            }
            Log::info("Profile Link Taps Date Range: {$currentStart} to {$currentEnd}");
            $currentProfileClickResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_links_taps',
                'metric_type' => 'total_value',
                'period' => 'day',
                'since' => $currentStart->toDateString(),
                'until' => $currentEnd->toDateString(),
                'access_token' => $token,
            ])->json();
            Log::info('Instagram Current Profile Link Tabs Response: ' . print_r($currentProfileClickResponse, true));
            if (isset($currentProfileClickResponse['data'][0]['total_value'])) {
                $profile_link_current = $currentProfileClickResponse['data'][0]['total_value']['value'];
                $result['profile_link']['current'] = $profile_link_current;
            }

            $prevProfileClickResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_links_taps',
                'metric_type' => 'total_value',
                'period' => 'day',
                'since' => $prevStart->toDateString(),
                'until' => $prevEnd->toDateString(),
                'access_token' => $token,
            ])->json();

            if (isset($prevProfileClickResponse['data'][0]['total_value'])) {
                $profile_link_prev = $prevProfileClickResponse['data'][0]['total_value']['value'];
                $result['profile_link']['previous'] = $profile_link_prev;
            }

            $result['profile_link']['percent_change'] = $result['profile_link']['previous'] > 0
                ? round((($result['profile_link']['current'] - $result['profile_link']['previous']) / $result['profile_link']['previous']) * 100, 2)
                : 0;
            /* ENGAGEMENT (Accounts engaged, interactions)*/
            $engagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'accounts_engaged,total_interactions',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $currentStart->toDateString(),
                'until' => $currentEnd->toDateString(),
                'access_token' => $token,
            ])->json();
            Log::info('Instagram Engagement Response: ' . print_r($engagementResponse, true));
            if (isset($engagementResponse['data'])) {
                foreach ($engagementResponse['data'] as $metric) {
                    $metricName = $metric['name'];
                    if (isset($metric['total_value']['value'])) {
                        $result['engagement'][$metricName] = (int) $metric['total_value']['value'];
                    } elseif (isset($metric['values']) && is_array($metric['values'])) {
                        $values = array_column($metric['values'], 'value');
                        $result['engagement'][$metricName] = array_sum($values);
                    }
                }
            }
            return $result;
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Instagram API Request Failed: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Instagram Insights Exception: ' . $e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }

    private function fetchInstagramReachSummary($accountId, $token, $startDate, $endDate)
    {
        $result = [
            'reach' => [
                'previous' => 0,
                'current' => 0,
                'percent_change' => 0,
                'paid' => 0,
                'organic' => 0,
                'followers' => 0,
                'non_followers' => 0,
            ],
            'reach_prev' => [
                'paid' => 0,
                'organic' => 0,
                'followers' => 0,
                'non_followers' => 0,
            ]
        ];

        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $chunkSize = 30;
            $current = $start->copy();
            $warning = null;
            if ($start->diffInDays($end) > 30) {
                $end = $start->copy()->addDays(29);
                $warning = 'Selected date range exceeds 30 days. Only first 30 days of data have been fetched (Instagram API limit).';
            }
            // ==========================================================
            // CURRENT MONTH DATA
            // ==========================================================
            while ($current->lte($end)) {
                $chunkStart = $current->copy();
                $chunkEnd = $current->copy()->addDays($chunkSize - 1);
                if ($chunkEnd->gt($end)) $chunkEnd = $end->copy();
                $response = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                    'metric' => 'reach',
                    'period' => 'day',
                    'breakdown' => 'media_product_type,follow_type',
                    'metric_type' => 'total_value',
                    'since' => $chunkStart->toDateString(),
                    'until' => $chunkEnd->toDateString(),
                    'access_token' => $token,
                ])->json();
                //Log::info("Current Month Reach Response: " . print_r($response, true));

                if (isset($response['data'][0]['total_value']['breakdowns'][0]['results'])) {
                    foreach ($response['data'][0]['total_value']['breakdowns'][0]['results'] as $r) {
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

                $current->addDays($chunkSize);
            }

            // ==========================================================
            // PREVIOUS MONTH DATA
            // ==========================================================
            $prevStart = $start->copy()->subDays(30);
            $prevEnd = $start->copy()->subDay();

            $prevResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'reach',
                'period' => 'day',
                'breakdown' => 'media_product_type,follow_type',
                'metric_type' => 'total_value',
                'since' => $prevStart->toDateString(),
                'until' => $prevEnd->toDateString(),
                'access_token' => $token,
            ])->json();

            //Log::info("Previous Month Reach: ({$prevStart} - {$prevEnd})", $prevResponse);
            //Log::info("Previous Month Reach Response: " . print_r($prevResponse, true));
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

            // ==========================================================
            //CALCULATE PERCENTAGE CHANGES
            // ==========================================================
            $prev = $result['reach']['previous'];
            $curr = $result['reach']['current'];
            $result['reach']['percent_change'] = $prev > 0
                ? round((($curr - $prev) / $prev) * 100, 2)
                : 0;
            $total = max($curr, 1);
            $result['reach']['paid_percent'] = round(($result['reach']['paid'] / $total) * 100, 2);
            $result['reach']['organic_percent'] = round(($result['reach']['organic'] / $total) * 100, 2);

            // ==========================================================
            // FINAL RESPONSE
            // ==========================================================
            return response()->json([
                'status' => 'success',
                'message' => $warning ? $warning : 'Reach data fetched successfully.',
                'reach' => [
                    'current_month' => [
                        'total' => $result['reach']['current'],
                        'paid' => $result['reach']['paid'],
                        'organic' => $result['reach']['organic'],
                        'followers' => $result['reach']['followers'],
                        'non_followers' => $result['reach']['non_followers'],
                        'paid_percent' => $result['reach']['paid_percent'],
                        'organic_percent' => $result['reach']['organic_percent'],
                    ],
                    'previous_month' => [
                        'total' => $result['reach']['previous'],
                        'paid' => $result['reach_prev']['paid'],
                        'organic' => $result['reach_prev']['organic'],
                        'followers' => $result['reach_prev']['followers'],
                        'non_followers' => $result['reach_prev']['non_followers'],
                    ],
                    'percent_change' => $result['reach']['percent_change'],
                ]
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Instagram Reach Fetch Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function fetchMediaInsights($accountId, $token, $startDate, $endDate)
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $prevStart = $start->copy()->subDays(30);
        $prevEnd = $start->copy()->subDay();
        $counts = [];
        /* ===== Followers / Unfollowers (Current) ===== */
        $current_month_followers = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'follows_and_unfollows',
            'period' => 'day',
            'breakdown' => 'follow_type',
            'metric_type' => 'total_value',
            'since' => $startDate,
            'until' => $endDate,
            'access_token' => $token,
        ])->json();

        $followers = 0;
        $unfollowers = 0;
        if (isset($current_month_followers['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($current_month_followers['data'][0]['total_value']['breakdowns'][0]['results'] as $result) {
                $type = $result['dimension_values'][0] ?? '';
                $value = $result['value'] ?? 0;
                if ($type === 'FOLLOWER') {
                    $followers += $value;
                } elseif ($type === 'NON_FOLLOWER') {
                    $unfollowers += $value;
                }
            }
        }

        /* ===== Followers / Unfollowers (Previous) ===== */
        $previous_month_followers = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'follows_and_unfollows',
            'period' => 'day',
            'breakdown' => 'follow_type',
            'metric_type' => 'total_value',
            'since' => $prevStart->toDateString(),
            'until' => $prevEnd->toDateString(),
            'access_token' => $token,
        ])->json();

        $previous_followers = 0;
        $previous_unfollowers = 0;
        if (isset($previous_month_followers['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($previous_month_followers['data'][0]['total_value']['breakdowns'][0]['results'] as $result) {
                $pre_type = $result['dimension_values'][0] ?? '';
                $pre_value = $result['value'] ?? 0;
                if ($pre_type === 'FOLLOWER') {
                    $previous_followers += $pre_value;
                } elseif ($pre_type === 'NON_FOLLOWER') {
                    $previous_unfollowers += $pre_value;
                }
            }
        }
        /* ===== Current Media ===== */
        $mediaResponseCurrent = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/media", [
            'fields' => 'media_type,media_product_type,like_count,comments_count,timestamp',
            'since' => $startDate,
            'until' => $endDate,
            'access_token' => $token,
        ])->json();
        $posts = $stories = $reels = $totalInteractions = 0;
        if (isset($mediaResponseCurrent['data'])) {
            foreach ($mediaResponseCurrent['data'] as $media) {
                $mediaType = $media['media_type'] ?? '';
                $productType = $media['media_product_type'] ?? '';

                if ($productType === 'STORIES') $stories++;
                elseif ($productType === 'REELS') $reels++;
                elseif ($mediaType === 'CAROUSEL_ALBUM' || $mediaType === 'IMAGE') $posts++;

                $totalInteractions += ($media['like_count'] ?? 0) + ($media['comments_count'] ?? 0);
            }
        }

        /* ===== Previous Media ===== */
        $mediaResponsePrevious = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/media", [
            'fields' => 'media_type,media_product_type,like_count,comments_count,timestamp',
            'since' => $prevStart->toDateString(),
            'until' => $prevEnd->toDateString(),
            'access_token' => $token,
        ])->json();

        $pre_posts = $pre_stories = $pre_reels = $pre_totalInteractions = 0;

        if (isset($mediaResponsePrevious['data'])) {
            foreach ($mediaResponsePrevious['data'] as $mediaPrev) {
                $preMediaType = $mediaPrev['media_type'] ?? '';
                $preProductType = $mediaPrev['media_product_type'] ?? '';

                if ($preProductType === 'STORIES') $pre_stories++;
                elseif ($preProductType === 'REELS') $pre_reels++;
                elseif ($preMediaType === 'CAROUSEL_ALBUM' || $preMediaType === 'IMAGE') $pre_posts++;

                $pre_totalInteractions += ($mediaPrev['like_count'] ?? 0) + ($mediaPrev['comments_count'] ?? 0);
            }
        }
        $data = [
            'followers' => ['previous' => $previous_followers, 'current' => $followers],
            'unfollowers' => ['previous' => $previous_unfollowers, 'current' => $unfollowers],
            'posts' => ['previous' => $pre_posts, 'current' => $posts],
            'stories' => ['previous' => $pre_stories, 'current' => $stories],
            'reels' => ['previous' => $pre_reels, 'current' => $reels],
            'content_interaction' => ['previous' => $pre_totalInteractions, 'current' => $totalInteractions],
        ];

        /* ===== Percent & Status (↑↓) Calculation ===== */
        $final = [];
        foreach ($data as $key => $value) {
            $prev = $value['previous'] ?? 0;
            $cur = $value['current'] ?? 0;

            if ($prev == 0) {
                $percent = $cur > 0 ? 100 : 0;
            } else {
                $percent = round((($cur - $prev) / $prev) * 100, 2);
            }

            $status = $percent > 0 ? '↑' : ($percent < 0 ? '↓' : '-');

            $final[$key] = [
                'previous' => $prev,
                'current' => $cur,
                'percent' => $percent,
                'status' => $status,
            ];
        }

        return $final;
    }

    private function renderDashboardHtml($media = [], $paging = [], $instagramId, $performanceData)
    {
        // Log::info('Rendering Dashboard HTML with Performance Data: ' . print_r($performanceData, true));
        $dateRange = $performanceData['date_range']['display'];
        $instagramReach = $performanceData['instagramReach'] ?? null;
        $paidReach = $instagramReach ? ($instagramReach->reach->current_month->paid ?? 0) : 0;
        $organicReach = $instagramReach ? ($instagramReach->reach->current_month->organic ?? 0) : 0;
        $followersReach = $instagramReach ? ($instagramReach->reach->current_month->followers ?? 0) : 0;
        $nonFollowersReach = $instagramReach ? ($instagramReach->reach->current_month->non_followers ?? 0) : 0;

        $formattedPaidReach = $this->formatNumber($paidReach);
        $formattedOrganicReach = $this->formatNumber($organicReach);
        $formattedFollowersReach = $this->formatNumber($followersReach);
        $formattedNonFollowersReach = $this->formatNumber($nonFollowersReach);


        $currentReach = $instagramReach ? ($instagramReach->reach->current_month->total ?? 0) : 0;
        $previousReach = $instagramReach ? ($instagramReach->reach->previous_month->total ?? 0) : 0;
        $reachPercentage = $instagramReach ? ($instagramReach->reach->percent_change ?? 0) : 0;
        $reachTrend = $reachPercentage >= 0 ? 'positive' : 'negative';


        $currentProfile = $performanceData['profile_visits']['current_profile'] ?? 0;
        $previousProfile = $performanceData['profile_visits']['previous_profile'] ?? 0;
        $profilePercentage = $performanceData['profile_visits']['percent_change'] ?? 0;
        $profileTrend = $profilePercentage >= 0 ? 'positive' : 'negative';


        $accountsEngaged = $performanceData['engagement']['accounts_engaged'] ?? 0;
        $totalInteractions = $performanceData['engagement']['total_interactions'] ?? 0;
        $profileLinkCurrent = $performanceData['profile_link']['current'] ?? 0;
        $profileLinkPre = $performanceData['profile_link']['previous'] ?? 0;
        $profileLinkPercent = $performanceData['profile_link']['percent_change'] ?? 0;

        $postsCurrent = $performanceData['posts']['current'] ?? 0;
        $storiesCurrent = $performanceData['stories']['current'] ?? 0;
        $reelsCurrent = $performanceData['reels']['current'] ?? 0;
        $contentInteractionCurrent = $performanceData['content_interaction']['current'] ?? 0;

        $mediaTableHtml = view('backend.pages.instagram.partials.instagram-media-table', [
            'media' => $media,
            'paging' => $paging,
            'instagram' => ['id' => $instagramId],
        ])->render();

        $html = '
    <div class="card">
        <div class="card-header text-white">
            <h4 class="mb-2">
                Performance
            </h4>
            <h5>' . $dateRange . '</h5>
        </div>
        <div class="card-body">
            <div class="reach-section mb-4">
                <div class="row g-3">
                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">Reach</div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <th>
                                            <h3 class="mb-0">' . $this->formatNumber($previousReach) . '</h3>
                                        </th>
                                        <th>
                                            <h3 class="mb-0">' . $this->formatNumber($currentReach) . '</h3>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center;" class="bg-black text-light">Previous Month</td>
                                        <td style="text-align: center;" class="bg-black text-light">Current Month</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="">
                                           <h4 class="mb-0"> ' . ($reachPercentage >= 0 ? '+' : '') . $reachPercentage . '%</h4>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center;" class="bg-black text-light">Paid Reach</td>
                                        <td style="text-align: center;" class="bg-black text-light">Organic Reach</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4 class="mb-0">' . $formattedPaidReach . '</h4>
                                        </td>
                                        <td>
                                            <h4 class="mb-0">' . $formattedOrganicReach . '</h4>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center;" class="bg-black text-light">Followers</td>
                                        <td style="text-align: center;" class="bg-black text-light">Non-Followers</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4 class="mb-0">' . $formattedFollowersReach . '</h4>
                                        </td>
                                        <td>
                                            <h4 class="mb-0">' . $formattedNonFollowersReach . '</h4>
                                        </td>
                                    </tr>
                                </table> 
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">Profile Visits</div>
                            <div class="metric-body"> 
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <th>
                                            <h3 class="mb-0">' . $this->formatNumber($previousProfile) . '</h3>
                                        </th>
                                        <th>
                                            <h3 class="mb-0">' . $this->formatNumber($currentProfile) . '</h3>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center;" class="bg-black text-light">Previous Month</td>
                                        <td style="text-align: center;" class="bg-black text-light">Current Month</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="">
                                            <h4 class="mb-0">
                                            ' . ($profilePercentage >= 0 ? '+' : '') . $profilePercentage . '%
                                            </h4>
                                        </td>
                                    </tr>                                    
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">Profile Links Clicks</div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    ' . (isset($performanceData['profile_link']['message']) && !empty($performanceData['profile_link']['message'])
            ? '<tr>
                                        <td colspan="2" class="text-info small text-start">
                                            <i class="fas fa-info-circle"></i> ' . e($performanceData['profile_link']['message']) . '
                                        </td>
                                    </tr>'
            : '') . '
                                    <tr>
                                        <th>
                                            <h3 class="mb-0">' . $this->formatNumber($profileLinkPre) . '</h3>
                                        </th>
                                        <th>
                                            <h3 class="mb-0">' . $this->formatNumber($profileLinkCurrent) . '</h3>
                                        </th>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center;" class="bg-black text-light">Previous Month</td>
                                        <td style="text-align: center;" class="bg-black text-light">Current Month</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="">
                                            <h4 class="mb-0">
                                            ' . ($profileLinkPercent >= 0 ? '+' : '') . $profileLinkPercent . '%
                                            </h4>
                                        </td>
                                    </tr>                                    
                                </table>                                
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">Engagement</div>
                            <div class="metric-body">                                
                                <div class="stats-row">
                                    <div class="account-enga">
                                        <h4>' . $this->formatNumber($accountsEngaged) . '</h4>
                                        <p class="mb-0">Accounts Engaged</p>
                                    </div>
                                    <div class="account-enga">
                                        <h4>' . $this->formatNumber($totalInteractions) . '</h4>
                                        <p class="mb-0">Total Interactions</p>
                                    </div>
                                </div>
                                ' . (isset($performanceData['engagement']['message']) ?
            '<div class="small text-info mt-2"><i class="fas fa-info-circle"></i> ' . $performanceData['profile_link']['message'] . '</div>' : '') . '
                            </div>
                        </div>
                    </div>
                </div>                    
                <div class="row g-3 text-center">
                    <div class="col-md-2 col-sm-4">
                        <div class="metric-card py-3">
                            <div class="icon-metric mb-1">➕</div>
                            <div class="fw-bold fs-5">' . $postsCurrent . '</div>
                            <div class="small">Number of Posts</div>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-4">
                        <div class="metric-card py-3">
                            <div class="icon-metric mb-1">🎥</div>
                            <div class="fw-bold fs-5">' . $storiesCurrent . '</div>
                            <div class="small">Number of Stories</div>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-4">
                        <div class="metric-card py-3">
                            <div class="icon-metric mb-1">📹</div>
                            <div class="fw-bold fs-5">' . $reelsCurrent . '</div>
                            <div class="small">Number of Reels</div>
                        </div>
                    </div>  
                    <div class="col-md-2 col-sm-4">
                        <div class="metric-card py-3">
                            <div class="icon-metric mb-1">💬</div>
                            <div class="fw-bold fs-5">' . $this->formatNumber($contentInteractionCurrent) . '</div>
                            <div class="small">Content Interaction</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-3">
                <h4 class="mb-3">Latest Post</h4>
            </div>
            <div id="instagram-media-table">
                ' . $mediaTableHtml . '
            </div>
        </div>
    </div>';

        return $html;
    }

    private function formatNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return $number;
    }

    private function calculatePercentage($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
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
            switch ($period) {
                case 'day':
                    $since = now()->subDay()->toDateString();
                    $until = now()->toDateString();
                    break;

                case 'week':
                    $since = now()->subDays(7)->toDateString();
                    $until = now()->toDateString();
                    break;

                case 'days_28':
                    $since = now()->subDays(28)->toDateString();
                    $until = now()->toDateString();
                    break;
                case 'month':
                    $since = now()->subMonth()->toDateString();
                    $until = now()->toDateString();
                    break;

                case 'lifetime':
                    /* No range restriction — all data */
                    $since = null;
                    $until = null;
                    break;

                case 'total_over_range':
                    /* For demonstration, consider last 3 months*/
                    $since = now()->subMonths(3)->toDateString();
                    $until = now()->toDateString();
                    break;

                default:
                    $since = now()->subDay()->toDateString();
                    $until = now()->toDateString();
                    break;
            }
            $url = "https://graph.facebook.com/v24.0/{$id}/media";
            $params = [
                'fields' => "timestamp,media_product_type,insights.metric({$metrics}).period({$period})",
                'access_token' => $token,
            ];
            if ($since && $until) {
                $params['since'] = $since;
                $params['until'] = $until;
            }
            $response = Http::get($url, $params)->json();
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
                $date = \Carbon\Carbon::parse($timestamp)->format('Y-m-d');
                if (!in_array($date, $dates)) $dates[] = $date;
                $reach = $likes = $comments = $views = 0;
                foreach ($media['insights']['data'] ?? [] as $metric) {
                    $value = $metric['values'][0]['value'] ?? 0;
                    switch ($metric['name']) {
                        case 'reach':
                            $reach = $value;
                            break;
                        case 'likes':
                            $likes = $value;
                            break;
                        case 'comments':
                            $comments = $value;
                            break;
                        case 'views':
                            $views = $value;
                            break;
                    }
                }
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
                'range' => compact('since', 'until'),
            ];
            return response()->json($final);
        } catch (\Exception $e) {
            Log::error('Instagram metricsGraph error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**insights every post start */
    public function postInsightsPage($id, $postId)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return redirect()->route('facebook.index')->with('error', 'Facebook account not connected');
            }
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            /* Fetch Instagram account basic info */
            $instagram = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'id,name,username,profile_picture_url,followers_count',
                'access_token' => $token,
            ])->json();

            if (isset($instagram['error'])) {
                throw new Exception($instagram['error']['message']);
            }

            /* Fetch post basic info */
            $postBasic = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$postId}", [
                'fields' => 'id,media_type,media_url,permalink,timestamp,like_count,comments_count,caption,username,children{media_type,media_url}',
                'access_token' => $token,
            ])->json();

            if (isset($postBasic['error'])) {
                throw new Exception($postBasic['error']['message']);
            }
            $mediaType = $this->detectSmartMediaType($postBasic);
            $metrics = $this->getAvailableMetrics($mediaType);

            /* Fetch post with insights */
            $postWithInsights = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$postId}/insights", [
                'metric' => $metrics,
                'access_token' => $token,
            ])->json();

            if (isset($postWithInsights['error'])) {
                throw new Exception($postWithInsights['error']['message']);
            }
            $postBasic['insights'] = ['data' => $postWithInsights['data'] ?? []];
            $postData = $this->processPostData($postBasic, $mediaType);
            return view('backend.pages.instagram.insights', compact('postData', 'instagram'));
        } catch (\Exception $e) {
            Log::error('Post insights page error: ' . $e->getMessage());
            return back()->with('error', 'Failed to load post insights: ' . $e->getMessage());
        }
    }


    private function getAvailableMetrics($mediaType)
    {
        Log::error('getAvailableMetrics mediaType: ' . $mediaType);

        switch ($mediaType) {
            case 'REEL':
                return 'reach,saved,shares,total_interactions,likes,comments,ig_reels_avg_watch_time,ig_reels_video_view_total_time';
            case 'VIDEO':
                return 'impressions,reach,saved,shares,video_views,total_interactions,likes,comments';
            case 'CAROUSEL_ALBUM':
                return 'impressions,reach,saved,shares,total_interactions,likes,comments';
            case 'IMAGE':
            default:
                return 'impressions,reach,saved,shares,total_interactions,likes,comments';
        }
    }

    private function detectSmartMediaType($post)
    {
        if (isset($post['permalink']) && str_contains($post['permalink'], '/reel/')) {
            return 'REEL';
        }

        return $post['media_type'] ?? 'UNKNOWN';
    }

    private function processPostData($post, $mediaType = null)
    {
        //dd($post);
        $insights = [];
        if (isset($post['insights']['data'])) {
            foreach ($post['insights']['data'] as $insight) {
                $value = $insight['values'][0]['value'] ?? 0;
                $insights[$insight['name']] = $value;
            }
        }

        $likes = $post['like_count'] ?? 0;
        $comments = $post['comments_count'] ?? 0;

        $impressions = $insights['impressions'] ?? 0;
        $reach = $insights['reach'] ?? 0;
        $shares = $insights['shares'] ?? 0;
        $saves = $insights['saved'] ?? 0;
        $videoViews = $insights['video_views'] ?? 0;
        $totalInteractions = $insights['total_interactions'] ?? 0;

        $totalWatchTime = 0;
        $avgWatchTime = 0;
        $totalWatchTimeFormatted = '0s';
        $avgWatchTimeFormatted = '0s';

        if ($mediaType === 'REEL') {
            $totalWatchTimeMs = $insights['ig_reels_video_view_total_time'] ?? 0;
            $avgWatchTimeMs = $insights['ig_reels_avg_watch_time'] ?? 0;

            $totalWatchTimeSeconds = $totalWatchTimeMs / 1000;
            $avgWatchTimeSeconds = $avgWatchTimeMs / 1000;

            $avgWatchTimeFormatted = $this->formatSecondsToFacebookStyle($avgWatchTimeSeconds);
            $totalWatchTimeFormatted = $this->formatSecondsToFacebookStyle($totalWatchTimeSeconds);

            $totalWatchTime = $totalWatchTimeSeconds;
            $avgWatchTime = $avgWatchTimeSeconds;
        }

        $denominator = $reach > 0 ? $reach : ($impressions > 0 ? $impressions : 1);
        $engagementRate = $denominator > 0 ? (($likes + $comments + $shares) / $denominator) * 100 : 0;

        $carouselMedia = [];
        if (($post['media_type'] === 'CAROUSEL_ALBUM' || $mediaType === 'REEL') && isset($post['children']['data'])) {
            $carouselMedia = $post['children']['data'];
        }

        return [
            'id' => $post['id'],
            'media_type' => $mediaType,
            'original_media_type' => $post['media_type'] ?? 'UNKNOWN',
            'media_url' => $post['media_url'] ?? '',
            'permalink' => $post['permalink'] ?? '',
            'timestamp' => Carbon::parse($post['timestamp'])->format('F j, Y \a\t g:i A'),
            'caption' => $post['caption'] ?? 'No caption',
            'likes' => $likes,
            'comments' => $comments,
            'shares' => $shares,
            'impressions' => $impressions,
            'reach' => $reach,
            'saves' => $saves,
            'video_views' => $videoViews,
            'total_watch_time' => $totalWatchTime,
            'total_watch_time_formatted' => $totalWatchTimeFormatted,
            'avg_watch_time' => $avgWatchTime,
            'avg_watch_time_formatted' => $avgWatchTimeFormatted,
            'total_interactions' => $totalInteractions,
            'engagement_rate' => round($engagementRate, 2),
            'total_engagement' => $likes + $comments + $shares,
            'carousel_media' => $carouselMedia,
            'available_metrics' => array_keys($insights)
        ];
    }

    private function formatSecondsToFacebookStyle($seconds)
    {
        if ($seconds < 60) {
            return round($seconds) . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = round($seconds % 60);

        if ($minutes < 60) {
            return $minutes . 'm ' . $remainingSeconds . 's';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $hours . 'h ' . $remainingMinutes . 'm ' . $remainingSeconds . 's';
    }


    public function fetchCommentsHtml($mediaId)
    {
        try {
            $user = Auth::user();
            $mainAccount = \App\Models\SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['error' => 'Facebook account not connected'], 401);
            }

            $token = \App\Helpers\SocialTokenHelper::getFacebookToken($mainAccount);

            $response = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$mediaId}/comments", [
                'fields' => 'id,text,timestamp,like_count,from,children{id,text,timestamp,like_count,from}',
                'access_token' => $token,
                'limit' => 30,
            ])->json();

            if (isset($response['error'])) {
                throw new Exception($response['error']['message']);
            }

            $comments = $response['data'] ?? [];
            Log::info('Fetched ' . count($comments) . ' comments for media ID ' . $mediaId);
            Log::debug('Comments data: ' . print_r($comments, true));

            $html = '';

            if (count($comments) > 0) {
                foreach ($comments as $c) {
                    $html .= $this->renderCommentHtml($c);
                }
            } else {
                $html = "<p class='text-muted mb-0'>No comments found on this post.</p>";
            }

            return response()->json(['html' => $html]);
        } catch (Exception $e) {
            Log::error('Error fetching IG comments: ' . $e->getMessage());
            return response()->json(['html' => '<p class="text-danger">Error: ' . e($e->getMessage()) . '</p>']);
        }
    }


    private function renderCommentHtml($comment, $isChild = false)
    {
        $username = $comment['from']['username'] ?? 'Anonymous';
        $text = e($comment['text'] ?? '');
        $time = isset($comment['timestamp']) ? date('d M Y, h:i A', strtotime($comment['timestamp'])) : '';
        $likes = $comment['like_count'] ?? 0;
        $padding = $isChild ? 'pl-4 border-start ms-2' : '';
        $html = "
            <div class='border-bottom py-2 {$padding}'>
                <div class='d-flex justify-content-between'>
                    <strong>{$username}</strong>
                    <small class='text-muted'>{$time}</small>
                </div>
                <p class='mb-1'>{$text}</p>
                <small class='text-muted'><i class='bi bi-heart'></i> {$likes} likes</small>
            </div>
        ";
        if (isset($comment['children']['data']) && count($comment['children']['data']) > 0) {
            foreach ($comment['children']['data'] as $child) {
                $html .= $this->renderCommentHtml($child, true);
            }
        }

        return $html;
    }
}
