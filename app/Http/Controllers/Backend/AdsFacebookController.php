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


}
