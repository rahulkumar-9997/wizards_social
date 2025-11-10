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
            return view('backend.pages.instagram.show', compact(
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
            //Log::info('Instagram fetchHtml Media Response: ' . print_r($performanceData, true));            
            $html = $this->renderDashboardHtml($instagramId, $performanceData);
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
            /**final */
            $instagramReach = $this->fetchInstagramReachSummary($accountId, $token, $startDate, $endDate);
            $result['instagramReach'] = $instagramReach->getData();

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

            $result['profile_visits'] = [
                'current_profile' => 0,
                'previous_profile' => 0,
                'percent_change' => 0,
                'api_description' => 0,
            ];

            $result['profile_link'] = [
                'current' => 0,
                'previous' => 0,
                'percent_change' => 0,
                'api_description' => 0,
            ];

            $result['engagement'] = [
                'accounts_engaged_current' => 0,
                'accounts_engaged_previous' => 0,
                'accounts_engaged_percent_change' => 0,
                'account_engaged_api_description' => 0,

                'total_interactions_current' => 0,
                'total_interactions_previous' => 0,
                'total_interactions_percent_change' => 0,
                'interactions_api_description' => 0,
            ];

            /* Current Month PROFILE VISITS*/
            $profileResponseCurrent = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_views',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            if (isset($profileResponseCurrent['data'][0]['total_value'])) {
                $currentProfile_values = $profileResponseCurrent['data'][0]['total_value']['value'];
                $result['profile_visits']['current_profile'] = $currentProfile_values;
                $result['profile_visits']['api_description'] = $profileResponseCurrent['data'][0]['description'];
            }

            /* Previous Month PROFILE VISITS*/
            $prevProfile = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_views',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();

            if (isset($prevProfile['data'][0]['total_value'])) {
                $preProfile_values = $prevProfile['data'][0]['total_value']['value'];
                $result['profile_visits']['previous_profile'] = $preProfile_values;
            }


            $result['profile_visits']['percent_change'] = $result['profile_visits']['previous_profile'] > 0
                ? round((($result['profile_visits']['current_profile'] - $result['profile_visits']['previous_profile']) / $result['profile_visits']['previous_profile']) * 100, 2)
                : 0;


            $currentProfileClickResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_links_taps',
                'metric_type' => 'total_value',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            //Log::info('Instagram Current Profile Link Tabs Response: ' . print_r($currentProfileClickResponse, true));
            if (isset($currentProfileClickResponse['data'][0]['total_value'])) {
                $profile_link_current = $currentProfileClickResponse['data'][0]['total_value']['value'];
                $result['profile_link']['current'] = $profile_link_current;
                $result['profile_link']['api_description'] = $currentProfileClickResponse['data'][0]['description'];
            }

            $prevProfileClickResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'profile_links_taps',
                'metric_type' => 'total_value',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            //Log::info('Instagram previous Profile Link Tabs Response: ' . print_r($prevProfileClickResponse, true));
            if (isset($prevProfileClickResponse['data'][0]['total_value'])) {
                $profile_link_prev = $prevProfileClickResponse['data'][0]['total_value']['value'];
                $result['profile_link']['previous'] = $profile_link_prev;
            }

            $result['profile_link']['percent_change'] = $result['profile_link']['previous'] > 0
                ? round((($result['profile_link']['current'] - $result['profile_link']['previous']) / $result['profile_link']['previous']) * 100, 2)
                : 0;
            /* CURRENT MONTH DATA */
            $currentEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'accounts_engaged,total_interactions',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            //Log::info('Instagram Engagement Response: ' . print_r($currentEngagementResponse, true));

            if (!empty($currentEngagementResponse['data'])) {
                foreach ($currentEngagementResponse['data'] as $metric) {
                    $metricName = $metric['name'] ?? '';
                    $value = $metric['total_value']['value'] ?? 0;

                    switch ($metricName) {
                        case 'accounts_engaged':
                            $result['engagement']['accounts_engaged_current'] = (int) $value;
                            $result['engagement']['account_engaged_api_description'] = $metric['description'];
                            break;

                        case 'total_interactions':
                            $result['engagement']['total_interactions_current'] = (int) $value;
                            $result['engagement']['interactions_api_description'] = $metric['description'];
                            break;
                    }
                }
            }
            /* PREVIOUS MONTH DATA */
            $prevEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'accounts_engaged,total_interactions',
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            //Log::info('Previous Month Engagement Response: ' . print_r($prevEngagementResponse, true));

            if (!empty($prevEngagementResponse['data'])) {
                foreach ($prevEngagementResponse['data'] as $metric) {
                    $metricName = $metric['name'] ?? '';
                    $value = $metric['total_value']['value'] ?? 0;

                    switch ($metricName) {
                        case 'accounts_engaged':
                            $result['engagement']['accounts_engaged_previous'] = (int) $value;
                            break;

                        case 'total_interactions':
                            $result['engagement']['total_interactions_previous'] = (int) $value;
                            break;
                    }
                }
            }
            $prevEngaged = $result['engagement']['accounts_engaged_previous'];
            $currEngaged = $result['engagement']['accounts_engaged_current'];
            $prevInteractions = $result['engagement']['total_interactions_previous'];
            $currInteractions = $result['engagement']['total_interactions_current'];
            $result['engagement']['accounts_engaged_percent_change'] = $prevEngaged > 0
                ? round((($currEngaged - $prevEngaged) / $prevEngaged) * 100, 2)
                : 0;

            $result['engagement']['total_interactions_percent_change'] = $prevInteractions > 0
                ? round((($currInteractions - $prevInteractions) / $prevInteractions) * 100, 2)
                : 0;
            /*
            FORMULLA THIS USE
            Percentage Change=Previous(Current−Previous)​×100
            */
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
                'api_description' => 'No description available',
            ],
            'reach_prev' => [
                'paid' => 0,
                'organic' => 0,
                'followers' => 0,
                'non_followers' => 0,
            ]
        ];

        try {
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

            // ==========================================================
            // CURRENT MONTH DATA
            // ==========================================================

            $response = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'reach',
                'period' => 'day',
                'breakdown' => 'media_product_type,follow_type',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            //Log::info("Current Month Reach Response: " . print_r($response, true));
            //Log::info("Current Month Reach log : https://graph.facebook.com/v24.0/{$accountId}/insights?metric=reach&period=day&breakdown=media_product_type,follow_type&metric_type=total_value&since={$since}&until={$until}&access_token={$token}");


            if (isset($response['data'][0]['total_value']['breakdowns'][0]['results'])) {
                $result['reach']['api_description'] = $response['data'][0]['description'] ?? 'No description available';
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
            // ==========================================================
            // PREVIOUS MONTH DATA
            // ==========================================================           

            $prevResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => 'reach',
                'period' => 'day',
                'breakdown' => 'media_product_type,follow_type',
                'metric_type' => 'total_value',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $token,
            ])->json();
            //Log::info("Previous Month Reach log : https://graph.facebook.com/v24.0/{$accountId}/insights?metric=reach&period=day&breakdown=media_product_type,follow_type&metric_type=total_value&since={$previousSince}&until={$previousUntil}&access_token={$token}");
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
                'message' => 'Reach data fetched successfully.',
                'reach' => [
                    'api_description' => $result['reach']['api_description'],
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
        $start = \Carbon\Carbon::parse($startDate)->startOfDay()->clone();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay()->clone();
        $days = (int) round($start->diffInDays($end))+1;
        
        $prevEnd = $start->copy()->subDay()->endOfDay()->clone();
        $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay()->clone();

        $since = $start->timestamp;
        $until = $end->timestamp;
        $previousSince = $prevStart->timestamp;
        $previousUntil = $prevEnd->timestamp;

        Log::info('Date Range Fetch Media:', [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'days' => $days,
            'prevStart' => $prevStart->toDateString(),
            'prevEnd' => $prevEnd->toDateString(),
            'since (timestamp)' => $since,
            'until (timestamp)' => $until,
            'previousSince (timestamp)' => $previousSince,
            'previousUntil (timestamp)' => $previousUntil,
        ]);
        /* ===== Followers / Unfollowers (Current) ===== */
        $current_month_followers = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'follows_and_unfollows',
            'period' => 'day',
            'breakdown' => 'follow_type',
            'metric_type' => 'total_value',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        $followers = 0;
        $unfollowers = 0;
        $api_description = $current_month_followers['data'][0]['description'] ?? '';

        if (isset($current_month_followers['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($current_month_followers['data'][0]['total_value']['breakdowns'][0]['results'] as $result) {
                $type = $result['dimension_values'][0] ?? '';
                $value = $result['value'] ?? 0;
                if ($type === 'FOLLOWER') $followers += $value;
                elseif ($type === 'NON_FOLLOWER') $unfollowers += $value;
            }
        }

        /* ===== Followers / Unfollowers (Previous) ===== */
        $previous_month_followers = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'follows_and_unfollows',
            'period' => 'day',
            'breakdown' => 'follow_type',
            'metric_type' => 'total_value',
            'since' => $previousSince,
            'until' => $previousUntil,
            'access_token' => $token,
        ])->json();

        $previous_followers = 0;
        $previous_unfollowers = 0;
        if (isset($previous_month_followers['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($previous_month_followers['data'][0]['total_value']['breakdowns'][0]['results'] as $result) {
                $pre_type = $result['dimension_values'][0] ?? '';
                $pre_value = $result['value'] ?? 0;
                if ($pre_type === 'FOLLOWER') $previous_followers += $pre_value;
                elseif ($pre_type === 'NON_FOLLOWER') $previous_unfollowers += $pre_value;
            }
        }

        /* ===== Views (Current) ===== */
        $current_views = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'views',
            'period' => 'day',
            'breakdown' => 'follow_type',
            'metric_type' => 'total_value',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        $views_followers = 0;
        $views_non_followers = 0;
        $views_unknown = 0;
        $views_api_description = $current_views['data'][0]['description'] ?? '';

        if (isset($current_views['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($current_views['data'][0]['total_value']['breakdowns'][0]['results'] as $result) {
                $type = $result['dimension_values'][0] ?? '';
                $value = $result['value'] ?? 0;
                if ($type === 'FOLLOWER') $views_followers += $value;
                elseif ($type === 'NON_FOLLOWER') $views_non_followers += $value;
                elseif ($type === 'UNKNOWN') $views_unknown += $value;
            }
        }

        /* ===== Views (Previous) ===== */
        $previous_views = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'views',
            'period' => 'day',
            'breakdown' => 'follow_type',
            'metric_type' => 'total_value',
            'since' => $previousSince,
            'until' => $previousUntil,
            'access_token' => $token,
        ])->json();

        $prev_views_followers = 0;
        $prev_views_non_followers = 0;
        $prev_views_unknown = 0;

        if (isset($previous_views['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($previous_views['data'][0]['total_value']['breakdowns'][0]['results'] as $result) {
                $type = $result['dimension_values'][0] ?? '';
                $value = $result['value'] ?? 0;
                if ($type === 'FOLLOWER') $prev_views_followers += $value;
                elseif ($type === 'NON_FOLLOWER') $prev_views_non_followers += $value;
                elseif ($type === 'UNKNOWN') $prev_views_unknown += $value;
            }
        }
         /* ===== Interactions (Current) ===== */
        $currentInteractions = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'likes,comments,saves,shares,reposts',
            'period' => 'day',
            'metric_type' => 'total_value',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        $likes = $comments = $saves = $shares = $reposts = 0;
        $likes_desc = $comments_desc = $saves_desc = $shares_desc = $reposts_desc = '';

        if (isset($currentInteractions['data'])) {
            foreach ($currentInteractions['data'] as $metric) {
                $name = $metric['name'] ?? '';
                $value = $metric['total_value']['value'] ?? 0;
                $desc = $metric['description'] ?? '';

                switch ($name) {
                    case 'likes': $likes = $value; $likes_desc = $desc; break;
                    case 'comments': $comments = $value; $comments_desc = $desc; break;
                    case 'saves': $saves = $value; $saves_desc = $desc; break;
                    case 'shares': $shares = $value; $shares_desc = $desc; break;
                    case 'reposts': $reposts = $value; $reposts_desc = $desc; break;
                }
            }
        }

        /* ===== Interactions (Previous) ===== */
        $previousInteractions = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'likes,comments,saves,shares,reposts',
            'period' => 'day',
            'metric_type' => 'total_value',
            'since' => $previousSince,
            'until' => $previousUntil,
            'access_token' => $token,
        ])->json();

        $pre_likes = $pre_comments = $pre_saves = $pre_shares = $pre_reposts = 0;
        if (isset($previousInteractions['data'])) {
            foreach ($previousInteractions['data'] as $metric) {
                $name = $metric['name'] ?? '';
                $value = $metric['total_value']['value'] ?? 0;
                switch ($name) {
                    case 'likes': $pre_likes = $value; break;
                    case 'comments': $pre_comments = $value; break;
                    case 'saves': $pre_saves = $value; break;
                    case 'shares': $pre_shares = $value; break;
                    case 'reposts': $pre_reposts = $value; break;
                }
            }
        }

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

        //Log::info("Total Interactions Current Response:", $currentRes);
        $totalInteractionsDesc = $currentRes['data'][0]['description'] ?? '';
        $currentByType = $mediaTypes;

        if (
            isset($currentRes['data'][0]['total_value']['breakdowns'][0]['results']) &&
            is_array($currentRes['data'][0]['total_value']['breakdowns'][0]['results'])
        ) {
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
        //Log::info("Total Interactions Previous Response:", $previousRes);
        $previousByType = $mediaTypes;

        if (
            isset($previousRes['data'][0]['total_value']['breakdowns'][0]['results']) &&
            is_array($previousRes['data'][0]['total_value']['breakdowns'][0]['results'])
        ) {
            foreach ($previousRes['data'][0]['total_value']['breakdowns'][0]['results'] as $item) {
                $type = strtoupper($item['dimension_values'][0] ?? '');
                $value = (int) ($item['value'] ?? 0);
                if (isset($previousByType[$type])) {
                    $previousByType[$type] += $value;
                }
            }
        }
        /* ===== Combine ===== */
        $combinedInteractions = [];
        foreach ($mediaTypes as $type => $_) {
            $prev = $previousByType[$type] ?? 0;
            $curr = $currentByType[$type] ?? 0;
            $percent = ($prev == 0)
                ? ($curr > 0 ? 100 : 0)
                : round((($curr - $prev) / $prev) * 100, 2);
            $status = $percent > 0 ? '↑' : ($percent < 0 ? '↓' : '-');

            $combinedInteractions[$type] = [
                'api_description' => $totalInteractionsDesc,
                'previous' => $prev,
                'current' => $curr,
                'percent' => $percent,
                'status' => $status,
            ];
        }

        /* ===== Current Media ===== */
        $mediaResponseCurrent = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/media", [
            'fields' => 'media_type,media_product_type,like_count,comments_count,timestamp',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();
        //Log::info("Current Month Media Date Range: {$since} to {$until}");
        Log::info("Current Month Media : https://graph.facebook.com/v24.0/{$accountId}/media?fields=media_type,media_product_type,like_count,comments_count,timestamp&since={$since}&until={$until}&access_token={$token}");
        Log::info("Current Month Media : ");
        $posts = $stories = $reels = $totalInteractions = 0;
        if (isset($mediaResponseCurrent['data'])) {
            foreach ($mediaResponseCurrent['data'] as $media) {
                $mediaType = $media['media_type'] ?? '';
                $productType = $media['media_product_type'] ?? '';
                if ($productType === 'STORIES') {
                    $stories++;
                } elseif ($productType === 'REELS') {
                    $reels++;
                } elseif ($mediaType === 'CAROUSEL_ALBUM' || $mediaType === 'IMAGE') {
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
                elseif ($preMediaType === 'CAROUSEL_ALBUM' || $preMediaType === 'IMAGE'){
                     $pre_posts++;
                }

                $pre_totalInteractions += ($mediaPrev['like_count'] ?? 0) + ($mediaPrev['comments_count'] ?? 0);
            }
        }

        /** ===== Final Combined Data ===== */
        $data = [
            'followers' => [
                'api_description' => $api_description,
                'previous' => $previous_followers,
                'current' => $followers
            ],
            'unfollowers' => [
                'api_description' => $api_description,
                'previous' => $previous_unfollowers,
                'current' => $unfollowers
            ],
            'views_followers' => [
                'api_description' => $views_api_description,
                'previous' => $prev_views_followers,
                'current' => $views_followers
            ],
            'views_non_followers' => [
                'api_description' => $views_api_description,
                'previous' => $prev_views_non_followers,
                'current' => $views_non_followers
            ],
            'views_unknown' => [
                'api_description' => $views_api_description,
                'previous' => $prev_views_unknown,
                'current' => $views_unknown
            ],
            'posts' => ['previous' => $pre_posts, 'current' => $posts],
            'stories' => ['previous' => $pre_stories, 'current' => $stories],
            'reels' => ['previous' => $pre_reels, 'current' => $reels],
            'content_interaction' => ['previous' => $pre_totalInteractions, 'current' => $totalInteractions],

            'likes' => ['api_description' => $likes_desc, 'previous' => $pre_likes, 'current' => $likes],
            'comments' => ['api_description' => $comments_desc, 'previous' => $pre_comments, 'current' => $comments],
            'saves' => ['api_description' => $saves_desc, 'previous' => $pre_saves, 'current' => $saves],
            'shares' => ['api_description' => $shares_desc, 'previous' => $pre_shares, 'current' => $shares],
            'reposts' => ['api_description' => $reposts_desc, 'previous' => $pre_reposts, 'current' => $reposts],
            'total_interactions_by_media_type' => $combinedInteractions,

        ];

        /* ===== Percent & Status (↑↓) Calculation ===== */
        $final = [];
        foreach ($data as $key => $value) {
            if ($key === 'total_interactions_by_media_type') {
                $final[$key] = $value;
                continue;
            }
            $prev = $value['previous'] ?? 0;
            $cur = $value['current'] ?? 0;

            if ($prev == 0) {
                $percent = $cur > 0 ? 100 : 0;
            } else {
                $percent = round((($cur - $prev) / $prev) * 100, 2);
            }

            $status = $percent > 0 ? '↑' : ($percent < 0 ? '↓' : '-');

            $final[$key] = [
                'api_description' => $value['api_description'] ?? '',
                'previous' => $prev,
                'current' => $cur,
                'percent' => $percent,
                'status' => $status,
            ];
        }

        return $final;
    }

    private function renderDashboardHtml($instagramId, $performanceData)
    {
        Log::info('Rendering Dashboard HTML with Performance Data: ' . print_r($performanceData, true));
        /* Date range */
        $dateRange = $performanceData['date_range']['display'] ?? '';
        /* Instagram Reach (object) */
        $instagramReach = $performanceData['instagramReach'] ?? null;
        $paidReach        = $instagramReach->reach->current_month->paid ?? 0;
        $organicReach     = $instagramReach->reach->current_month->organic ?? 0;
        $followersReach   = $instagramReach->reach->current_month->followers ?? 0;
        $nonFollowersReach = $instagramReach->reach->current_month->non_followers ?? 0;

        $formattedPaidReach       = $this->formatNumber($paidReach);
        $formattedOrganicReach    = $this->formatNumber($organicReach);
        $formattedFollowersReach  = $this->formatNumber($followersReach);
        $formattedNonFollowersReach = $this->formatNumber($nonFollowersReach);

        $currentReach  = $instagramReach->reach->current_month->total ?? 0;
        $previousReach = $instagramReach->reach->previous_month->total ?? 0;
        $reachPercentage = $instagramReach->reach->percent_change ?? 0;
        $reachTrend = $reachPercentage >= 0 ? 'positive' : 'negative';

        /* Profile Visits */
        $currentProfile  = $performanceData['profile_visits']['current_profile'] ?? 0;
        $previousProfile = $performanceData['profile_visits']['previous_profile'] ?? 0;
        $profilePercentage = $performanceData['profile_visits']['percent_change'] ?? 0;

        /* Engagement */
        $accountEngagedCurrent  = $performanceData['engagement']['accounts_engaged_current'] ?? 0;
        $accountEngagedPrevious = $performanceData['engagement']['accounts_engaged_previous'] ?? 0;
        $accountEngagedPercent  = $performanceData['engagement']['accounts_engaged_percent_change'] ?? 0;

        $totalInteractionsCurrent  = $performanceData['engagement']['total_interactions_current'] ?? 0;
        $totalInteractionsPrevious = $performanceData['engagement']['total_interactions_previous'] ?? 0;
        $totalInteractionsPercent  = $performanceData['engagement']['total_interactions_percent_change'] ?? 0;

        /* Profile Link Clicks */
        $profileLinkCurrent = $performanceData['profile_link']['current'] ?? 0;
        $profileLinkPrevious = $performanceData['profile_link']['previous'] ?? 0;
        $profileLinkPercent = $performanceData['profile_link']['percent_change'] ?? 0;

        /* Followers & Unfollowers*/
        $followersCurrent  = $performanceData['followers']['current'] ?? 0;
        $followersPrevious = $performanceData['followers']['previous'] ?? 0;
        $followersPerce    = $performanceData['followers']['percent'] ?? 0;
        $followersStatus   = $performanceData['followers']['status'] ?? '-';

        $unfollowersCurrent  = $performanceData['unfollowers']['current'] ?? 0;
        $unfollowersPrevious = $performanceData['unfollowers']['previous'] ?? 0;
        $unfollowersPerce    = $performanceData['unfollowers']['percent'] ?? 0;
        $unfollowersStatus   = $performanceData['unfollowers']['status'] ?? '-';

        /* Posts, Stories, Reels */
        $postsCurrent   = $performanceData['posts']['current'] ?? 0;
        $postsPrevious  = $performanceData['posts']['previous'] ?? 0;
        $postsPerce     = $performanceData['posts']['percent'] ?? 0;
        $postsStatus    = $performanceData['posts']['status'] ?? '-';

        $storiesCurrent  = $performanceData['stories']['current'] ?? 0;
        $storiesPrevious = $performanceData['stories']['previous'] ?? 0;
        $storiesPerce    = $performanceData['stories']['percent'] ?? 0;
        $storiesStatus   = $performanceData['stories']['status'] ?? '-';

        $reelsCurrent  = $performanceData['reels']['current'] ?? 0;
        $reelsPrevious = $performanceData['reels']['previous'] ?? 0;
        $reelsPerce    = $performanceData['reels']['percent'] ?? 0;
        $reelsStatus   = $performanceData['reels']['status'] ?? '-';

        /**View follower and non followers */
        $views_followers_non_foll_desc   = $performanceData['views_followers']['api_description'] ?? '-';
        $views_followers_current   = $performanceData['views_followers']['current'] ?? '-';
        $views_followers_previous   = $performanceData['views_followers']['previous'] ?? '-';
        $views_followers_perce   = $performanceData['views_followers']['percent'] ?? '-';

        $views_non_followers_current   = $performanceData['views_non_followers']['current'] ?? '-';
        $views_non_followers_previous   = $performanceData['views_non_followers']['previous'] ?? '-';
        $views_non_followers_perce   = $performanceData['views_non_followers']['percent'] ?? '-';
        /**View follower and non followers */
        /**tooltips */
        $reachDescription = $performanceData['instagramReach']->reach->api_description ?? '';
        $profileVisitDescription = $performanceData['profile_visits']['api_description'] ?? '';
        $profileLinkDescription = $performanceData['profile_link']['api_description'] ?? '';
        $engagementDescription = $performanceData['engagement']['account_engaged_api_description'] ?? '';
        $interactionsDescription = $performanceData['engagement']['interactions_api_description'] ?? '';
        $followUnfollowDescription = $performanceData['followers']['api_description'] ?? '';
        /*Multiple interactions */
        $intraCurrentLikes      = $performanceData['likes']['current'] ?? '-';
        $intraCurrentComments   = $performanceData['comments']['current'] ?? '-';
        $intraCurrentSaves      = $performanceData['saves']['current'] ?? '-';
        $intraCurrentShares     = $performanceData['shares']['current'] ?? '-';
        $intraCurrentReposts    = $performanceData['reposts']['current'] ?? '-';

        $intraPrevLikes         = $performanceData['likes']['previous'] ?? '-';
        $intraPrevComments      = $performanceData['comments']['previous'] ?? '-';
        $intraPrevSaves         = $performanceData['saves']['previous'] ?? '-';
        $intraPrevShares        = $performanceData['shares']['previous'] ?? '-';
        $intraPrevReposts       = $performanceData['reposts']['previous'] ?? '-';

        $intraPercentLikes      = $performanceData['likes']['percent'] ?? '-';
        $intraPercentComments   = $performanceData['comments']['percent'] ?? '-';
        $intraPercentSaves      = $performanceData['saves']['percent'] ?? '-';
        $intraPercentShares     = $performanceData['shares']['percent'] ?? '-';
        $intraPercentReposts    = $performanceData['reposts']['percent'] ?? '-';        

        $intraDescLikes         = $performanceData['likes']['api_description'] ?? '-';
        $intraDescComments      = $performanceData['comments']['api_description'] ?? '-';
        $intraDescSaves         = $performanceData['saves']['api_description'] ?? '-';
        $intraDescShares        = $performanceData['shares']['api_description'] ?? '-';
        $intraDescReposts       = $performanceData['reposts']['api_description'] ?? '-';
       
        $html = '
        <div class="card">
            <div class="card-header text-white">
               <h4 class="card-title mb-0">
                    Performance (<span class="text-info">' . $dateRange . '</span>)                   
               </h4>
            </div>
            <div class="card-body">
                <div class="reach-section mb-4">
                    <div class="row g-4">
                        <div class="col-md-4 reach col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h4>
                                        Reach
                                        <i class="bx bx-question-mark text-primary" 
                                        style="cursor: pointer; font-size: 18px;" 
                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                        data-bs-custom-class="success-tooltip"
                                        data-bs-title="' . e($reachDescription) . '">
                                        </i>     
                                    </h4>
                                </div>
                                <div class="metric-body">
                                    <table class="table table-sm mb-2 align-middle text-center">
                                        <tr>
                                            <th><h3 class="mb-0">' . $this->formatNumber($previousReach) . '</h3></th>
                                            <th><h3 class="mb-0">' . $this->formatNumber($currentReach) . '</h3></th>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Previous Month</td>
                                            <td class="bg-black text-light">Current Month</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="' . ($reachPercentage > 0 ? 'positive' : ($reachPercentage < 0 ? 'negative' : 'neutral')) . '">
                                                <h4 class="mb-0">' . ($reachPercentage > 0 ? "▲ +" : ($reachPercentage < 0 ? "▼ " : "➖ ")) . abs($reachPercentage) . '%</h4>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Paid Reach</td>
                                            <td class="bg-black text-light">Organic Reach</td>
                                        </tr>
                                        <tr>
                                            <td><h4 class="mb-0">' . $formattedPaidReach . '</h4></td>
                                            <td><h4 class="mb-0">' . $formattedOrganicReach . '</h4></td>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Followers</td>
                                            <td class="bg-black text-light">Non-Followers</td>
                                        </tr>
                                        <tr>
                                            <td><h4 class="mb-0">' . $formattedFollowersReach . '</h4></td>
                                            <td><h4 class="mb-0">' . $formattedNonFollowersReach . '</h4></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 followers">
                            <div class="row">
                                <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                    <div class="metric-card">
                                        <div class="metric-header">
                                            <h4>
                                                Followers
                                                <i class="bx bx-question-mark text-primary" 
                                                style="cursor: pointer; font-size: 18px;" 
                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                data-bs-custom-class="success-tooltip"
                                                data-bs-title="' . e($followUnfollowDescription) . '">
                                                </i> 
                                            </h4>
                                        </div>
                                        <div class="metric-body">
                                            <table class="table table-sm mb-2 align-middle text-center">
                                                
                                                <tr>
                                                    <th>
                                                        <h3 class="mb-0">
                                                            ' . $followersPrevious . '
                                                        </h3>
                                                    </th>
                                                    <th>
                                                        <h3 class="mb-0">
                                                            ' . $followersCurrent . '
                                                        </h3>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td class="bg-black text-light">Previous Month</td>
                                                    <td class="bg-black text-light">Current Month</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="' . ($followersPerce > 0 ? 'positive' : ($followersPerce < 0 ? 'negative' : 'neutral')) . '">
                                                        <h4 class="mb-0">' . ($followersPerce > 0 ? "▲ +" : ($followersPerce < 0 ? "▼ " : "➖ ")) . abs($followersPerce) . '%</h4>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                    <div class="metric-card">
                                        <div class="metric-header">
                                            <h4>
                                                Unfollowers
                                                <i class="bx bx-question-mark text-primary" 
                                                style="cursor: pointer; font-size: 18px;" 
                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                data-bs-custom-class="success-tooltip"
                                                data-bs-title="' . e($followUnfollowDescription) . '">
                                                </i> 
                                            </h4>
                                        </div>
                                        <div class="metric-body">
                                            <table class="table table-sm mb-2 align-middle text-center">
                                                
                                                <tr>
                                                    <th>
                                                        <h3 class="mb-0">
                                                            ' . $unfollowersPrevious . '
                                                        </h3>
                                                    </th>
                                                    <th>
                                                        <h3 class="mb-0">
                                                            ' . $unfollowersCurrent . '
                                                        </h3>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td class="bg-black text-light">Previous Month</td>
                                                    <td class="bg-black text-light">Current Month</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="' . ($unfollowersPerce > 0 ? 'positive' : ($unfollowersPerce < 0 ? 'negative' : 'neutral')) . '">
                                                        <h4 class="mb-0">' . ($unfollowersPerce > 0 ? "▲ +" : ($unfollowersPerce < 0 ? "▼ " : "➖ ")) . abs($unfollowersPerce) . '%</h4>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>  
                        <div class="col-md-4 view col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h4>
                                        View
                                        <i class="bx bx-question-mark text-primary" 
                                        style="cursor: pointer; font-size: 18px;" 
                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                        data-bs-custom-class="success-tooltip"
                                        data-bs-title="' . e($views_followers_non_foll_desc) . '">
                                        </i> 
                                    </h4>
                                </div>
                                <div class="metric-body">
                                    <table class="table table-sm mb-2 align-middle text-center">
                                        <tr>
                                            <td colspan="2" class="bg-black text-light">Followers</td>                                        
                                        </tr>
                                        <tr>
                                            <td><h4 class="mb-0">' . $this->formatNumber($views_followers_previous) . '</h4></td>
                                            <td><h4 class="mb-0">' . $this->formatNumber($views_followers_current) . '</h4></td>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Previous Month</td>
                                            <td class="bg-black text-light">Current Month</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="' . ($views_followers_perce > 0 ? 'positive' : ($views_followers_perce < 0 ? 'negative' : 'neutral')) . '">
                                                <h4 class="mb-0">' . ($views_followers_perce > 0 ? "▲ +" : ($views_followers_perce < 0 ? "▼ " : "➖ ")) . abs($views_followers_perce) . '%</h4>
                                            </td>
                                        </tr> 
                                        <tr>
                                            <td colspan="2" class="bg-black text-light">Non Followers</td>                                        
                                        </tr>
                                        <tr>
                                            <td><h4 class="mb-0">' . $this->formatNumber($views_non_followers_previous) . '</h4></td>
                                            <td><h4 class="mb-0">' . $this->formatNumber($views_non_followers_current) . '</h4></td>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Previous Month</td>
                                            <td class="bg-black text-light">Current Month</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="' . ($views_non_followers_perce > 0 ? 'positive' : ($views_non_followers_perce < 0 ? 'negative' : 'neutral')) . '">
                                                <h4 class="mb-0">' . ($views_non_followers_perce > 0 ? "▲ +" : ($views_non_followers_perce < 0 ? "▼ " : "➖ ")) . abs($views_non_followers_perce) . '%</h4>
                                            </td>
                                        </tr>                                       
                                    </table>
                                </div>
                            </div>
                        </div> 
                    </div>
                </div>
                <div class="row g-4"> 
                    <div class="col-md-12 col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <h4>
                                            Total Interactions
                                            <i class="bx bx-question-mark text-primary" 
                                            style="cursor: pointer; font-size: 18px;" 
                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="' . e($interactionsDescription) . '">
                                            </i> 
                                        </h4>
                                    </div>
                                    <div class="metric-body">
                                        <table class="table table-sm mb-3 align-middle text-center">
                                            
                                            <tr>
                                                <td><h4 class="mb-0">' . $this->formatNumber($totalInteractionsPrevious) . '</h4></td>
                                                <td><h4 class="mb-0">' . $this->formatNumber($totalInteractionsCurrent) . '</h4></td>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Previous Month</td>
                                                <td class="bg-black text-light">Current Month</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="' . ($totalInteractionsPercent > 0 ? 'positive' : ($totalInteractionsPercent < 0 ? 'negative' : 'neutral')) . '">
                                                    <h4 class="mb-0">' . ($totalInteractionsPercent > 0 ? "▲ +" : ($totalInteractionsPercent < 0 ? "▼ " : "➖ ")) . abs($totalInteractionsPercent) . '%</h4>
                                                </td>
                                            </tr>                                       
                                        </table>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="col-lg-12 mb-2">
                                                    <h5 class="card-title mb-0">Total Interactions by Likes, Comments, Saves, Shares, Reposts </h5>
                                                </div>
                                                <table class="table table-bordered table-sm mb-2 ">                                            
                                                    <tr>
                                                        <td class="bg-black text-light">Previous Month</td>
                                                        <td class="bg-black text-light">Current Month</td>
                                                    </tr>
                                                    <tr>
                                                        <!-- Previous Month -->
                                                        <td>
                                                            <table class="table table-sm mb-2 "> 
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0"> 
                                                                            Likes
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescLikes) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td><h4 class="mb-0">' . $this->formatNumber($intraPrevLikes) . '</h4></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0"> 
                                                                            Comments
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescComments) . '">
                                                                            </i>
                                                                        </h4> 
                                                                    </td>
                                                                    <td><h4 class="mb-0">' . $this->formatNumber($intraPrevComments) . '</h4></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Saves
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescSaves) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td><h4 class="mb-0">' . $this->formatNumber($intraPrevSaves) . '</h4></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Shares
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescShares) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td><h4 class="mb-0">' . $this->formatNumber($intraPrevShares) . '</h4></td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Reposts
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescReposts) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td><h4 class="mb-0">' . $this->formatNumber($intraPrevReposts) . '</h4></td>
                                                                </tr>
                                                            </table>
                                                        </td>

                                                        <!-- Current Month -->
                                                        <td>
                                                            <table class="table  table-sm mb-2"> 
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Likes 
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescLikes) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                        ' . $this->formatNumber($intraCurrentLikes) . '
                                                                        <small class="' . ($intraPercentLikes > 0 ? 'text-success' : ($intraPercentLikes < 0 ? 'text-danger' : 'text-muted')) . '">
                                                                            ' . ($intraPercentLikes > 0 ? '▲ +' : ($intraPercentLikes < 0 ? '▼ ' : '➖ ')) . abs($intraPercentLikes) . '%</small>
                                                                        </h4>
                                                                        
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Comments
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescComments) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                        ' . $this->formatNumber($intraCurrentComments) . '
                                                                        <small class="' . ($intraPercentComments > 0 ? 'text-success' : ($intraPercentComments < 0 ? 'text-danger' : 'text-muted')) . '">
                                                                            ' . ($intraPercentComments > 0 ? '▲ +' : ($intraPercentComments < 0 ? '▼ ' : '➖ ')) . abs($intraPercentComments) . '%</small>
                                                                        </h4>
                                                                        
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Saves
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescSaves) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                        ' . $this->formatNumber($intraCurrentSaves) . '
                                                                        <small class="' . ($intraPercentSaves > 0 ? 'text-success' : ($intraPercentSaves < 0 ? 'text-danger' : 'text-muted')) . '">
                                                                            ' . ($intraPercentSaves > 0 ? '▲ +' : ($intraPercentSaves < 0 ? '▼ ' : '➖ ')) . abs($intraPercentSaves) . '%</small>
                                                                        </h4>
                                                                        
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Shares
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescShares) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                        ' . $this->formatNumber($intraCurrentShares) . '
                                                                        <small class="' . ($intraPercentShares > 0 ? 'text-success' : ($intraPercentShares < 0 ? 'text-danger' : 'text-muted')) . '">
                                                                            ' . ($intraPercentShares > 0 ? '▲ +' : ($intraPercentShares < 0 ? '▼ ' : '➖ ')) . abs($intraPercentShares) . '%</small>
                                                                        </h4>
                                                                        
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                            Reposts
                                                                            <i class="bx bx-question-mark text-primary" 
                                                                                style="cursor: pointer; font-size: 18px;" 
                                                                                data-bs-toggle="tooltip" data-bs-placement="top" 
                                                                                data-bs-custom-class="success-tooltip" 
                                                                                data-bs-title="' . e($intraDescReposts) . '">
                                                                            </i>
                                                                        </h4>
                                                                    </td>
                                                                    <td>
                                                                        <h4 class="mb-0">
                                                                        ' . $this->formatNumber($intraCurrentReposts) . '
                                                                        <small class="' . ($intraPercentReposts > 0 ? 'text-success' : ($intraPercentReposts < 0 ? 'text-danger' : 'text-muted')) . '">
                                                                            ' . ($intraPercentReposts > 0 ? '▲ +' : ($intraPercentReposts < 0 ? '▼ ' : '➖ ')) . abs($intraPercentReposts) . '%</small>
                                                                        </h4>
                                                                        
                                                                    </td>
                                                                </tr>
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>';
                                            $html .= '
                                            <div class="col-lg-6">
                                                <div class="col-lg-12 mb-2">
                                                    <h5 class="card-title mb-0">Total Interactions by Media Type</h5>
                                                </div>
                                                <table class="table table-bordered table-sm mb-2">
                                                    <tr>
                                                        <td class="bg-black text-light">Previous Month</td>
                                                        <td class="bg-black text-light">Current Month</td>
                                                    </tr>
                                                    <tr>
                                                        <!-- Previous Month -->
                                                        <td>
                                                            <table class="table table-sm mb-2">';
                                                                if (!empty($performanceData["total_interactions_by_media_type"])) {
                                                                    foreach ($performanceData["total_interactions_by_media_type"] as $type => $data) {
                                                                        $desc = e($data["api_description"] ?? "-");
                                                                        $prev = $this->formatNumber($data["previous"] ?? 0);

                                                                        $html .= '
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">' . ucfirst(strtolower($type)) . '
                                                                                    <i class="bx bx-question-mark text-primary"
                                                                                        style="cursor: pointer; font-size: 18px;"
                                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                        data-bs-custom-class="success-tooltip"
                                                                                        data-bs-title="' . $desc . '">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td><h4 class="mb-0">' . $prev . '</h4></td>
                                                                        </tr>';
                                                                    }
                                                                }
                                                                $html .= '
                                                            </table>
                                                        </td>

                                                        <!-- Current Month -->
                                                        <td>
                                                            <table class="table table-sm mb-2">';
                                                                if (!empty($performanceData["total_interactions_by_media_type"])) {
                                                                    foreach ($performanceData["total_interactions_by_media_type"] as $type => $data) {
                                                                        $desc = e($data["api_description"] ?? "-");
                                                                        $curr = $this->formatNumber($data["current"] ?? 0);
                                                                        $percent = $data["percent"] ?? 0;

                                                                        $colorClass = $percent > 0 ? "text-success" : ($percent < 0 ? "text-danger" : "text-muted");
                                                                        $arrow = $percent > 0 ? "▲ +" : ($percent < 0 ? "▼ " : "➖ ");

                                                                        $html .= '
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">' . ucfirst(strtolower($type)) . '
                                                                                    <i class="bx bx-question-mark text-primary"
                                                                                        style="cursor: pointer; font-size: 18px;"
                                                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                                                        data-bs-custom-class="success-tooltip"
                                                                                        data-bs-title="' . $desc . '">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    ' . $curr . '
                                                                                    <small class="' . $colorClass . '">' . $arrow . abs($percent) . '%</small>
                                                                                </h4>
                                                                            </td>
                                                                        </tr>';
                                                                    }
                                                                }
                                                            $html .= '
                                                            </table>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <div class="row">
                    <!-- Profile Visits -->
                    <div class="col-md-4 col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                Profile Visits
                                <i class="bx bx-question-mark text-primary" 
                                    style="cursor: pointer; font-size: 18px;" 
                                    data-bs-toggle="tooltip" data-bs-placement="top" 
                                    data-bs-custom-class="success-tooltip"
                                    data-bs-title="' . e($profileVisitDescription) . '">
                                </i> 
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <th><h3 class="mb-0">' . $this->formatNumber($previousProfile) . '</h3></th>
                                        <th><h3 class="mb-0">' . $this->formatNumber($currentProfile) . '</h3></th>
                                    </tr>
                                    <tr>
                                        <td class="bg-black text-light">Previous Month</td>
                                        <td class="bg-black text-light">Current Month</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="' . ($profilePercentage > 0 ? 'positive' : ($profilePercentage < 0 ? 'negative' : 'neutral')) . '">
                                            <h4 class="mb-0">' . ($profilePercentage > 0 ? "▲ +" : ($profilePercentage < 0 ? "▼ " : "➖ ")) . abs($profilePercentage) . '%</h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Link Clicks -->
                    <div class="col-md-4 col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <h4>
                                            Profile Link Clicks
                                            <i class="bx bx-question-mark text-primary" 
                                            style="cursor: pointer; font-size: 18px;" 
                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="' . e($profileLinkDescription) . '">
                                    </i> 
                                        </h4>
                                    </div>
                                    <div class="metric-body">
                                        <table class="table table-sm mb-2 align-middle text-center">
                                            ' . (isset($performanceData["profile_link"]["message"]) && !empty($performanceData["profile_link"]["message"])
                                            ? "<tr><td colspan=\"2\" class=\"text-info small text-start\"><i class=\"fas fa-info-circle\"></i> " . e($performanceData["profile_link"]["message"]) . "</td></tr>"
                                            : "") . '
                                            <tr>
                                                <th><h3 class="mb-0">' . $this->formatNumber($profileLinkPrevious) . '</h3></th>
                                                <th><h3 class="mb-0">' . $this->formatNumber($profileLinkCurrent) . '</h3></th>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Previous Month</td>
                                                <td class="bg-black text-light">Current Month</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="' . ($profileLinkPercent > 0 ? 'positive' : ($profileLinkPercent < 0 ? 'negative' : 'neutral')) . '">
                                                    <h4 class="mb-0">' . ($profileLinkPercent > 0 ? "▲ +" : ($profileLinkPercent < 0 ? "▼ " : "➖ ")) . abs($profileLinkPercent) . '%</h4>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Engagement -->
                    <div class="col-md-4 col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2 mb-1">
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <h4>
                                        Engagement
                                        <i class="bx bx-question-mark text-primary" 
                                            style="cursor: pointer; font-size: 18px;" 
                                            data-bs-toggle="tooltip" data-bs-placement="top" 
                                            data-bs-custom-class="success-tooltip"
                                            data-bs-title="' . e($engagementDescription) . '">
                                            </i> 
                                        </h4>
                                    </div>
                                    <div class="metric-body">
                                        <table class="table table-sm mb-2 align-middle text-center">
                                            <tr>
                                                <th><h3 class="mb-0">' . $this->formatNumber($accountEngagedPrevious) . '</h3></th>
                                                <th><h3 class="mb-0">' . $this->formatNumber($accountEngagedCurrent) . '</h3></th>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Previous Month</td>
                                                <td class="bg-black text-light">Current Month</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="' . ($accountEngagedPercent > 0 ? 'positive' : ($accountEngagedPercent < 0 ? 'negative' : 'neutral')) . '">
                                                    <h4 class="mb-0">' . ($accountEngagedPercent > 0 ? "▲ +" : ($accountEngagedPercent < 0 ? "▼ " : "➖ ")) . abs($accountEngagedPercent) . '%</h4>
                                                </td>
                                            </tr>                                        
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="post-section">
                        <div class="row justify-content-center">
                            <div class="col-lg-12">
                                <div class="table-responsive metrics-table mt-3">
                                    <table class="table table-bordered align-middle text-center mb-0">
                                        <thead>
                                            <tr>
                                                <th colspan="2">Number of Posts</th>
                                                <th colspan="2">Number of Stories</th>
                                                <th colspan="2">Number of Reels</th>
                                            </tr>
                                            <tr>
                                                <th class="metric-section-header">Prev. Month</th>
                                                <th class="metric-section-header">Current</th>
                                                <th class="metric-section-header">Prev. Month</th>
                                                <th class="metric-section-header">Current</th>
                                                <th class="metric-section-header">Prev. Month</th>
                                                <th class="metric-section-header">Current</th>                                              
                                                
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="highlight">' . $postsPrevious . '</td>
                                                <td>' . $postsCurrent . '</td>
                                                <td class="highlight">' . $storiesPrevious . '</td>
                                                <td>' . $storiesCurrent . '</td>
                                                <td class="highlight">' . $reelsPrevious . '</td>
                                                <td>' . $reelsCurrent . '</td>
                                                
                                            </tr>
                                            <tr>
                                                <td colspan="2" style="' . $this->getBgColor($postsPerce) . '">' . ($postsPerce > 0 ? '+' : '') . $postsPerce . '%</td>
                                                <td colspan="2" style="' . $this->getBgColor($storiesPerce) . '">' . ($storiesPerce > 0 ? '+' : '') . $storiesPerce . '%</td>
                                                <td colspan="2" style="' . $this->getBgColor($reelsPerce) . '">' . ($reelsPerce > 0 ? '+' : '') . $reelsPerce . '%</td>                                               
                                                
                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>                
            </div>
        </div>';
        return $html;
    }

    private function getBgColor($value)
    {
        if ($value > 0) {
            return 'background-color: #28a745; color: #fff; font-weight:600;';
        } elseif ($value < 0) {
            return 'background-color: #dc3545; color: #fff; font-weight:600;';
        } else {
            return 'background-color: #f8f9fa;';
        }
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


    public function getAudienceTopLocationsOld(Request $request, $instagramId)
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
        $token = SocialTokenHelper::getFacebookToken($mainAccount);
        $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$instagramId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'city',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();

        //Log::info("Top Location : https://graph.facebook.com/v24.0/{$instagramId}/insights?metric=engaged_audience_demographics&period=lifetime&breakdown=city&metric_type=total_value&timeframe={$timeframe}&access_token={$token}");
        //Log::info('Instagram Top Locations API Response:', $response);
        if (isset($response['error'])) {
            return response()->json([
                'success' => false,
                'message' => $response['error']['message']
            ]);
        }
        $results = data_get($response, 'data.0.total_value.breakdowns.0.results', []);
        /*Top 10 cities select */
        $topCities = collect($results)
            ->sortByDesc('value')
            ->take(10)
            ->values();
        $total = $topCities->sum('value');
        $labels = $topCities->map(function ($item) {
            $parts = $item['dimension_values'] ?? [];
            return implode(', ', array_filter($parts));
        });
        $values = $topCities->pluck('value')->toArray();
        $percentages = $topCities->map(function ($item) use ($total) {
            return $total ? round(($item['value'] / $total) * 100, 2) : 0;
        });
        //Log::info('Top Location Labels:', $labels->toArray());
        return response()->json([
            'success' => true,
            'labels' => $labels,
            'values' => $percentages,
        ]);
    }

    public function getAudienceTopLocations(Request $request, $instagramId)
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
        $token = SocialTokenHelper::getFacebookToken($mainAccount);
        $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$instagramId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'city',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();

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

    public function getAudienceAgeGender(Request $request, $instagramId)
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

    public function fetchInstagramReachDaysWise(Request $request, $instagramId)
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
        $igId = $instagramId;

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


    public function fetchInstagramViewDaysWise(Request $request, $instagramId)
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
        $igId = $instagramId;
        $url = "https://graph.facebook.com/v24.0/{$igId}/insights";

        try {
            $response = Http::timeout(15)->get($url, [
                'metric' => 'views',
                'metric_type' => 'total_value',
                'period' => 'day',
                'breakdown' => 'media_product_type',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ]);
            $data = $response->json();

            if (!$response->successful() || empty($data['data'][0]['total_value'])) {
                return response()->json([
                    'success' => false,
                    'message' => $data['error']['message'] ?? 'Unable to fetch views data',
                ]);
            }
            $breakdowns = $data['data'][0]['total_value']['breakdowns'][0]['results'];
            $categories = [];
            $values = [];
            $api_description = $data['data'][0]['description'];

            foreach ($breakdowns as $item) {
                $mediaType = $item['dimension_values'][0];
                $value = $item['value'];
                if ($value < 1) continue;
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
                $values[] = $value;
            }
            return response()->json([
                'success' => true,
                'total_views' => $data['data'][0]['total_value']['value'],
                'categories' => $categories,
                'values' => $values,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'api_description' => $api_description
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching data: ' . $e->getMessage(),
            ]);
        }
    }

    public function fetchInstagramPost(Request $request, $instagramId)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['success' => false, 'error' => 'Facebook account not connected']);
            }
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));            
            $start = \Carbon\Carbon::parse($startDate)->startOfDay()->clone();
            $end = \Carbon\Carbon::parse($endDate)->endOfDay()->clone();
            
            $since = $start->timestamp;
            $until = $end->timestamp;
            $limit = $request->get('limit', 12);
            $after = $request->get('after');
            $before = $request->get('before');
            $sortField = $request->get('sort', 'timestamp');
            $sortOrder = $request->get('order', 'desc');
            $mediaTypeFilter = $request->get('media_type', '');
            $searchFilter = $request->get('search', '');
            $allMedia = [];
            $nextPageUrl = null;
            $params = [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,media_product_type,boost_ads_list{ad_id,ad_status}',
                'access_token' => $token,
                'since' => $since,
                'until' => $until,
                'limit' => 100
            ];
            if ($after) $params['after'] = $after;
            if ($before) $params['before'] = $before;

            do {
                $mediaResponse = Http::timeout(30)
                    ->get("https://graph.facebook.com/v24.0/{$instagramId}/media", $params)
                    ->json();
                Log::info("Fetch Instagram Post API URL: https://graph.facebook.com/v24.0/{$instagramId}/media?" . http_build_query($params));
                
                if (isset($mediaResponse['data'])) {
                    $allMedia = array_merge($allMedia, $mediaResponse['data']);
                }
                $nextPageUrl = $mediaResponse['paging']['next'] ?? null;
                $params = []; 
                if ($nextPageUrl && parse_url($nextPageUrl, PHP_URL_QUERY)) {
                    parse_str(parse_url($nextPageUrl, PHP_URL_QUERY), $queryParams);
                    $params['after'] = $queryParams['after'] ?? null;
                }
                
            } while ($nextPageUrl && count($allMedia) < 500); 
            $filteredMedia = $this->applyFilters($allMedia, $mediaTypeFilter, $searchFilter);
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
                'paging' => $mediaResponse['paging'] ?? [], 
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
            Log::error('Instagram API Error: ' . $e->getMessage());
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




    public function metricsGraph_remove($id, Request $request)
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
    public function postInsightsPage($instagramPageId, $postId)
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
            $instagram = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$instagramPageId}", [
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
            dd($postData);
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


    


    
}
