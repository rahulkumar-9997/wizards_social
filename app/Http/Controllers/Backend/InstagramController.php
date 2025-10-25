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
                    'html' => view('backend.pages.instagram.partials.instagram-media-table', compact('media', 'paging'))->render(),
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


    public function postComment(Request $request, $mediaId)
    {
        try {
            $request->validate(['message' => 'required|string|max:1000']);

            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return response()->json(['error' => 'Facebook account not connected'], 401);
            }

            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $postResponse = Http::timeout(15)->post("https://graph.facebook.com/v24.0/{$mediaId}/comments", [
                'message' => $request->message,
                'access_token' => $token,
            ])->json();

            if (isset($postResponse['error'])) {
                throw new Exception($postResponse['error']['message']);
            }
            return response()->json(['success' => true, 'comment_id' => $postResponse['id'] ?? null]);
        } catch (Exception $e) {
            Log::error('Error posting IG comment: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function getPostGraphData(Request $request)
    {
        try {
            $timeRange = $request->get('time_range', 'week');
            $data = $this->generateSimpleGraphData($timeRange);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('Graph data error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load graph data'], 500);
        }
    }

    /**
     * Generate simple graph data
     */
    private function generateSimpleGraphData($timeRange)
    {
        // Simple demo data
        $dates = [];
        $impressions = [];
        $reach = [];
        $likes = [];
        $engagement = [];

        // Generate dates based on time range
        if ($timeRange === 'week') {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $dates[] = $date->format('M j');
                $impressions[] = rand(500, 2000);
                $reach[] = rand(400, 1800);
                $likes[] = rand(100, 800);
                $engagement[] = rand(150, 900); // likes + comments + shares
            }
        } else {
            // Month view
            for ($i = 29; $i >= 0; $i -= 2) {
                $date = now()->subDays($i);
                $dates[] = $date->format('M j');
                $impressions[] = rand(800, 4000);
                $reach[] = rand(600, 3500);
                $likes[] = rand(200, 1500);
                $engagement[] = rand(300, 1800); // likes + comments + shares
            }
        }

        return [
            'dates' => $dates,
            'impressions' => $impressions,
            'reach' => $reach,
            'likes' => $likes,
            'engagement' => $engagement
        ];
    }
}
