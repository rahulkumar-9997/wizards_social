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

class FacebookController extends Controller
{
    /**
     * Facebook Page Dashboard
     */
    public function facebookMainIndex($id)
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
            
            // Get user token
            $userToken = SocialTokenHelper::getFacebookToken($mainAccount);
            
            // Get page access token
            $pageToken = SocialTokenHelper::getFacebookPageToken($userToken, $id);
            
            if (!$pageToken) {
                throw new Exception('Failed to get page access token. Please ensure you have admin access to this page.');
            }
            
            /**
             * Fetch Facebook Page Profile
             */
            $facebookBusinessOrProfile = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'id,name,about,category,fan_count,followers_count,picture{url},cover,link,emails,connected_instagram_account,is_published,rating_count,instagram_business_account,is_owned',
                'access_token' => $pageToken,
            ])->json();
            
            return view('backend.pages.facebook.fb-summary.fb-report', compact('facebookBusinessOrProfile'));
            
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

    /**
     * Fetch Facebook Page Performance Data (AJAX)
     */
    public function facebookHtmlAjax($id, Request $request)
    {
        try {
            $fb_page_id = $id;
            $user = Auth::user();
            
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['error' => 'Facebook account not connected'], 400);
            }

            // Get user token
            $userToken = SocialTokenHelper::getFacebookToken($mainAccount);
            
            // Get page access token
            $pageToken = SocialTokenHelper::getFacebookPageToken($userToken, $fb_page_id);
            
            if (!$pageToken) {
                return response()->json(['error' => 'Failed to get Page Access Token. Please ensure you have admin access to this page.'], 400);
            }

            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            
            $performanceData = $this->fetchPerformanceData($fb_page_id, $pageToken, $startDate, $endDate);
            
            $html = $this->renderDashboardHtml($fb_page_id, $performanceData);
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => $performanceData
            ]);
            
        } catch (\Exception $e) {
            Log::error('Facebook fetchHtml error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Fetch all performance data
     */
    private function fetchPerformanceData($pageId, $pageToken, $startDate, $endDate)
    {
        // Fetch account insights with date range
        $insightsData = $this->fetchAccountInsights($pageId, $pageToken, $startDate, $endDate);
        
        // Fetch reactions data
        $reactionsData = $this->fetchReactionsData($pageId, $pageToken, $startDate, $endDate);
        
        // Fetch video data
        $videoData = $this->fetchVideoData($pageId, $pageToken, $startDate, $endDate);

        $performanceData = array_merge($insightsData, $reactionsData, $videoData);

        $performanceData['date_range'] = [
            'start' => $startDate,
            'end' => $endDate,
            'display' => Carbon::parse($startDate)->format('d F Y') . ' - ' . Carbon::parse($endDate)->format('d F Y')
        ];
        
        return $performanceData;
    }

    /**
     * Fetch Facebook Page Insights
     */
    private function fetchAccountInsights($pageId, $pageToken, $startDate, $endDate)
    {
        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
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

            $result = [];

            // ==========================================================
            // REACH DATA
            // ==========================================================
            $result['reach'] = $this->fetchFacebookReach($pageId, $pageToken, $since, $until, $previousSince, $previousUntil);

            // ==========================================================
            // FOLLOWERS DATA
            // ==========================================================
            $result['followers'] = $this->fetchFacebookFollowers($pageId, $pageToken, $since, $until, $previousSince, $previousUntil);

            // ==========================================================
            // ENGAGEMENT DATA
            // ==========================================================
            $result['engagement'] = $this->fetchFacebookEngagement($pageId, $pageToken, $since, $until, $previousSince, $previousUntil);

            // ==========================================================
            // PAGE VIEWS
            // ==========================================================
            $result['page_views'] = $this->fetchFacebookPageViews($pageId, $pageToken, $since, $until, $previousSince, $previousUntil);

            // ==========================================================
            // CTA CLICKS
            // ==========================================================
            $result['cta_clicks'] = $this->fetchFacebookCTAClicks($pageId, $pageToken, $since, $until, $previousSince, $previousUntil);

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Facebook Insights Exception: ' . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Fetch Facebook Reach Data
     */
    private function fetchFacebookReach($pageId, $pageToken, $since, $until, $previousSince, $previousUntil)
    {
        $result = [
            'current_total' => 0,
            'previous_total' => 0,
            'current_paid' => 0,
            'previous_paid' => 0,
            'current_organic' => 0,
            'previous_organic' => 0,
            'api_description' => '',
            'percent_change' => 0
        ];

        try {
            /* CURRENT: Total unique reach */
            $currentReachResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_impressions_unique',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            //Log::info('Current Profile Link Click: ' . print_r($currentProfileClickResponse, true));
            //Log::info("Current Reach Api : https://graph.facebook.com/v24.0/{$pageId}/insights?metric=page_impressions_unique&period=day&since={$since}&until={$until}");

            if (isset($currentReachResponse['data'][0]['values'])) {
                $result['api_description'] = $currentReachResponse['data'][0]['description'] ?? '';
                $result['current_total'] = collect($currentReachResponse['data'][0]['values'])->sum('value');
            }

            /* PREVIOUS: Total unique reach */
            $previousReachResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_impressions_unique',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousReachResponse['data'][0]['values'])) {
                $result['previous_total'] = collect($previousReachResponse['data'][0]['values'])->sum('value');
            }

            /* CURRENT: Paid unique reach */
            $currentPaidResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_impressions_paid_unique',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentPaidResponse['data'][0]['values'])) {
                $result['current_paid'] = collect($currentPaidResponse['data'][0]['values'])->sum('value');
            }            

            /* PREVIOUS: Paid unique reach */
            $previousPaidResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_impressions_paid_unique',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousPaidResponse['data'][0]['values'])) {
                $result['previous_paid'] = collect($previousPaidResponse['data'][0]['values'])->sum('value');
            }

            /* Calculate organic reach (total - paid)*/
            $result['current_organic'] = max(0, $result['current_total'] - $result['current_paid']);
            $result['previous_organic'] = max(0, $result['previous_total'] - $result['previous_paid']);

            /* Calculate percentage change */
            $prev = $result['previous_total'];
            $curr = $result['current_total'];
            $result['percent_change'] = $prev > 0
                ? round((($curr - $prev) / $prev) * 100, 2)
                : ($curr > 0 ? 100 : 0);

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook reach: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Fetch Facebook Followers Data
     */
    private function fetchFacebookFollowers($pageId, $pageToken, $since, $until, $previousSince, $previousUntil)
    {
        $result = [
            'new_current' => 0,
            'new_previous' => 0,
            'lost_current' => 0,
            'lost_previous' => 0,
            'total_current' => 0,
            'api_description' => '',
            'new_percent_change' => 0,
            'lost_percent_change' => 0
        ];

        try {
            // Get current total followers (Days metric)
            $currentFansResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_follows',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentFansResponse['data'][0]['values'])) {
                $result['api_description'] = $currentFansResponse['data'][0]['description'] ?? '';
                $values = $currentFansResponse['data'][0]['values'];
                $latest = end($values);
                $result['total_current'] = $latest['value'] ?? 0;
            }

            // CURRENT: New followers
            $currentAddsResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_fan_adds',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentAddsResponse['data'][0]['values'])) {
                $result['new_current'] = collect($currentAddsResponse['data'][0]['values'])->sum('value');
            }

            // CURRENT: Lost followers
            $currentRemovesResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_fan_removes',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentRemovesResponse['data'][0]['values'])) {
                $result['lost_current'] = collect($currentRemovesResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: New followers
            $previousAddsResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_fan_adds',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousAddsResponse['data'][0]['values'])) {
                $result['new_previous'] = collect($previousAddsResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: Lost followers
            $previousRemovesResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_fan_removes',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousRemovesResponse['data'][0]['values'])) {
                $result['lost_previous'] = collect($previousRemovesResponse['data'][0]['values'])->sum('value');
            }

            // Calculate percentages
            $result['new_percent_change'] = $this->calculatePercentageChange(
                $result['new_current'],
                $result['new_previous']
            );
            
            $result['lost_percent_change'] = $this->calculatePercentageChange(
                $result['lost_current'],
                $result['lost_previous']
            );

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook followers: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Fetch Facebook Engagement Data
     */
    private function fetchFacebookEngagement($pageId, $pageToken, $since, $until, $previousSince, $previousUntil)
    {
        $result = [
            'current' => 0,
            'previous' => 0,
            'post_engagements_current' => 0,
            'post_engagements_previous' => 0,
            'api_description' => '',
            'percent_change' => 0,
            'post_engagements_percent_change' => 0
        ];

        try {
            // CURRENT: Page engaged users
            $currentEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_engaged_users',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentEngagementResponse['data'][0]['values'])) {
                $result['api_description'] = $currentEngagementResponse['data'][0]['description'] ?? '';
                $result['current'] = collect($currentEngagementResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: Page engaged users
            $previousEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_engaged_users',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousEngagementResponse['data'][0]['values'])) {
                $result['previous'] = collect($previousEngagementResponse['data'][0]['values'])->sum('value');
            }

            // CURRENT: Post engagements
            $currentPostEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_post_engagements',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentPostEngagementResponse['data'][0]['values'])) {
                $result['post_engagements_current'] = collect($currentPostEngagementResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: Post engagements
            $previousPostEngagementResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_post_engagements',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousPostEngagementResponse['data'][0]['values'])) {
                $result['post_engagements_previous'] = collect($previousPostEngagementResponse['data'][0]['values'])->sum('value');
            }

            // Calculate percentages
            $result['percent_change'] = $this->calculatePercentageChange(
                $result['current'],
                $result['previous']
            );
            
            $result['post_engagements_percent_change'] = $this->calculatePercentageChange(
                $result['post_engagements_current'],
                $result['post_engagements_previous']
            );

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook engagement: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Fetch Facebook Page Views
     */
    private function fetchFacebookPageViews($pageId, $pageToken, $since, $until, $previousSince, $previousUntil)
    {
        $result = [
            'current' => 0,
            'previous' => 0,
            'api_description' => '',
            'percent_change' => 0
        ];

        try {
            // CURRENT: Page views
            $currentResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_views_total',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentResponse['data'][0]['values'])) {
                $result['api_description'] = $currentResponse['data'][0]['description'] ?? '';
                $result['current'] = collect($currentResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: Page views
            $previousResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_views_total',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousResponse['data'][0]['values'])) {
                $result['previous'] = collect($previousResponse['data'][0]['values'])->sum('value');
            }

            // Calculate percentage change
            $result['percent_change'] = $this->calculatePercentageChange(
                $result['current'],
                $result['previous']
            );

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook page views: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Fetch Facebook CTA Clicks
     */
    private function fetchFacebookCTAClicks($pageId, $pageToken, $since, $until, $previousSince, $previousUntil)
    {
        $result = [
            'current' => 0,
            'previous' => 0,
            'api_description' => '',
            'percent_change' => 0
        ];

        try {
            // CURRENT: CTA clicks
            $currentResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_total_actions',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentResponse['data'][0]['values'])) {
                $result['api_description'] = $currentResponse['data'][0]['description'] ?? '';
                $result['current'] = collect($currentResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: CTA clicks
            $previousResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_total_actions',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousResponse['data'][0]['values'])) {
                $result['previous'] = collect($previousResponse['data'][0]['values'])->sum('value');
            }

            // Calculate percentage change
            $result['percent_change'] = $this->calculatePercentageChange(
                $result['current'],
                $result['previous']
            );

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook CTA clicks: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Fetch Facebook Reactions Data
     */
    private function fetchReactionsData($pageId, $pageToken, $startDate, $endDate)
    {
        $result = [
            'reactions' => [
                'like_current' => 0,
                'like_previous' => 0,
                'love_current' => 0,
                'love_previous' => 0,
                'wow_current' => 0,
                'wow_previous' => 0,
                'haha_current' => 0,
                'haha_previous' => 0,
                'sorry_current' => 0,
                'sorry_previous' => 0,
                'anger_current' => 0,
                'anger_previous' => 0,
                'total_current' => 0,
                'total_previous' => 0,
                'api_description' => '',
                'percent_change' => 0
            ]
        ];

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
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

            // Define reaction metrics
            $reactionMetrics = [
                'like' => 'page_actions_post_reactions_like_total',
                'love' => 'page_actions_post_reactions_love_total',
                'wow' => 'page_actions_post_reactions_wow_total',
                'haha' => 'page_actions_post_reactions_haha_total',
                'sorry' => 'page_actions_post_reactions_sorry_total',
                'anger' => 'page_actions_post_reactions_anger_total'
            ];

            foreach ($reactionMetrics as $key => $metric) {
                // Current period
                $currentResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                    'metric' => $metric,
                    'period' => 'day',
                    'since' => $since,
                    'until' => $until,
                    'access_token' => $pageToken,
                ])->json();
                
                if (isset($currentResponse['data'][0]['values'])) {
                    $result['reactions'][$key . '_current'] = collect($currentResponse['data'][0]['values'])->sum('value');
                    if (empty($result['reactions']['api_description'])) {
                        $result['reactions']['api_description'] = $currentResponse['data'][0]['description'] ?? '';
                    }
                    $result['reactions']['total_current'] += $result['reactions'][$key . '_current'];
                }

                // Previous period
                $previousResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                    'metric' => $metric,
                    'period' => 'day',
                    'since' => $previousSince,
                    'until' => $previousUntil,
                    'access_token' => $pageToken,
                ])->json();
                
                if (isset($previousResponse['data'][0]['values'])) {
                    $result['reactions'][$key . '_previous'] = collect($previousResponse['data'][0]['values'])->sum('value');
                    $result['reactions']['total_previous'] += $result['reactions'][$key . '_previous'];
                }
            }

            // Calculate total percentage change
            $result['reactions']['percent_change'] = $this->calculatePercentageChange(
                $result['reactions']['total_current'],
                $result['reactions']['total_previous']
            );

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook reactions: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Fetch Facebook Video Data
     */
    private function fetchVideoData($pageId, $pageToken, $startDate, $endDate)
    {
        $result = [
            'video_views' => [
                'current' => 0,
                'previous' => 0,
                'complete_current' => 0,
                'complete_previous' => 0,
                'api_description' => '',
                'percent_change' => 0,
                'complete_percent_change' => 0
            ]
        ];

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
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

            // CURRENT: Video views (3+ seconds)
            $currentViewsResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_video_views',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentViewsResponse['data'][0]['values'])) {
                $result['video_views']['api_description'] = $currentViewsResponse['data'][0]['description'] ?? '';
                $result['video_views']['current'] = collect($currentViewsResponse['data'][0]['values'])->sum('value');
            }

            // CURRENT: Complete video views (30+ seconds)
            $currentCompleteResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_video_complete_views_30s',
                'period' => 'day',
                'since' => $since,
                'until' => $until,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($currentCompleteResponse['data'][0]['values'])) {
                $result['video_views']['complete_current'] = collect($currentCompleteResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: Video views (3+ seconds)
            $previousViewsResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_video_views',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousViewsResponse['data'][0]['values'])) {
                $result['video_views']['previous'] = collect($previousViewsResponse['data'][0]['values'])->sum('value');
            }

            // PREVIOUS: Complete video views (30+ seconds)
            $previousCompleteResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_video_complete_views_30s',
                'period' => 'day',
                'since' => $previousSince,
                'until' => $previousUntil,
                'access_token' => $pageToken,
            ])->json();
            
            if (isset($previousCompleteResponse['data'][0]['values'])) {
                $result['video_views']['complete_previous'] = collect($previousCompleteResponse['data'][0]['values'])->sum('value');
            }

            // Calculate percentage changes
            $result['video_views']['percent_change'] = $this->calculatePercentageChange(
                $result['video_views']['current'],
                $result['video_views']['previous']
            );
            
            $result['video_views']['complete_percent_change'] = $this->calculatePercentageChange(
                $result['video_views']['complete_current'],
                $result['video_views']['complete_previous']
            );

            return $result;
            
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook video data: ' . $e->getMessage());
            return $result;
        }
    }

    /**
     * Render Dashboard HTML
     */
    private function renderDashboardHtml($pageId, $performanceData)
    {
        if (isset($performanceData['error'])) {
            return "<div class='alert alert-danger'>Error: {$performanceData['error']}</div>";
        }
        
        $dateRange = $performanceData['date_range']['display'] ?? '';
        
        $reach = $performanceData['reach'] ?? [];
        $followers = $performanceData['followers'] ?? [];
        $engagement = $performanceData['engagement'] ?? [];
        $pageViews = $performanceData['page_views'] ?? [];
        $ctaClicks = $performanceData['cta_clicks'] ?? [];
        $reactions = $performanceData['reactions'] ?? [];
        $videoViews = $performanceData['video_views'] ?? [];

        $html = '
        <div class="card">
            <div class="card-header text-white">
                <h4 class="card-title mb-0">
                    Facebook Page Performance
                    (<span class="text-info">' . $dateRange . '</span>)
                </h4>
            </div>
            <div class="card-body">
                <div class="reach-section mb-4">
                    <div class="row g-4">
                        <!-- Reach Section -->
                        <div class="col-md-6 reach col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h4>
                                        Reach
                                        <i class="bx bx-question-mark text-primary" 
                                           style="cursor: pointer; font-size: 18px;" 
                                           data-bs-toggle="tooltip" data-bs-placement="top" 
                                           data-bs-custom-class="success-tooltip"
                                           data-bs-title="' . e($reach['api_description'] ?? '') . '">
                                        </i>
                                    </h4>
                                </div>
                                <div class="metric-body">
                                    <table class="table table-sm mb-2 align-middle text-center">
                                        <tr>
                                            <th><h3 class="mb-0">' . $this->formatNumber($reach['previous_total'] ?? 0) . '</h3></th>
                                            <th><h3 class="mb-0">' . $this->formatNumber($reach['current_total'] ?? 0) . '</h3></th>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Previous Period</td>
                                            <td class="bg-black text-light">Current Period</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="' . ($reach['percent_change'] > 0 ? 'positive' : ($reach['percent_change'] < 0 ? 'negative' : 'neutral')) . '">
                                                <h4 class="mb-0">' . ($reach['percent_change'] > 0 ? "▲ +" : ($reach['percent_change'] < 0 ? "▼ " : "➖ ")) . abs($reach['percent_change']) . '%</h4>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Paid Reach</td>
                                            <td class="bg-black text-light">Organic Reach</td>
                                        </tr>
                                        <tr>
                                            <td><h4 class="mb-0">' . $this->formatNumber($reach['current_paid'] ?? 0) . '</h4></td>
                                            <td><h4 class="mb-0">' . $this->formatNumber($reach['current_organic'] ?? 0) . '</h4></td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="bg-black text-light">Change</td>
                                        </tr>
                                        <tr>
                                            <td class="' . $this->getChangeClass($this->calculatePercentageChange($reach['current_paid'] ?? 0, $reach['previous_paid'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reach['current_paid'] ?? 0, $reach['previous_paid'] ?? 0)) . '
                                            </td>
                                            <td class="' . $this->getChangeClass($this->calculatePercentageChange($reach['current_organic'] ?? 0, $reach['previous_organic'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reach['current_organic'] ?? 0, $reach['previous_organic'] ?? 0)) . '
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Followers Section -->
                        <div class="col-md-6 followers col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h4>
                                        Followers
                                        <i class="bx bx-question-mark text-primary" 
                                           style="cursor: pointer; font-size: 18px;" 
                                           data-bs-toggle="tooltip" data-bs-placement="top" 
                                           data-bs-custom-class="success-tooltip"
                                           data-bs-title="' . e($followers['api_description'] ?? '') . '">
                                        </i>
                                    </h4>
                                </div>
                                <div class="metric-body">
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                            <div class="metric-card">
                                                <div class="metric-header">
                                                    <h5>New Followers</h5>
                                                </div>
                                                <div class="metric-body">
                                                    <table class="table table-sm mb-2 align-middle text-center">
                                                        <tr>
                                                            <th><h3 class="mb-0">' . $this->formatNumber($followers['new_previous'] ?? 0) . '</h3></th>
                                                            <th><h3 class="mb-0">' . $this->formatNumber($followers['new_current'] ?? 0) . '</h3></th>
                                                        </tr>
                                                        <tr>
                                                            <td class="bg-black text-light">Previous</td>
                                                            <td class="bg-black text-light">Current</td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="2" class="' . ($followers['new_percent_change'] > 0 ? 'positive' : ($followers['new_percent_change'] < 0 ? 'negative' : 'neutral')) . '">
                                                                <h4 class="mb-0">' . ($followers['new_percent_change'] > 0 ? "▲ +" : ($followers['new_percent_change'] < 0 ? "▼ " : "➖ ")) . abs($followers['new_percent_change']) . '%</h4>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 col-sm-6 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                            <div class="metric-card">
                                                <div class="metric-header">
                                                    <h5>Total Followers</h5>
                                                </div>
                                                <div class="metric-body">
                                                    <div class="text-center">
                                                        <h1 class="text-primary">' . $this->formatNumber($followers['total_current'] ?? 0) . '</h1>
                                                        <small class="text-muted">Current Total</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Engagement & Post Engagements -->
                <div class="row g-4">
                    <div class="col-md-6 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                    Engagement
                                    <i class="bx bx-question-mark text-primary" 
                                       style="cursor: pointer; font-size: 18px;" 
                                       data-bs-toggle="tooltip" data-bs-placement="top" 
                                       data-bs-custom-class="success-tooltip"
                                       data-bs-title="' . e($engagement['api_description'] ?? '') . '">
                                    </i>
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <th><h3 class="mb-0">' . $this->formatNumber($engagement['previous'] ?? 0) . '</h3></th>
                                        <th><h3 class="mb-0">' . $this->formatNumber($engagement['current'] ?? 0) . '</h3></th>
                                    </tr>
                                    <tr>
                                        <td class="bg-black text-light">Previous</td>
                                        <td class="bg-black text-light">Current</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="' . ($engagement['percent_change'] > 0 ? 'positive' : ($engagement['percent_change'] < 0 ? 'negative' : 'neutral')) . '">
                                            <h4 class="mb-0">' . ($engagement['percent_change'] > 0 ? "▲ +" : ($engagement['percent_change'] < 0 ? "▼ " : "➖ ")) . abs($engagement['percent_change']) . '%</h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                    Post Engagements
                                    <i class="bx bx-question-mark text-primary" 
                                       style="cursor: pointer; font-size: 18px;" 
                                       data-bs-toggle="tooltip" data-bs-placement="top" 
                                       data-bs-custom-class="success-tooltip"
                                       data-bs-title="Total engagements on posts">
                                    </i>
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <th><h3 class="mb-0">' . $this->formatNumber($engagement['post_engagements_previous'] ?? 0) . '</h3></th>
                                        <th><h3 class="mb-0">' . $this->formatNumber($engagement['post_engagements_current'] ?? 0) . '</h3></th>
                                    </tr>
                                    <tr>
                                        <td class="bg-black text-light">Previous</td>
                                        <td class="bg-black text-light">Current</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="' . ($engagement['post_engagements_percent_change'] > 0 ? 'positive' : ($engagement['post_engagements_percent_change'] < 0 ? 'negative' : 'neutral')) . '">
                                            <h4 class="mb-0">' . ($engagement['post_engagements_percent_change'] > 0 ? "▲ +" : ($engagement['post_engagements_percent_change'] < 0 ? "▼ " : "➖ ")) . abs($engagement['post_engagements_percent_change']) . '%</h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reactions Breakdown -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                    Reactions Breakdown
                                    <i class="bx bx-question-mark text-primary" 
                                       style="cursor: pointer; font-size: 18px;" 
                                       data-bs-toggle="tooltip" data-bs-placement="top" 
                                       data-bs-custom-class="success-tooltip"
                                       data-bs-title="' . e($reactions['api_description'] ?? '') . '">
                                    </i>
                                </h4>
                            </div>
                            <div class="metric-body">
                                <div class="row text-center">
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <div class="reaction-item">
                                            <div class="reaction-icon" style="font-size: 24px;">👍</div>
                                            <div class="reaction-label">Like</div>
                                            <div class="reaction-count">' . $this->formatNumber($reactions['like_current'] ?? 0) . '</div>
                                            <div class="reaction-change ' . $this->getChangeClass($this->calculatePercentageChange($reactions['like_current'] ?? 0, $reactions['like_previous'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reactions['like_current'] ?? 0, $reactions['like_previous'] ?? 0)) . '
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <div class="reaction-item">
                                            <div class="reaction-icon" style="font-size: 24px;">❤️</div>
                                            <div class="reaction-label">Love</div>
                                            <div class="reaction-count">' . $this->formatNumber($reactions['love_current'] ?? 0) . '</div>
                                            <div class="reaction-change ' . $this->getChangeClass($this->calculatePercentageChange($reactions['love_current'] ?? 0, $reactions['love_previous'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reactions['love_current'] ?? 0, $reactions['love_previous'] ?? 0)) . '
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <div class="reaction-item">
                                            <div class="reaction-icon" style="font-size: 24px;">😮</div>
                                            <div class="reaction-label">Wow</div>
                                            <div class="reaction-count">' . $this->formatNumber($reactions['wow_current'] ?? 0) . '</div>
                                            <div class="reaction-change ' . $this->getChangeClass($this->calculatePercentageChange($reactions['wow_current'] ?? 0, $reactions['wow_previous'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reactions['wow_current'] ?? 0, $reactions['wow_previous'] ?? 0)) . '
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <div class="reaction-item">
                                            <div class="reaction-icon" style="font-size: 24px;">😂</div>
                                            <div class="reaction-label">Haha</div>
                                            <div class="reaction-count">' . $this->formatNumber($reactions['haha_current'] ?? 0) . '</div>
                                            <div class="reaction-change ' . $this->getChangeClass($this->calculatePercentageChange($reactions['haha_current'] ?? 0, $reactions['haha_previous'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reactions['haha_current'] ?? 0, $reactions['haha_previous'] ?? 0)) . '
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <div class="reaction-item">
                                            <div class="reaction-icon" style="font-size: 24px;">😢</div>
                                            <div class="reaction-label">Sad</div>
                                            <div class="reaction-count">' . $this->formatNumber($reactions['sorry_current'] ?? 0) . '</div>
                                            <div class="reaction-change ' . $this->getChangeClass($this->calculatePercentageChange($reactions['sorry_current'] ?? 0, $reactions['sorry_previous'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reactions['sorry_current'] ?? 0, $reactions['sorry_previous'] ?? 0)) . '
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 col-sm-4 mb-3">
                                        <div class="reaction-item">
                                            <div class="reaction-icon" style="font-size: 24px;">😠</div>
                                            <div class="reaction-label">Angry</div>
                                            <div class="reaction-count">' . $this->formatNumber($reactions['anger_current'] ?? 0) . '</div>
                                            <div class="reaction-change ' . $this->getChangeClass($this->calculatePercentageChange($reactions['anger_current'] ?? 0, $reactions['anger_previous'] ?? 0)) . '">
                                                ' . $this->formatChange($this->calculatePercentageChange($reactions['anger_current'] ?? 0, $reactions['anger_previous'] ?? 0)) . '
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <h5>Total Reactions: ' . $this->formatNumber($reactions['total_current'] ?? 0) . ' 
                                        <span class="' . $this->getChangeClass($reactions['percent_change'] ?? 0) . '">
                                            ' . $this->formatChange($reactions['percent_change'] ?? 0) . '
                                        </span>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Video Views, Page Views & CTA -->
                <div class="row mt-4">
                    <div class="col-md-4 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                    Video Views
                                    <i class="bx bx-question-mark text-primary" 
                                       style="cursor: pointer; font-size: 18px;" 
                                       data-bs-toggle="tooltip" data-bs-placement="top" 
                                       data-bs-custom-class="success-tooltip"
                                       data-bs-title="' . e($videoViews['api_description'] ?? '') . '">
                                    </i>
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <td class="bg-black text-light">3+ Second Views</td>
                                        <td class="bg-black text-light">30+ Second Views</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <h4 class="mb-0">' . $this->formatNumber($videoViews['current'] ?? 0) . '</h4>
                                            <small class="' . $this->getChangeClass($videoViews['percent_change'] ?? 0) . '">
                                                ' . $this->formatChange($videoViews['percent_change'] ?? 0) . '
                                            </small>
                                        </td>
                                        <td>
                                            <h4 class="mb-0">' . $this->formatNumber($videoViews['complete_current'] ?? 0) . '</h4>
                                            <small class="' . $this->getChangeClass($videoViews['complete_percent_change'] ?? 0) . '">
                                                ' . $this->formatChange($videoViews['complete_percent_change'] ?? 0) . '
                                            </small>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                    Page Views
                                    <i class="bx bx-question-mark text-primary" 
                                       style="cursor: pointer; font-size: 18px;" 
                                       data-bs-toggle="tooltip" data-bs-placement="top" 
                                       data-bs-custom-class="success-tooltip"
                                       data-bs-title="' . e($pageViews['api_description'] ?? '') . '">
                                    </i>
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <th><h3 class="mb-0">' . $this->formatNumber($pageViews['previous'] ?? 0) . '</h3></th>
                                        <th><h3 class="mb-0">' . $this->formatNumber($pageViews['current'] ?? 0) . '</h3></th>
                                    </tr>
                                    <tr>
                                        <td class="bg-black text-light">Previous</td>
                                        <td class="bg-black text-light">Current</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="' . ($pageViews['percent_change'] > 0 ? 'positive' : ($pageViews['percent_change'] < 0 ? 'negative' : 'neutral')) . '">
                                            <h4 class="mb-0">' . ($pageViews['percent_change'] > 0 ? "▲ +" : ($pageViews['percent_change'] < 0 ? "▼ " : "➖ ")) . abs($pageViews['percent_change']) . '%</h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-header">
                                <h4>
                                    CTA Clicks
                                    <i class="bx bx-question-mark text-primary" 
                                       style="cursor: pointer; font-size: 18px;" 
                                       data-bs-toggle="tooltip" data-bs-placement="top" 
                                       data-bs-custom-class="success-tooltip"
                                       data-bs-title="' . e($ctaClicks['api_description'] ?? '') . '">
                                    </i>
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tr>
                                        <th><h3 class="mb-0">' . $this->formatNumber($ctaClicks['previous'] ?? 0) . '</h3></th>
                                        <th><h3 class="mb-0">' . $this->formatNumber($ctaClicks['current'] ?? 0) . '</h3></th>
                                    </tr>
                                    <tr>
                                        <td class="bg-black text-light">Previous</td>
                                        <td class="bg-black text-light">Current</td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" class="' . ($ctaClicks['percent_change'] > 0 ? 'positive' : ($ctaClicks['percent_change'] < 0 ? 'negative' : 'neutral')) . '">
                                            <h4 class="mb-0">' . ($ctaClicks['percent_change'] > 0 ? "▲ +" : ($ctaClicks['percent_change'] < 0 ? "▼ " : "➖ ")) . abs($ctaClicks['percent_change']) . '%</h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';
        
        return $html;
    }

    /**
     * Helper Methods
     */
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 2);
    }

    private function formatNumber($number)
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    private function formatChange($change)
    {
        if ($change > 0) {
            return '▲ +' . abs($change) . '%';
        } elseif ($change < 0) {
            return '▼ ' . abs($change) . '%';
        }
        return '0%';
    }

    private function getChangeClass($change)
    {
        if ($change > 0) {
            return 'text-success';
        } elseif ($change < 0) {
            return 'text-danger';
        }
        return 'text-muted';
    }

    /**
     * Additional Methods (similar to Instagram controller)
     */
    public function getAudienceTopLocations(Request $request, $pageId)
    {
        // Similar to Instagram method but for Facebook
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();
                
            if (!$mainAccount) {
                return response()->json(['success' => false, 'message' => 'Facebook account not connected.'], 400);
            }
            
            $userToken = SocialTokenHelper::getFacebookToken($mainAccount);
            $pageToken = SocialTokenHelper::getFacebookPageToken($userToken, $pageId);
            
            $timeframe = $request->get('timeframe', 'this_month');
            
            $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$pageId}/insights", [
                'metric' => 'page_fans_city',
                'period' => 'lifetime',
                'access_token' => $pageToken,
            ])->json();
            
            // Process response similar to Instagram method
            // ...
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function fetchFacebookPosts(Request $request, $pageId)
    {
        // Fetch Facebook posts similar to Instagram
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['success' => false, 'error' => 'Facebook account not connected']);
            }

            $userToken = SocialTokenHelper::getFacebookToken($mainAccount);
            $pageToken = SocialTokenHelper::getFacebookPageToken($userToken, $pageId);
            
            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            $since = $start->timestamp;
            $until = $end->timestamp;
            
            // Fetch Facebook posts
            $postsResponse = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$pageId}/feed", [
                'fields' => 'id,message,created_time,full_picture,permalink_url,attachments{media,title,description,type},shares,reactions.summary(true),comments.summary(true)',
                'since' => $since,
                'until' => $until,
                'limit' => $request->get('limit', 12),
                'access_token' => $pageToken,
            ])->json();
            
            // Process and return posts
            // ...
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // Add other methods as needed (post insights, charts, etc.)
}