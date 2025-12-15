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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Cache;

class InstagramPdfController extends Controller
{
    public function generatePdfReport(Request $request, $id)
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

            // Get date range from request or default
            $startDate = $request->get('start_date', now()->subDays(28)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->subDays(1)->format('Y-m-d'));

            // Create cache key
            $cacheKey = "instagram_pdf_{$id}_{$startDate}_{$endDate}_{$user->id}";
            
            // Check cache first
            if (Cache::has($cacheKey)) {
                $cachedData = Cache::get($cacheKey);
                return $this->generatePdfFromData($cachedData, $id);
            }

            // Fetch all data using batch calls
            $allData = $this->fetchAllDataInBatch($id, $token, $startDate, $endDate);
            
            if (isset($allData['instagram']['error'])) {
                return response()->json(['error' => 'Failed to fetch Instagram profile'], 500);
            }

            // Add additional data
            $allData['startDate'] = $startDate;
            $allData['endDate'] = $endDate;
            $allData['dateRange'] = Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y');

            // Cache the data for 10 minutes
            Cache::put($cacheKey, $allData, 600);

            // Generate PDF
            return $this->generatePdfFromData($allData, $id);

        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    private function fetchAllDataInBatch($accountId, $token, $startDate, $endDate)
    {
        $data = [];
        
        // Calculate date ranges
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

        // BATCH 1: Profile data
        $data['instagram'] = $this->fetchInstagramProfile($accountId, $token);

        // BATCH 2: Profile insights (current period) - Combine multiple metrics
        $currentInsights = $this->fetchBatchInsights($accountId, $token, $since, $until, [
            'profile_views',
            'profile_links_taps',
            'accounts_engaged',
            'total_interactions',
            'follows_and_unfollows',
            'views',
            'likes,comments,saves,shares,reposts'
        ]);
        
        // BATCH 3: Previous period insights
        $previousInsights = $this->fetchBatchInsights($accountId, $token, $previousSince, $previousUntil, [
            'profile_views',
            'profile_links_taps',
            'accounts_engaged,total_interactions',
            'follows_and_unfollows',
            'views',
            'likes,comments,saves,shares,reposts'
        ]);

        // BATCH 4: Reach data with breakdowns
        $reachData = $this->fetchReachData($accountId, $token, $since, $until, $previousSince, $previousUntil);
        $data['locationsData'] = $reachData;

        // BATCH 5: Media data
        $mediaData = $this->fetchMediaData($accountId, $token, $since, $until, $previousSince, $previousUntil);
        $data['audienceAgeData'] = $mediaData;

        // BATCH 6: Audience demographics
        $timeframe = $this->getTimeframe($startDate, $endDate);
        $demographics = $this->fetchAudienceDemographics($accountId, $token, $timeframe);
        $data['topLocationsData'] = $demographics['locations'] ?? ['success' => true, 'locations' => []];
        $data['ageGenderData'] = $demographics['ageGender'] ?? ['success' => true, 'labels' => [], 'male' => [], 'female' => []];

        // BATCH 7: Time series data
        $timeSeriesData = $this->fetchTimeSeriesData($accountId, $token, $since, $until);
        $data['reachDaysData'] = $timeSeriesData['reach'] ?? ['success' => true, 'data' => []];
        $data['viewsDaysData'] = $timeSeriesData['views'] ?? ['success' => true, 'categories' => [], 'values' => []];

        // BATCH 8: Posts data
        $data['postsData'] = $this->fetchInstagramPosts($accountId, $token, $startDate, $endDate, 12);

        // Combine current and previous insights for performance data
        $data['performanceData'] = $this->combineInsightsData($currentInsights, $previousInsights);

        return $data;
    }

