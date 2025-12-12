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

            // Fetch Instagram profile
            $instagram = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'name,username,biography,followers_count,follows_count,media_count,profile_picture_url',
                'access_token' => $token,
            ])->json();

            if (isset($instagram['error'])) {
                return response()->json(['error' => 'Failed to fetch Instagram profile'], 500);
            }

            // Fetch all required data for PDF
            $performanceData = $this->fetchPerformanceData($id, $token, $startDate, $endDate);

            // Fetch top locations
            $locations = $this->getLocationsForPdf($id, $token, $startDate);

            // Fetch audience age data
            $audienceAgeData = $this->getAudienceAgeForPdf($id, $token, $startDate);

            // Fetch reach days data
            $reachDaysData = $this->getReachDaysForPdf($id, $token, $startDate, $endDate);

            // Fetch views data
            $viewsData = $this->getViewsForPdf($id, $token, $startDate, $endDate);

            // Fetch posts data
            $postsData = $this->getPostsForPdf($id, $token, $startDate, $endDate);

            // Generate PDF using DomPDF
            $pdf = PDF::loadView('backend.pages.instagram.pdf.report', [
                'instagram' => $instagram,
                'performanceData' => $performanceData,
                'locations' => $locations,
                'audienceAgeData' => $audienceAgeData,
                'reachDaysData' => $reachDaysData,
                'viewsData' => $viewsData,
                'postsData' => $postsData,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'dateRange' => Carbon::parse($startDate)->format('d M Y') . ' - ' . Carbon::parse($endDate)->format('d M Y')
            ]);

            // Set PDF options
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('defaultFont', 'sans-serif');

            $fileName = 'instagram-report-' . $id . '-' . now()->format('Y-m-d') . '.pdf';

            // Return the PDF as download
            return $pdf->download($fileName);
        } catch (\Exception $e) {
            Log::error('PDF Generation Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    // Helper methods for PDF data
    private function getLocationsForPdf($accountId, $token, $timeframe = 'this_month')
    {
        $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'city',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();

        if (isset($response['error'])) {
            return [];
        }

        $results = data_get($response, 'data.0.total_value.breakdowns.0.results', []);

        return collect($results)
            ->sortByDesc('value')
            ->take(10)
            ->values()
            ->map(function ($item) {
                return [
                    'name' => $item['dimension_values'][0] ?? 'Unknown',
                    'value' => $item['value'] ?? 0
                ];
            })->toArray();
    }

    private function getAudienceAgeForPdf($accountId, $token, $timeframe = 'this_month')
    {
        $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'engaged_audience_demographics',
            'period' => 'lifetime',
            'metric_type' => 'total_value',
            'breakdown' => 'age,gender',
            'timeframe' => $timeframe,
            'access_token' => $token,
        ])->json();

        if (isset($response['error'])) {
            return ['labels' => [], 'male' => [], 'female' => []];
        }

        $results = $response['data'][0]['total_value']['breakdowns'][0]['results'] ?? [];
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

        return [
            'labels' => array_keys($ageGroups),
            'male' => array_column($ageGroups, 'M'),
            'female' => array_column($ageGroups, 'F')
        ];
    }

    private function getReachDaysForPdf($accountId, $token, $startDate, $endDate)
    {
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();
        $since = $start->timestamp;
        $until = $end->timestamp;

        $response = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'reach',
            'metric_type' => 'time_series',
            'period' => 'day',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        if (!isset($response['data'][0]['values'])) {
            return [];
        }

        return collect($response['data'][0]['values'])->map(function ($item) {
            return [
                'date' => date('d M', strtotime($item['end_time'])),
                'value' => $item['value'],
            ];
        })->values()->toArray();
    }

    private function getViewsForPdf($accountId, $token, $startDate, $endDate)
    {
        $start = \Carbon\Carbon::parse($startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($endDate)->endOfDay();
        $since = $start->timestamp;
        $until = $end->timestamp;

        $response = Http::timeout(15)->get("https://graph.facebook.com/v24.0/{$accountId}/insights", [
            'metric' => 'views',
            'metric_type' => 'total_value',
            'period' => 'day',
            'breakdown' => 'media_product_type',
            'since' => $since,
            'until' => $until,
            'access_token' => $token,
        ])->json();

        if (!isset($response['data'][0]['total_value'])) {
            return ['categories' => [], 'values' => []];
        }

        $breakdowns = $response['data'][0]['total_value']['breakdowns'][0]['results'] ?? [];
        $categories = [];
        $values = [];

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
                default:
                    $label = $mediaType;
            }

            $categories[] = $label;
            $values[] = $value;
        }

        return [
            'categories' => $categories,
            'values' => $values,
            'total_views' => $response['data'][0]['total_value']['value'] ?? 0
        ];
    }

    private function getPostsForPdf($accountId, $token, $startDate, $endDate, $limit = 10)
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);
        $since = $start->timestamp;
        $until = $end->timestamp;

        $response = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$accountId}/media", [
            'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count,media_product_type',
            'access_token' => $token,
            'since' => $since,
            'until' => $until,
            'limit' => $limit
        ])->json();

        return $response['data'] ?? [];
    }
}
