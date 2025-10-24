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
    
}