    private function fetchInstagramProfile($accountId, $token)
    {
        return Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$accountId}", [
            'fields' => 'name,username,biography,followers_count,follows_count,media_count,profile_picture_url',
            'access_token' => $token,
        ])->json();
    }

    private function fetchBatchInsights($accountId, $token, $since, $until, $metrics)
    {
        $results = [];
        
        // Process metrics in chunks to avoid URL length limits
        $chunks = array_chunk($metrics, 3); // 3 metrics per request
        
        foreach ($chunks as $chunk) {
            $metricString = implode(',', $chunk);
            
            $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
                'metric' => $metricString,
                'period' => 'day',
                'metric_type' => 'total_value',
                'since' => $since,
                'until' => $until,
                'access_token' => $token,
            ])->json();
            
            if (isset($response['data'])) {
                foreach ($response['data'] as $metric) {
                    $name = $metric['name'] ?? '';
                    $results[$name] = $metric;
                }
            }
        }
        
        return $results;
    }

    private function fetchReachData($accountId, $token, $since, $until, $previousSince, $previousUntil)
    {
        $result = [
            'status' => 'success',
            'reach' => [
                'current_month' => [
                    'total' => 0,
                    'paid' => 0,
                    'organic' => 0,
                    'followers' => 0,
                    'non_followers' => 0,
                ],
                'previous_month' => [
                    'total' => 0,
                    'paid' => 0,
                    'organic' => 0,
                    'followers' => 0,
                    'non_followers' => 0,
                ]
            ]
        ];

        // Fetch current reach
        $currentResponse = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'reach',
            'period' => 'day',
            'breakdown' => 'media_product_type,follow_type',
            'metric_type' => 'total_value',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        if (isset($currentResponse['data'][0]['total_value']['breakdowns'][0]['results'])) {
            $result['reach']['api_description'] = $currentResponse['data'][0]['description'] ?? 'No description available';
            foreach ($currentResponse['data'][0]['total_value']['breakdowns'][0]['results'] as $r) {
                $mediaType = $r['dimension_values'][0] ?? '';
                $followType = $r['dimension_values'][1] ?? '';
                $value = $r['value'] ?? 0;
                
                if ($mediaType === 'AD') {
                    $result['reach']['current_month']['paid'] += $value;
                } else {
                    $result['reach']['current_month']['organic'] += $value;
                }
                
                if ($followType === 'FOLLOWER') {
                    $result['reach']['current_month']['followers'] += $value;
                } elseif ($followType === 'NON_FOLLOWER') {
                    $result['reach']['current_month']['non_followers'] += $value;
                }

                $result['reach']['current_month']['total'] += $value;
            }
        }

        // Fetch previous reach
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
                    $result['reach']['previous_month']['paid'] += $value;
                } else {
                    $result['reach']['previous_month']['organic'] += $value;
                }
                
                if ($followType === 'FOLLOWER') {
                    $result['reach']['previous_month']['followers'] += $value;
                } elseif ($followType === 'NON_FOLLOWER') {
                    $result['reach']['previous_month']['non_followers'] += $value;
                }

                $result['reach']['previous_month']['total'] += $value;
            }
        }

        // Calculate percentages
        $currTotal = $result['reach']['current_month']['total'];
        $prevTotal = $result['reach']['previous_month']['total'];
        
        $result['reach']['percent_change'] = $prevTotal > 0
            ? round((($currTotal - $prevTotal) / $prevTotal) * 100, 2)
            : 0;
            
        $total = max($currTotal, 1);
        $result['reach']['current_month']['paid_percent'] = round(($result['reach']['current_month']['paid'] / $total) * 100, 2);
        $result['reach']['current_month']['organic_percent'] = round(($result['reach']['current_month']['organic'] / $total) * 100, 2);

        return $result;
    }

    private function fetchMediaData($accountId, $token, $since, $until, $previousSince, $previousUntil)
    {
        $data = [];
        
        // Fetch total interactions by media type (current)
        $currentInteractions = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'total_interactions',
            'period' => 'day',
            'metric_type' => 'total_value',
            'breakdown' => 'media_product_type',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        // Fetch total interactions by media type (previous)
        $previousInteractions = Http::timeout(20)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'total_interactions',
            'period' => 'day',
            'metric_type' => 'total_value',
            'breakdown' => 'media_product_type',
            'since' => $previousSince,
            'until' => $previousUntil,
            'access_token' => $token,
        ])->json();

        // Process interactions
        $mediaTypes = ['POST' => 0, 'AD' => 0, 'REEL' => 0, 'STORY' => 0];
        
        // Current period
        $currentByType = $mediaTypes;
        if (isset($currentInteractions['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($currentInteractions['data'][0]['total_value']['breakdowns'][0]['results'] as $item) {
                $type = strtoupper($item['dimension_values'][0] ?? '');
                $value = (int) ($item['value'] ?? 0);
                if (isset($currentByType[$type])) {
                    $currentByType[$type] += $value;
                }
            }
        }

        // Previous period
        $previousByType = $mediaTypes;
        if (isset($previousInteractions['data'][0]['total_value']['breakdowns'][0]['results'])) {
            foreach ($previousInteractions['data'][0]['total_value']['breakdowns'][0]['results'] as $item) {
                $type = strtoupper($item['dimension_values'][0] ?? '');
                $value = (int) ($item['value'] ?? 0);
                if (isset($previousByType[$type])) {
                    $previousByType[$type] += $value;
                }
            }
        }

        // Combine
        $combinedInteractions = [];
        foreach ($mediaTypes as $type => $_) {
            $prev = $previousByType[$type] ?? 0;
            $curr = $currentByType[$type] ?? 0;
            $percent = ($prev == 0)
                ? ($curr > 0 ? 100 : 0)
                : round((($curr - $prev) / $prev) * 100, 2);
            $status = $percent > 0 ? '↑' : ($percent < 0 ? '↓' : '-');

            $combinedInteractions[$type] = [
                'previous' => $prev,
                'current' => $curr,
                'percent' => $percent,
                'status' => $status,
            ];
        }

        $data['total_interactions_by_media_type'] = $combinedInteractions;

        return $data;
    }

    private function fetchAudienceDemographics($accountId, $token, $timeframe)
    {
        $result = [
            'locations' => ['success' => true, 'locations' => []],
            'ageGender' => ['success' => true, 'labels' => [], 'male' => [], 'female' => []]
        ];

        // Fetch locations
        $locationsResponse = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'city',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();

        if (isset($locationsResponse['data'][0]['total_value']['breakdowns'][0]['results'])) {
            $results = $locationsResponse['data'][0]['total_value']['breakdowns'][0]['results'];
            $total = collect($results)->sum('value');
            
            $locations = collect($results)
                ->sortByDesc('value')
                ->take(10)
                ->map(function ($item) use ($total) {
                    $cityName = $item['dimension_values'][0] ?? 'Unknown';
                    $value = $item['value'];
                    $percentage = $total ? round(($value / $total) * 100, 2) : 0;
                    
                    return [
                        'name' => $cityName,
                        'value' => $value,
                        'percentage' => $percentage
                    ];
                })->toArray();
            
            $result['locations']['locations'] = $locations;
            $result['locations']['total'] = $total;
            $result['locations']['api_description'] = $locationsResponse['data'][0]['description'] ?? '';
        }

        // Fetch age gender
        $ageGenderResponse = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'age,gender',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();

        if (isset($ageGenderResponse['data'][0]['total_value']['breakdowns'][0]['results'])) {
            $results = $ageGenderResponse['data'][0]['total_value']['breakdowns'][0]['results'];
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
            $result['ageGender']['labels'] = array_keys($ageGroups);
            $result['ageGender']['male'] = array_column($ageGroups, 'M');
            $result['ageGender']['female'] = array_column($ageGroups, 'F');
            $result['ageGender']['unknown'] = array_column($ageGroups, 'U');
            $result['ageGender']['api_description'] = $ageGenderResponse['data'][0]['description'] ?? '';
        }

        return $result;
    }

    private function fetchTimeSeriesData($accountId, $token, $since, $until)
    {
        $result = [
            'reach' => ['success' => true, 'data' => []],
            'views' => ['success' => true, 'categories' => [], 'values' => []]
        ];

        // Fetch reach time series
        $reachResponse = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'reach',
            'metric_type' => 'time_series',
            'period' => 'day',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        if (isset($reachResponse['data'][0]['values'])) {
            $chartData = collect($reachResponse['data'][0]['values'])->map(function ($item) {
                return [
                    'date' => date('Y-m-d', strtotime($item['end_time'])),
                    'value' => $item['value'],
                ];
            })->values()->toArray();
            
            $result['reach']['data'] = $chartData;
            $result['reach']['api_description'] = $reachResponse['data'][0]['description'] ?? '';
        }

        // Fetch views breakdown
        $viewsResponse = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'views',
            'metric_type' => 'total_value',
            'period' => 'day',
            'breakdown' => 'media_product_type',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        if (isset($viewsResponse['data'][0]['total_value']['breakdowns'][0]['results'])) {
            $breakdowns = $viewsResponse['data'][0]['total_value']['breakdowns'][0]['results'];
            
            foreach ($breakdowns as $item) {
                $mediaType = $item['dimension_values'][0] ?? '';
                $value = $item['value'] ?? 0;
                
                if ($value < 1) continue;
                
                switch ($mediaType) {
                    case 'POST': $label = 'Posts'; break;
                    case 'STORY': $label = 'Stories'; break;
                    case 'REEL': $label = 'Reels'; break;
                    case 'AD': $label = 'Ads'; break;
                    case 'CAROUSEL_CONTAINER': $label = 'Carousels'; break;
                    case 'IGTV': $label = 'IGTV'; break;
                    default: $label = $mediaType;
                }

                $result['views']['categories'][] = $label;
                $result['views']['values'][] = $value;
            }
            
            $result['views']['total_views'] = $viewsResponse['data'][0]['total_value']['value'] ?? 0;
            $result['views']['api_description'] = $viewsResponse['data'][0]['description'] ?? '';
        }

        return $result;
    }

    private function combineInsightsData($currentInsights, $previousInsights)
    {
        $result = [
            'profile_visits' => [
                'current_profile' => 0,
                'previous_profile' => 0,
                'percent_change' => 0,
                'api_description' => '',
            ],
            'profile_link' => [
                'current' => 0,
                'previous' => 0,
                'percent_change' => 0,
                'api_description' => '',
            ],
            'engagement' => [
                'accounts_engaged_current' => 0,
                'accounts_engaged_previous' => 0,
                'accounts_engaged_percent_change' => 0,
                'account_engaged_api_description' => '',

                'total_interactions_current' => 0,
                'total_interactions_previous' => 0,
                'total_interactions_percent_change' => 0,
                'interactions_api_description' => '',
            ]
        ];

        // Profile visits
        if (isset($currentInsights['profile_views'])) {
            $result['profile_visits']['current_profile'] = $currentInsights['profile_views']['total_value']['value'] ?? 0;
            $result['profile_visits']['api_description'] = $currentInsights['profile_views']['description'] ?? '';
        }
        if (isset($previousInsights['profile_views'])) {
            $result['profile_visits']['previous_profile'] = $previousInsights['profile_views']['total_value']['value'] ?? 0;
        }

        // Profile link clicks
        if (isset($currentInsights['profile_links_taps'])) {
            $result['profile_link']['current'] = $currentInsights['profile_links_taps']['total_value']['value'] ?? 0;
            $result['profile_link']['api_description'] = $currentInsights['profile_links_taps']['description'] ?? '';
        }
        if (isset($previousInsights['profile_links_taps'])) {
            $result['profile_link']['previous'] = $previousInsights['profile_links_taps']['total_value']['value'] ?? 0;
        }

        // Engagement
        if (isset($currentInsights['accounts_engaged'])) {
            $result['engagement']['accounts_engaged_current'] = $currentInsights['accounts_engaged']['total_value']['value'] ?? 0;
            $result['engagement']['account_engaged_api_description'] = $currentInsights['accounts_engaged']['description'] ?? '';
        }
        if (isset($previousInsights['accounts_engaged'])) {
            $result['engagement']['accounts_engaged_previous'] = $previousInsights['accounts_engaged']['total_value']['value'] ?? 0;
        }

        if (isset($currentInsights['total_interactions'])) {
            $result['engagement']['total_interactions_current'] = $currentInsights['total_interactions']['total_value']['value'] ?? 0;
            $result['engagement']['interactions_api_description'] = $currentInsights['total_interactions']['description'] ?? '';
        }
        if (isset($previousInsights['total_interactions'])) {
            $result['engagement']['total_interactions_previous'] = $previousInsights['total_interactions']['total_value']['value'] ?? 0;
        }

        // Calculate percentages
        $result['profile_visits']['percent_change'] = $this->calculatePercentageChange(
            $result['profile_visits']['previous_profile'],
            $result['profile_visits']['current_profile']
        );

        $result['profile_link']['percent_change'] = $this->calculatePercentageChange(
            $result['profile_link']['previous'],
            $result['profile_link']['current']
        );

        $result['engagement']['accounts_engaged_percent_change'] = $this->calculatePercentageChange(
            $result['engagement']['accounts_engaged_previous'],
            $result['engagement']['accounts_engaged_current']
        );

        $result['engagement']['total_interactions_percent_change'] = $this->calculatePercentageChange(
            $result['engagement']['total_interactions_previous'],
            $result['engagement']['total_interactions_current']
        );

        return $result;
    }

    private function calculatePercentageChange($previous, $current)
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 2);
        }
        return 0;
    }

    private function getTimeframe($startDate, $endDate)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $daysDiff = $start->diffInDays($end);
        
        if ($daysDiff <= 7) {
            return 'last_7d';
        } elseif ($daysDiff <= 28) {
            return 'last_28d';
        }
        return 'this_month';
    }

    private function generatePdfFromData($data, $id)
    {
        $pdf = PDF::loadView('backend.pages.instagram.pdf.report', $data);
        
        // Set PDF options
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'sans-serif');
        
        $fileName = 'instagram-report-' . $id . '-' . now()->format('Y-m-d') . '.pdf';
        return $pdf->download($fileName);
    }

    // Keep your existing fetchInstagramPosts method
    private function fetchInstagramPosts($accountId, $token, $startDate, $endDate, $limit = 12)
    {
        try {
            $start = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);
            $since = $start->timestamp;
            $until = $end->timestamp;

            $params = [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,media_product_type',
                'access_token' => $token,
                'since' => $since,
                'until' => $until,
                'limit' => $limit,
            ];

            $mediaResponse = Http::timeout(10)
                ->get("https://graph.facebook.com/v24.0/{$accountId}/media", $params)
                ->json();

            $media = $mediaResponse['data'] ?? [];
            
            // Format the media data for PDF
            $formattedMedia = collect($media)->map(function ($post) {
                return [
                    'id' => $post['id'] ?? '',
                    'caption' => $post['caption'] ?? '',
                    'media_type' => $post['media_type'] ?? '',
                    'media_url' => $post['media_url'] ?? $post['thumbnail_url'] ?? '',
                    'permalink' => $post['permalink'] ?? '',
                    'timestamp' => $post['timestamp'] ?? '',
                    'like_count' => $post['like_count'] ?? 0,
                    'comments_count' => $post['comments_count'] ?? 0,
                    'media_product_type' => $post['media_product_type'] ?? '',
                    'total_interactions' => ($post['like_count'] ?? 0) + ($post['comments_count'] ?? 0),
                ];
            })->toArray();

            return [
                'success' => true,
                'media' => $formattedMedia,
                'total_count' => count($formattedMedia),
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        } catch (\Exception $e) {
            Log::error('Instagram Posts Fetch Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'media' => [],
                'total_count' => 0,
            ];
        }
    }

    // Keep other existing methods for backward compatibility
    public function getAudienceTopLocationsApi(Request $request, $instagramId)
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
        
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));
        $timeframe = $this->getTimeframe($startDate, $endDate);
        
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

    // Add other public methods as needed...
}