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
    public function facebookMainIndex($id)
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
            //Log::info('Fetched Facebook Profile:', $facebookBusinessOrProfile);
            return view('backend.pages.facebook.fb-summary.fb-report', compact('facebookBusinessOrProfile'));
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return back()->with('error', 'No internet connection.');
        } catch (\Exception $e) {
            Log::error('Facebook Data Fetch Error:', ['message' => $e->getMessage()]);
            return back()->with('error', $e->getMessage());
        }
    }

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
        $html='
        <div class="card">
            <div class="card-header text-white">
                <h4 class="card-title mb-0">
                    Performance
                    (<span class="text-info">'.$performanceData['date_range']['display'].'</span>)
                </h4>
            </div>
            <div class="card-body">
                <div class="reach-section mb-4">
                    <div class="row g-4">
                        <div class="col-md-4 reach col-sm-4 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-0 pe-xl-0 ps-xl-2">
                            <div class="metric-card">
                                <div class="metric-header card-header">
                                    <h4 class="mb-0">
                                        Reach
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="">
                                        </i>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm mb-2 align-middle text-center">
                                        <tbody>
                                            <tr>
                                                <th>
                                                    <h3 class="mb-0">335.6K</h3>
                                                </th>
                                                <th>
                                                    <h3 class="mb-0">690.3K</h3>
                                                </th>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Previous Month</td>
                                                <td class="bg-black text-light">Current Month</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="positive">
                                                    <h4 class="mb-0">▲ +105.68%</h4>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Paid Reach</td>
                                                <td class="bg-black text-light">Organic Reach</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <h4 class="mb-0">421.8K</h4>
                                                </td>
                                                <td>
                                                    <h4 class="mb-0">268.6K</h4>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Followers</td>
                                                <td class="bg-black text-light">Non-Followers</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <h4 class="mb-0">14.7K</h4>
                                                </td>
                                                <td>
                                                    <h4 class="mb-0">675.6K</h4>
                                                </td>
                                            </tr>
                                        </tbody>
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
                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that followed you and the number of accounts that unfollowed you or left Instagram in the selected time period.">
                                                </i>
                                            </h4>
                                        </div>
                                        <div class="metric-body">
                                            <table class="table table-sm mb-2 align-middle text-center">

                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <h3 class="mb-0">
                                                                568
                                                            </h3>
                                                        </th>
                                                        <th>
                                                            <h3 class="mb-0">
                                                                1196
                                                            </h3>
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <td class="bg-black text-light">Previous Month</td>
                                                        <td class="bg-black text-light">Current Month</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="positive">
                                                            <h4 class="mb-0">▲ +110.56%</h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12 col-sm-12 mb-sm-1 mb-md-1 mb-lg-1 mb-xl-1 pe-xl-0 ps-xl-2">
                                    <div class="metric-card">
                                        <div class="metric-header">
                                            <h4>
                                                Unfollowers
                                                <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that followed you and the number of accounts that unfollowed you or left Instagram in the selected time period.">
                                                </i>
                                            </h4>
                                        </div>
                                        <div class="metric-body">
                                            <table class="table table-sm mb-2 align-middle text-center">

                                                <tbody>
                                                    <tr>
                                                        <th>
                                                            <h3 class="mb-0">
                                                                593
                                                            </h3>
                                                        </th>
                                                        <th>
                                                            <h3 class="mb-0">
                                                                273
                                                            </h3>
                                                        </th>
                                                    </tr>
                                                    <tr>
                                                        <td class="bg-black text-light">Previous Month</td>
                                                        <td class="bg-black text-light">Current Month</td>
                                                    </tr>
                                                    <tr>
                                                        <td colspan="2" class="negative">
                                                            <h4 class="mb-0">▼ 53.96%</h4>
                                                        </td>
                                                    </tr>
                                                </tbody>
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
                                        <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of times your content was played or displayed. Content includes reels, posts, stories.">
                                        </i>
                                    </h4>
                                </div>
                                <div class="metric-body">
                                    <table class="table table-sm mb-2 align-middle text-center">
                                        <tbody>
                                            <tr>
                                                <td colspan="2" class="bg-black text-light">Followers</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <h4 class="mb-0">185K</h4>
                                                </td>
                                                <td>
                                                    <h4 class="mb-0">158.6K</h4>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Previous Month</td>
                                                <td class="bg-black text-light">Current Month</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="negative">
                                                    <h4 class="mb-0">▼ 14.25%</h4>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="bg-black text-light">Non Followers</td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <h4 class="mb-0">660.6K</h4>
                                                </td>
                                                <td>
                                                    <h4 class="mb-0">1.2M</h4>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="bg-black text-light">Previous Month</td>
                                                <td class="bg-black text-light">Current Month</td>
                                            </tr>
                                            <tr>
                                                <td colspan="2" class="positive">
                                                    <h4 class="mb-0">▲ +76.02%</h4>
                                                </td>
                                            </tr>
                                        </tbody>
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
                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                            </i>
                                        </h4>
                                    </div>
                                    <div class="metric-body">
                                        <table class="table table-sm mb-3 align-middle text-center">

                                            <tbody>
                                                <tr>
                                                    <td>
                                                        <h4 class="mb-0">4.1K</h4>
                                                    </td>
                                                    <td>
                                                        <h4 class="mb-0">10.8K</h4>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td class="bg-black text-light">Previous Month</td>
                                                    <td class="bg-black text-light">Current Month</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="positive">
                                                        <h4 class="mb-0">▲ +163.4%</h4>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="col-lg-12 mb-2">
                                                    <h5 class="card-title mb-0">Total Interactions by Likes, Comments, Saves, Shares, Reposts </h5>
                                                </div>
                                                <table class="table table-bordered table-sm mb-2 ">
                                                    <tbody>
                                                        <tr>
                                                            <td class="bg-black text-light">Previous Month</td>
                                                            <td class="bg-black text-light">Current Month</td>
                                                        </tr>
                                                        <tr>
                                                            <!-- Previous Month -->
                                                            <td>
                                                                <table class="table table-sm mb-2 ">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Likes
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of likes on your posts, reels and videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">2.4K</h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Comments
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of comments on your posts, reels, videos and live videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">45</h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Saves
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of saves of your posts, reels and videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">531</h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Shares
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of shares of your posts, stories, reels, videos and live videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">627</h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Reposts
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of times your content was reposted. A repost occurs when another account shares your content to their own profile.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">14</h4>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>

                                                            <!-- Current Month -->
                                                            <td>
                                                                <table class="table  table-sm mb-2">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Likes
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of likes on your posts, reels and videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    7.5K
                                                                                    <small class="text-success">
                                                                                        ▲ +211.4%</small>
                                                                                </h4>

                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Comments
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of comments on your posts, reels, videos and live videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    81
                                                                                    <small class="text-success">
                                                                                        ▲ +80%</small>
                                                                                </h4>

                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Saves
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of saves of your posts, reels and videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    817
                                                                                    <small class="text-success">
                                                                                        ▲ +53.86%</small>
                                                                                </h4>

                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Shares
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of shares of your posts, stories, reels, videos and live videos.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    1.2K
                                                                                    <small class="text-success">
                                                                                        ▲ +89.63%</small>
                                                                                </h4>

                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    Reposts
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of times your content was reposted. A repost occurs when another account shares your content to their own profile.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    30
                                                                                    <small class="text-success">
                                                                                        ▲ +114.29%</small>
                                                                                </h4>

                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="col-lg-12 mb-2">
                                                    <h5 class="card-title mb-0">Total Interactions by Media Type</h5>
                                                </div>
                                                <table class="table table-bordered table-sm mb-2">
                                                    <tbody>
                                                        <tr>
                                                            <td class="bg-black text-light">Previous Month</td>
                                                            <td class="bg-black text-light">Current Month</td>
                                                        </tr>
                                                        <tr>
                                                            <!-- Previous Month -->
                                                            <td>
                                                                <table class="table table-sm mb-2">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Post
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">234</h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Ad
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">655</h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Reel
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">3.4K</h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Story
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">144</h4>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>

                                                            <!-- Current Month -->
                                                            <td>
                                                                <table class="table table-sm mb-2">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Post
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    254
                                                                                    <small class="text-success">▲ +8.55%</small>
                                                                                </h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Ad
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    5.4K
                                                                                    <small class="text-success">▲ +721.53%</small>
                                                                                </h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Reel
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    5.1K
                                                                                    <small class="text-success">▲ +53.1%</small>
                                                                                </h4>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>
                                                                                <h4 class="mb-0">Story
                                                                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The total number of post interactions, story interactions, reels interactions, video interactions and live video interactions, including any interactions on boosted content.">
                                                                                    </i>
                                                                                </h4>
                                                                            </td>
                                                                            <td>
                                                                                <h4 class="mb-0">
                                                                                    46
                                                                                    <small class="text-danger">▼ 68.06%</small>
                                                                                </h4>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </td>
                                                        </tr>
                                                    </tbody>
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
                                    <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of times that your profile was visited.">
                                    </i>
                                </h4>
                            </div>
                            <div class="metric-body">
                                <table class="table table-sm mb-2 align-middle text-center">
                                    <tbody>
                                        <tr>
                                            <th>
                                                <h3 class="mb-0">8.9K</h3>
                                            </th>
                                            <th>
                                                <h3 class="mb-0">17.1K</h3>
                                            </th>
                                        </tr>
                                        <tr>
                                            <td class="bg-black text-light">Previous Month</td>
                                            <td class="bg-black text-light">Current Month</td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="positive">
                                                <h4 class="mb-0">▲ +92.16%</h4>
                                            </td>
                                        </tr>
                                    </tbody>
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
                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of taps on your business address, call button, email button and text button.">
                                            </i>
                                        </h4>
                                    </div>
                                    <div class="metric-body">
                                        <table class="table table-sm mb-2 align-middle text-center">

                                            <tbody>
                                                <tr>
                                                    <th>
                                                        <h3 class="mb-0">10</h3>
                                                    </th>
                                                    <th>
                                                        <h3 class="mb-0">8</h3>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td class="bg-black text-light">Previous Month</td>
                                                    <td class="bg-black text-light">Current Month</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="negative">
                                                        <h4 class="mb-0">▼ 20%</h4>
                                                    </td>
                                                </tr>
                                            </tbody>
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
                                            <i class="bx bx-question-mark text-primary" style="cursor: pointer; font-size: 18px;" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="success-tooltip" data-bs-title="The number of accounts that have interacted with your content, including in ads. Content includes posts, stories, reels, videos and live videos. Interactions can include actions such as likes, saves, comments, shares or replies. These metrics are estimated and in development.">
                                            </i>
                                        </h4>
                                    </div>
                                    <div class="metric-body">
                                        <table class="table table-sm mb-2 align-middle text-center">
                                            <tbody>
                                                <tr>
                                                    <th>
                                                        <h3 class="mb-0">2.9K</h3>
                                                    </th>
                                                    <th>
                                                        <h3 class="mb-0">8.7K</h3>
                                                    </th>
                                                </tr>
                                                <tr>
                                                    <td class="bg-black text-light">Previous Month</td>
                                                    <td class="bg-black text-light">Current Month</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2" class="positive">
                                                        <h4 class="mb-0">▲ +202.33%</h4>
                                                    </td>
                                                </tr>
                                            </tbody>
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
                                                <td class="highlight">5</td>
                                                <td>4</td>
                                                <td class="highlight">0</td>
                                                <td>0</td>
                                                <td class="highlight">19</td>
                                                <td>19</td>

                                            </tr>
                                            <tr>
                                                <td colspan="2" style="background-color: #dc3545; color: #fff; font-weight:600;">-20%</td>
                                                <td colspan="2" style="background-color: #f8f9fa;">0%</td>
                                                <td colspan="2" style="background-color: #f8f9fa;">0%</td>

                                            </tr>

                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ';
        return $html;
    }
}
