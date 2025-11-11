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
    public function facebookHtmlDataIndex($id)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();
            if (!$mainAccount) {
                return back()->with('error', 'Facebook account not connected');
            }
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            if (!$token) {
                return back()->with('error', 'Access token not found or expired.');
            }

            $response = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'id,name,about,category,fan_count,followers_count,picture{url},cover,link,emails,connected_instagram_account,is_published,rating_count,instagram_business_account,is_owned',
                'access_token' => $token,
            ]);

            $facebookBusinessOrProfile = $response->json();
            if ($response->failed()) {
                Log::error('Facebook API Error:', $facebookBusinessOrProfile);
                return back()->with('error', $facebookBusinessOrProfile['error']['message'] ?? 'Facebook API request failed.');
            }
            Log::info('Fetched Facebook Profile:', $facebookBusinessOrProfile);
            return view('backend.pages.facebook.fb-summary.fb-report', compact('facebookBusinessOrProfile'));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->with('error', 'No internet connection.');
        } catch (\Exception $e) {
            Log::error('Facebook Data Fetch Error:', ['message' => $e->getMessage()]);
            return back()->with('error', $e->getMessage());
        }
    }

    public function fetchHtmlForFb($id, Request $request)
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
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            if (!$token) {
                return response()->json(['error' => 'Access token not found or expired.'], 400);
            }

            $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->get('end_date', now()->format('Y-m-d'));
            $performanceData = $this->fetchFbPerformanceData($fb_page_id, $token, $startDate, $endDate);
            $html = $this->renderFacebookDashboardHtml($fb_page_id, $performanceData);
            return response()->json([
                'success' => true,
                'html' => $html,
                'data' => $performanceData
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Facebook connection error: ' . $e->getMessage());
            return response()->json(['error' => 'No internet connection.'], 500);
        } catch (\Exception $e) {
            Log::error('Facebook data fetch error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    private function fetchFbPerformanceData($id, $token, $startDate, $endDate)
    {
        try {
            $fb_page_id = $id;
            $fb_reach_day_wise_unique = $this->fbReacDayWiseUnique($fb_page_id, $token, $startDate, $endDate);
            $fb_paid_reach_day_wise_unique = $this->fbPaidReacDayWiseUnique($fb_page_id, $token, $startDate, $endDate);
            $performanceData = [
                'fb_total_reach_unique' => $fb_reach_day_wise_unique,
                'fb_paid_reach_quniue' => $fb_paid_reach_day_wise_unique,
                'date_range' => [
                    'start' => $startDate,
                    'end' => $endDate,
                    'display' => \Carbon\Carbon::parse($startDate)->format('d F Y') . ' - ' . \Carbon\Carbon::parse($endDate)->format('d F Y')
                ]
            ];
            return $performanceData;
        } catch (\Exception $e) {
            Log::error('Error in fetchFbPerformanceData: ' . $e->getMessage());
            return [
                'error' => 'Failed to fetch performance data',
                'details' => $e->getMessage()
            ];
        }
    }

    public function fbReacDayWiseUnique($pageId, $token, $startDate, $endDate)
    {
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

        Log::info('Date Range for fbReacDayWise', [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'since' => $since,
            'until' => $until,
            'prevStart' => $prevStart->toDateString(),
            'prevEnd' => $prevEnd->toDateString(),
        ]);

        try {
            $url = "https://graph.facebook.com/v24.0/{$pageId}/insights";
            $currentResponse = Http::timeout(15)->get($url, [
                'metric' => 'page_impressions_unique',
                'since' => $since,
                'until' => $until,
                'period' => 'day',
                'access_token' => $token,
            ]);

            if ($currentResponse->failed()) {
                $error = $currentResponse->json();
                Log::error('Facebook Insights API Error (Current)', $error);
                return ['error' => $error['error']['message'] ?? 'Facebook API request failed'];
            }

            $currentData = $currentResponse->json();
            $currentValues = $currentData['data'][0]['values'] ?? [];
            $description = $currentData['data'][0]['description'] ?? '';
            $totalCurrentReach = collect($currentValues)->sum('value');

            $previousResponse = Http::timeout(15)->get($url, [
                'metric' => 'page_impressions_unique',
                'since' => $previousSince,
                'until' => $previousUntil,
                'period' => 'day',
                'access_token' => $token,
            ]);

            if ($previousResponse->failed()) {
                $error = $previousResponse->json();
                Log::error('Facebook Insights API Error (Previous)', $error);
                return ['error' => $error['error']['message'] ?? 'Facebook API request failed'];
            }

            $previousData = $previousResponse->json();
            $previousValues = $previousData['data'][0]['values'] ?? [];
            $totalPreviousReach = collect($previousValues)->sum('value');

            $percentageChange = $totalPreviousReach > 0
                ? round((($totalCurrentReach - $totalPreviousReach) / $totalPreviousReach) * 100, 2)
                : null;

            Log::info('Facebook Reach Summary', [
                'current_total' => $totalCurrentReach,
                'previous_total' => $totalPreviousReach,
                'percentage_change' => $percentageChange,
            ]);

            $reach = [
                'description' => $description,
                'current' => [
                    'total_reach' => $totalCurrentReach,
                    'daily_data' => $currentValues,
                ],
                'previous' => [
                    'total_reach' => $totalPreviousReach,
                    'daily_data' => $previousValues,
                ],
                'percentage_change' => $percentageChange,
            ];
            return $reach;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection error in fbReacDayWise: ' . $e->getMessage());
            return ['error' => 'Connection error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook Reach (day-wise): ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    public function fbPaidReacDayWiseUnique($pageId, $token, $startDate, $endDate)
    {
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

        Log::info('Date Range for fbReacDayWise', [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'since' => $since,
            'until' => $until,
            'prevStart' => $prevStart->toDateString(),
            'prevEnd' => $prevEnd->toDateString(),
        ]);

        try {
            $url = "https://graph.facebook.com/v24.0/{$pageId}/insights";
            $currentResponse = Http::timeout(15)->get($url, [
                'metric' => 'page_impressions_paid_unique',
                'since' => $since,
                'until' => $until,
                'period' => 'day',
                'access_token' => $token,
            ]);

            if ($currentResponse->failed()) {
                $error = $currentResponse->json();
                Log::error('Facebook Insights API Error (Current)', $error);
                return ['error' => $error['error']['message'] ?? 'Facebook API request failed'];
            }

            $currentData = $currentResponse->json();
            $currentValues = $currentData['data'][0]['values'] ?? [];
            $description = $currentData['data'][0]['description'] ?? '';
            $totalCurrentReach = collect($currentValues)->sum('value');

            $previousResponse = Http::timeout(15)->get($url, [
                'metric' => 'page_impressions_paid_unique',
                'since' => $previousSince,
                'until' => $previousUntil,
                'period' => 'day',
                'access_token' => $token,
            ]);

            if ($previousResponse->failed()) {
                $error = $previousResponse->json();
                Log::error('Facebook Insights API Error (Previous)', $error);
                return ['error' => $error['error']['message'] ?? 'Facebook API request failed'];
            }

            $previousData = $previousResponse->json();
            $previousValues = $previousData['data'][0]['values'] ?? [];
            $totalPreviousReach = collect($previousValues)->sum('value');

            $percentageChange = $totalPreviousReach > 0
                ? round((($totalCurrentReach - $totalPreviousReach) / $totalPreviousReach) * 100, 2)
                : null;

            Log::info('Facebook Reach Summary', [
                'current_total' => $totalCurrentReach,
                'previous_total' => $totalPreviousReach,
                'percentage_change' => $percentageChange,
            ]);

            $reach = [
                'description' => $description,
                'current' => [
                    'total_reach' => $totalCurrentReach,
                    'daily_data' => $currentValues,
                ],
                'previous' => [
                    'total_reach' => $totalPreviousReach,
                    'daily_data' => $previousValues,
                ],
                'percentage_change' => $percentageChange,
            ];
            return $reach;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection error in fbReacDayWise: ' . $e->getMessage());
            return ['error' => 'Connection error: ' . $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('Error fetching Facebook Reach (day-wise): ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }



    public function renderFacebookDashboardHtml($pageId, $performanceData)
    {
        if (isset($performanceData['error'])) {
            return "<div class='alert alert-danger'>Error: {$performanceData['error']}</div>";
        }

        $html = "<h4>Facebook Page ID: {$pageId}</h4>";
        $html .= "<p>Date Range: {$performanceData['date_range']['display']}</p>";


        return $html;
    }
}
