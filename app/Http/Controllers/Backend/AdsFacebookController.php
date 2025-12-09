<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SocialAccount;
use App\Helpers\SocialTokenHelper;
use Exception;

class AdsFacebookController extends Controller
{
    public function mainIndex(Request $request)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return redirect()->route('facebook.index')->with('error', 'Facebook account not connected.');
            }
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $response = Http::timeout(10)->get('https://graph.facebook.com/v24.0/me/adaccounts', [
                'fields' => 'id,name,account_status,amount_spent,currency',
                'access_token' => $token,
            ]);
            if ($response->failed()) {
                $error = $response->json('error.message') ?? $response->body();
                throw new Exception('Facebook API error: ' . $error);
            }
            $adAccount = $response->json();
            Log::info('Fetched Facebook Ad Account details', ['user_id' => $user->id, 'data' => $adAccount]);
            return view('backend.pages.facebook.ads.index', compact('adAccount'));
        } catch (Exception $e) {
            Log::error('Facebook Ads Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    public function getAdsSummaryOld($adAccountId)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['success' => false, 'message' => 'Facebook account not connected.']);
            }
            $selectedColumns = request()->get('columns', 'title,status,results,cost_per_result,amount_spent,views,viewers,budget');
            $columns = explode(',', $selectedColumns);
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            /*ad  campaigns*/
            $campaignsUrl = "https://graph.facebook.com/v24.0/{$adAccountId}/campaigns";
            $fields = 'id,account_id,name';
            $campaigns = [];
            $nextUrl = $campaignsUrl;
            do {
                if ($nextUrl === $campaignsUrl) {
                    $response = Http::get($campaignsUrl, [
                        'fields' => $fields,
                        'limit' => 50,
                        'access_token' => $token,
                    ]);
                } else {
                    $response = Http::get($nextUrl);
                }

                if ($response->failed()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error fetching campaigns: ' . $response->body(),
                    ]);
                }

                $data = $response->json();
                $campaigns = array_merge($campaigns, $data['data'] ?? []);
                $nextUrl = $data['paging']['next'] ?? null;
            } while ($nextUrl);
            Log::info('Meta ad campaigns: ' . print_r($campaigns, true));
            /*ad  campaigns*/
            $url = "https://graph.facebook.com/v21.0/{$adAccountId}";
            $fields = 'id,name,account_status,amount_spent,currency,balance,spend_cap,timezone_name,owner,business,
                campaigns{name,created_time,start_time,stop_time,
                    ads{
                        insights{
                            reach,full_view_impressions,impressions,inline_link_clicks,
                            instagram_profile_visits,account_currency,account_id,spend,
                            video_avg_time_watched_actions,frequency,conversion_rate_ranking,cpc
                        }
                    }
                }';
            $response = Http::get($url, [
                'fields' => $fields,
                'access_token' => $token,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facebook API error: ' . $response->body(),
                ]);
            }

            $data = $response->json();
            Log::info('Facebook Ads API Request', ['url' => $url, 'fields' => $fields]);
            //Log::info('Facebook Ads API Response', ['response' => $response->json()]);
            Log::info('Facebook Ads API Response: ' . print_r($data, true));
            $ads = [];
            if (!empty($data['campaigns']['data'])) {
                foreach ($data['campaigns']['data'] as $campaign) {
                    if (!empty($campaign['ads']['data'])) {
                        foreach ($campaign['ads']['data'] as $ad) {
                            $insight = $ad['insights']['data'][0] ?? [];
                            $startDate = isset($campaign['start_time']) ? date('Y-m-d H:i:s', strtotime($campaign['start_time'])) : '-';
                            $endDate = isset($campaign['stop_time']) ? date('Y-m-d H:i:s', strtotime($campaign['stop_time'])) : 'Active';

                            $adData = [
                                'title' => $campaign['name'] ?? 'N/A',
                                'status' => (!empty($campaign['stop_time']) && strtotime($campaign['stop_time']) < time()) ? 'Ended' : 'Active',
                                'results' => $insight['inline_link_clicks'] ?? ($insight['instagram_profile_visits'] ?? 0),
                                'cost_per_result' => isset($insight['cpc']) ? '₹' . number_format($insight['cpc'], 2) : '-',
                                'amount_spent' => isset($insight['spend']) ? '₹' . number_format($insight['spend'], 2) : '-',
                                'views' => $insight['impressions'] ?? 0,
                                'viewers' => $insight['reach'] ?? 0,
                                'budget' => $data['currency'] ?? 'INR',
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'post_engagements' => 0, // You can add actual data here
                                'post_reactions' => 0,
                                'post_comments' => 0,
                                'post_shares' => 0,
                                'post_saves' => 0,
                                'link_clicks' => $insight['inline_link_clicks'] ?? 0,
                                'follows' => 0,
                                'ctr' => isset($insight['inline_link_clicks'], $insight['impressions']) && $insight['impressions'] > 0
                                    ? round(($insight['inline_link_clicks'] / $insight['impressions']) * 100, 2) . '%'
                                    : '0%',
                                '3_second_video_plays' => 0,
                                'video_avg_play_time' => $insight['video_avg_time_watched_actions'][0]['value'] ?? 0,
                                'thruplays' => 0,
                            ];
                            $filteredAd = [];
                            foreach ($columns as $column) {
                                if (isset($adData[$column])) {
                                    $filteredAd[$column] = $adData[$column];
                                }
                            }

                            $ads[] = $filteredAd;
                        }
                    }
                }
            }

            $html = view('backend.pages.facebook.ads.partials.ads-table', compact('ads', 'columns', 'campaigns'))->render();

            return response()->json(['success' => true, 'html' => $html]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function getAdsSummaryOld_9_12_2025($adAccountId)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();
            if (!$mainAccount) {
                return response()->json(['success' => false, 'message' => 'Facebook account not connected.']);
            }
            $dateRange = request()->get('date_range', null);
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            if ($dateRange) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $timeRange = [
                        'since' => $dates[0],
                        'until' => $dates[1],
                    ];
                }
            }
            if (empty($timeRange ?? [])) {
                $timeRange = [
                    'since' => date('Y-m-d', strtotime('-28 days')),
                    'until' => date('Y-m-d', strtotime('-1 day'))
                ];
            }
            /* Fetch Campaigns */
            $campaignsUrl = "https://graph.facebook.com/v24.0/{$adAccountId}/campaigns";
            $campaignFields = 'id,account_id,name';
            $campaigns = [];
            $campaignParams = [
                'fields' => $campaignFields,
                'limit' => 50,
                'access_token' => $token,
            ];

            $nextUrl = $campaignsUrl;
            $isFirstRequest = true;

            do {
                if ($isFirstRequest) {
                    $response = Http::get($campaignsUrl, $campaignParams);
                    $isFirstRequest = false;
                } else {
                    $response = Http::get($nextUrl);
                }

                if ($response->failed()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error fetching campaigns: ' . $response->body(),
                    ]);
                }

                $data = $response->json();
                $campaigns = array_merge($campaigns, $data['data'] ?? []);
                $nextUrl = $data['paging']['next'] ?? null;
            } while ($nextUrl);

            /* Fetch Ads With Insights */
            $adsUrl = "https://graph.facebook.com/v24.0/{$adAccountId}/ads";

            $fields = implode(',', [
                'id',
                'name',
                'status',
                'effective_status',
                'campaign{id,name,start_time,stop_time}',
                'creative{id,thumbnail_url,object_story_spec,asset_feed_spec}',
                'insights{reach,impressions,inline_link_clicks,spend,cpc,clicks,frequency,ctr,cpm}'
            ]);

            Log::info(
                'Fetching Facebook Ads with fields: ' .
                    "https://graph.facebook.com/v24.0/{$adAccountId}/ads?fields={$fields}&time_range[since]={$timeRange['since']}&time_range[until]={$timeRange['until']}"
            );

            $ads = [];
            $adsParams = [
                'fields' => $fields,
                'limit' => 50,
                'access_token' => $token,
                'time_range' => [
                    'since' => $timeRange['since'],
                    'until' => $timeRange['until'],
                ],
            ];

            $nextAdsUrl = $adsUrl;
            $isFirstAdsRequest = true;

            do {
                if ($isFirstAdsRequest) {
                    $response = Http::get($adsUrl, $adsParams);
                    $isFirstAdsRequest = false;
                } else {
                    $response = Http::get($nextAdsUrl);
                }

                if ($response->failed()) {
                    Log::error('Facebook Ads API Error: ' . $response->body());
                    break;
                }

                $data = $response->json();
                $ads = array_merge($ads, $data['data'] ?? []);
                $nextAdsUrl = $data['paging']['next'] ?? null;
            } while ($nextAdsUrl && count($ads) < 100);

            /* Process Ads */
            $processedAds = [];

            foreach ($ads as $ad) {
                $insight = $ad['insights']['data'][0] ?? [];
                $campaign = $ad['campaign'] ?? [];

                $startDate = isset($campaign['start_time'])
                    ? date('Y-m-d H:i:s', strtotime($campaign['start_time']))
                    : '-';

                $endDate = isset($campaign['stop_time'])
                    ? date('Y-m-d H:i:s', strtotime($campaign['stop_time']))
                    : 'Active';

                $adStatus = $ad['effective_status'] ?? 'UNKNOWN';
                if ($endDate !== 'Active' && strtotime($endDate) < time()) {
                    $adStatus = 'ENDED';
                }

                $creative = $ad['creative'] ?? [];

                $processedAds[] = [
                    'campaign_id' => $campaign['id'] ?? null,
                    'title' => $ad['name'] ?? ($campaign['name'] ?? 'N/A'),
                    'campaign_name' => $campaign['name'] ?? 'N/A',
                    'status' => $adStatus,
                    'results' => $insight['inline_link_clicks'] ?? ($insight['clicks'] ?? 0),
                    'cost_per_result' => isset($insight['cpc']) ? '₹' . number_format($insight['cpc'], 2) : '-',
                    'amount_spent' => isset($insight['spend']) ? '₹' . number_format($insight['spend'], 2) : '-',
                    'views' => $insight['impressions'] ?? 0,
                    'viewers' => $insight['reach'] ?? 0,
                    'budget' => 'INR',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'link_clicks' => $insight['inline_link_clicks'] ?? 0,
                    'follows' => 0,
                    'ad_creative_url' => $creative['object_story_spec']['link_data']['link'] ?? null,
                    'ad_thumbnail' => $creative['thumbnail_url'] ?? null,
                    'ctr' => isset($insight['ctr'])
                        ? round($insight['ctr'] * 100, 2) . '%'
                        : (isset($insight['inline_link_clicks'], $insight['impressions']) && $insight['impressions'] > 0
                            ? round(($insight['inline_link_clicks'] / $insight['impressions']) * 100, 2) . '%'
                            : '0%'),
                    'date_range' => $timeRange['since'] . ' to ' . $timeRange['until']
                ];
            }

            $html = view(
                'backend.pages.facebook.ads.partials.ads-table',
                compact('processedAds', 'campaigns')
            )->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'date_range' => $timeRange,
                'total_ads' => count($ads),
                'total_campaigns' => count($campaigns),
            ]);
        } catch (\Exception $e) {
            Log::error('Facebook Ads Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function getAdsSummary($adAccountId)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();
            if (!$mainAccount) {
                return response()->json(['success' => false, 'message' => 'Facebook account not connected.']);
            }

            $dateRange = request()->get('date_range', null);
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 300);
            $campaignFilter = [];
            if (request()->has('campaign_filter')) {
                $campaignFilter = request()->get('campaign_filter');
            } elseif (request()->has('campaign_filter[]')) {
                $campaignFilter = request()->get('campaign_filter[]', []);
            }
            
            if (is_string($campaignFilter)) {
                if (strpos($campaignFilter, ',') !== false) {
                    $campaignFilter = explode(',', $campaignFilter);
                } else {
                    $campaignFilter = [$campaignFilter];
                }
            }
            
            $campaignFilter = array_filter($campaignFilter, function($value) {
                return !empty($value) && $value !== '';
            });

            Log::info('Campaign Filter received: ' . json_encode($campaignFilter));
            Log::info('Filter applied: ' . (!empty($campaignFilter) ? 'YES' : 'NO'));
            
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            
            if ($dateRange) {
                $dates = explode(' - ', $dateRange);
                if (count($dates) == 2) {
                    $timeRange = [
                        'since' => $dates[0],
                        'until' => $dates[1],
                    ];
                }
            }
            
            if (empty($timeRange ?? [])) {
                $timeRange = [
                    'since' => date('Y-m-d', strtotime('-28 days')),
                    'until' => date('Y-m-d', strtotime('-1 day'))
                ];
            }
            
            /* Fetch Campaigns */
            $campaignsUrl = "https://graph.facebook.com/v24.0/{$adAccountId}/campaigns";
            $campaignFields = 'id,account_id,name';
            $campaigns = [];
            $campaignParams = [
                'fields' => $campaignFields,
                'limit' => 50,
                'access_token' => $token,
            ];

            $nextUrl = $campaignsUrl;
            $isFirstRequest = true;
            
            do {
                if ($isFirstRequest) {
                    $response = Http::get($campaignsUrl, $campaignParams);
                    $isFirstRequest = false;
                } else {
                    $response = Http::get($nextUrl);
                }

                if ($response->failed()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error fetching campaigns: ' . $response->body(),
                    ]);
                }

                $data = $response->json();
                $campaigns = array_merge($campaigns, $data['data'] ?? []);
                $nextUrl = $data['paging']['next'] ?? null;
            } while ($nextUrl);

            /* Fetch Ads With Insights - Single Page Only */
            $adsUrl = "https://graph.facebook.com/v24.0/{$adAccountId}/ads";

            $fields = implode(',', [
                'id',
                'name',
                'status',
                'effective_status',
                'campaign{id,name,start_time,stop_time}',
                'creative{id,thumbnail_url,object_story_spec,asset_feed_spec}',
                'insights{reach,impressions,inline_link_clicks,spend,cpc,clicks,frequency,ctr,cpm}'
            ]);

            $ads = [];
            $adsParams = [
                'fields' => $fields,
                'limit' => $limit,
                'access_token' => $token,
                'time_range' => [
                    'since' => $timeRange['since'],
                    'until' => $timeRange['until'],
                ],
            ];

            if ($page > 1) {
                $after = request()->get('after');
                if ($after) {
                    $adsParams['after'] = $after;
                }
            }

            $response = Http::get($adsUrl, $adsParams);
            
            if ($response->failed()) {
                Log::error('Facebook Ads API Error: ' . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => 'Error fetching ads: ' . $response->body(),
                ]);
            }
            
            $data = $response->json();
            $allAds = $data['data'] ?? [];
            $paging = $data['paging'] ?? [];            
            $nextPageCursor = $paging['cursors']['after'] ?? null;
            $prevPageCursor = $paging['cursors']['before'] ?? null;
            $hasNextPage = isset($paging['next']);
            $hasPrevPage = isset($paging['previous']);
            /* Process Ads */
            $processedAds = [];
            $filteredCampaignIds = [];            
            foreach ($allAds as $ad) {
                $campaignId = $ad['campaign']['id'] ?? null;
                if (!empty($campaignFilter) && is_array($campaignFilter) && $campaignId) {
                    if (!in_array($campaignId, $campaignFilter)) {
                        continue; 
                    }
                }
                $insight = $ad['insights']['data'][0] ?? [];
                $campaign = $ad['campaign'] ?? [];                
                $startDate = isset($campaign['start_time'])
                    ? date('Y-m-d H:i:s', strtotime($campaign['start_time']))
                    : '-';

                $endDate = isset($campaign['stop_time'])
                    ? date('Y-m-d H:i:s', strtotime($campaign['stop_time']))
                    : 'Active';

                $adStatus = $ad['effective_status'] ?? 'UNKNOWN';
                if ($endDate !== 'Active' && strtotime($endDate) < time()) {
                    $adStatus = 'ENDED';
                }
                
                $creative = $ad['creative'] ?? [];
                
                $processedAds[] = [
                    'campaign_id' => $campaignId,
                    'title' => $ad['name'] ?? ($campaign['name'] ?? 'N/A'),
                    'campaign_name' => $campaign['name'] ?? 'N/A',
                    'status' => $adStatus,
                    'results' => $insight['inline_link_clicks'] ?? ($insight['clicks'] ?? 0),
                    'cost_per_result' => isset($insight['cpc']) ? '₹' . number_format($insight['cpc'], 2) : '-',
                    'amount_spent' => isset($insight['spend']) ? '₹' . number_format($insight['spend'], 2) : '-',
                    'views' => $insight['impressions'] ?? 0,
                    'viewers' => $insight['reach'] ?? 0,
                    'budget' => 'INR',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'link_clicks' => $insight['inline_link_clicks'] ?? 0,
                    'follows' => 0,
                    'ad_creative_url' => $creative['object_story_spec']['link_data']['link'] ?? null,
                    'ad_thumbnail' => $creative['thumbnail_url'] ?? null,
                    'ctr' => isset($insight['ctr'])
                        ? round($insight['ctr'] * 100, 2) . '%'
                        : (isset($insight['inline_link_clicks'], $insight['impressions']) && $insight['impressions'] > 0
                            ? round(($insight['inline_link_clicks'] / $insight['impressions']) * 100, 2) . '%'
                            : '0%'),
                    'date_range' => $timeRange['since'] . ' to ' . $timeRange['until']
                ];

                if ($campaignId && !in_array($campaignId, $filteredCampaignIds)) {
                    $filteredCampaignIds[] = $campaignId;
                }
            }
            /*Filter pagination */
            $totalFilteredAds = count($processedAds);
            $isFilterApplied = !empty($campaignFilter);
            if ($isFilterApplied) {
                if ($totalFilteredAds < $limit) {
                    $hasNextPage = false;
                }
            }
            $hasPrevPage = ($page > 1) ? $hasPrevPage : false;

            $pagination = [
                'current_page' => $page,
                'next_cursor' => $nextPageCursor,
                'prev_cursor' => $prevPageCursor,
                'has_next' => $hasNextPage,
                'has_prev' => $hasPrevPage,
                'per_page' => $limit,
                'has_previous' => $hasPrevPage,
                'is_filter_applied' => $isFilterApplied,
                'filtered_count' => $totalFilteredAds
            ];
            
            $html = view(
                'backend.pages.facebook.ads.partials.ads-table',
                compact('processedAds', 'campaigns', 'pagination', 'campaignFilter')
            )->render();
            
            return response()->json([
                'success' => true,
                'html' => $html,
                'date_range' => $timeRange,
                'total_ads' => $totalFilteredAds,
                'total_campaigns' => count($campaigns),
                'filtered_campaigns' => $filteredCampaignIds,
                'pagination' => $pagination,
                'is_filter_applied' => $isFilterApplied
            ]);
        } catch (\Exception $e) {
            Log::error('Facebook Ads Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
}
