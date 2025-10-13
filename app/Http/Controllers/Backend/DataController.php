<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\SocialAccount;

class DataController extends Controller
{
    public function index()
    {
        $accounts = SocialAccount::where('user_id', Auth::id())->get();

        $facebookData = [];
        $youtubeData = [];

        foreach ($accounts as $account) {
            if ($account->provider === 'facebook' && $account->account_id === null) {
                // skip provider-level token row if you want only page rows; if you stored pages separately, use those
                // Here we fetch the default /me posts if page-specific not available
                $facebookData = $this->getFacebookData($account);
            }
            if ($account->provider === 'google') {
                $youtubeData = $this->getYouTubeData($account);
            }
        }

        return view('dashboard', compact('accounts','facebookData','youtubeData'));
    }

    public function platformData($provider)
    {
        $account = SocialAccount::where('user_id', Auth::id())->where('provider', $provider)->first();
        if (!$account) return redirect()->route('dashboard')->with('error','Not connected to '.$provider);

        if ($provider === 'facebook') {
            $data = $this->getFacebookData($account);
            return view('social.facebook_data', ['posts'=>$data]);
        }

        if ($provider === 'google') {
            $data = $this->getYouTubeData($account);
            return view('social.youtube_data', ['videos'=>$data]);
        }

        abort(404);
    }

    public function filter(Request $request)
    {
        $provider = $request->get('platform');
        $from = $request->get('from');
        $to = $request->get('to');

        $account = SocialAccount::where('user_id', Auth::id())->where('provider', $provider)->first();
        if (!$account) return back()->with('error','Not connected');

        if ($provider === 'facebook') {
            $data = $this->getFacebookData($account,$from,$to);
            return view('partials.social_data', ['data'=>$data,'platform'=>'facebook']);
        }

        if ($provider === 'google') {
            $data = $this->getYouTubeData($account,$from,$to);
            return view('partials.social_data', ['data'=>$data,'platform'=>'google']);
        }

        return back();
    }

    /* ---------- Helpers ---------- */

    private function getFacebookData($account, $from = null, $to = null)
    {
        $enc = Crypt::decryptString($account->access_token);
        $tokenJson = json_decode($enc, true);
        $token = $tokenJson['token'] ?? $enc;

        $fb = new \Facebook\Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v15.0',
        ]);

        try {
            $response = $fb->get('/me/posts?fields=id,message,created_time,attachments,likes.summary(true),comments.summary(true)&limit=25', $token);
            $postsEdge = $response->getGraphEdge();
            $posts = [];
            foreach ($postsEdge as $p) { $posts[] = $p->asArray(); }

            if ($from || $to) {
                $posts = array_filter($posts, function($p) use($from,$to){
                    if (empty($p['created_time'])) return false;
                    $dt = \Carbon\Carbon::parse(is_object($p['created_time']) ? $p['created_time']->format('Y-m-d H:i:s') : $p['created_time']);
                    if ($from && $dt->lt(\Carbon\Carbon::parse($from))) return false;
                    if ($to && $dt->gt(\Carbon\Carbon::parse($to)->endOfDay())) return false;
                    return true;
                });
            }

            return $posts;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function getYouTubeData($account, $from = null, $to = null)
    {
        $enc = Crypt::decryptString($account->access_token);
        $tokenJson = json_decode($enc, true);
        $token = $tokenJson['token'] ?? $enc;

        $client = new \Google_Client();

        if (is_string($token) && str_starts_with($token,'{')) $token = json_decode($token,true);

        if (is_string($token)) {
            $client->setAccessToken(['access_token'=>$token]);
        } else {
            $client->setAccessToken($token);
        }

        if ($client->isAccessTokenExpired()) {
            if ($account->refresh_token) {
                $client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($account->refresh_token));
                $newToken = $client->getAccessToken();
                $account->access_token = Crypt::encryptString(json_encode($newToken));
                if (isset($newToken['refresh_token'])) $account->refresh_token = Crypt::encryptString($newToken['refresh_token']);
                $account->token_expires_at = now()->addSeconds($newToken['expires_in'] ?? 3600);
                $account->save();
            } else {
                return ['error'=>'Token expired - please reconnect'];
            }
        }

        $youtube = new \Google_Service_YouTube($client);
        $videos = [];

        try {
            $channels = $youtube->channels->listChannels('contentDetails,snippet', ['mine'=>true]);
            foreach ($channels->getItems() as $channel) {
                $uploadsPlaylistId = $channel->getContentDetails()->getRelatedPlaylists()->getUploads();
                $playlistItems = $youtube->playlistItems->listPlaylistItems('snippet,contentDetails',['playlistId'=>$uploadsPlaylistId,'maxResults'=>25]);
                foreach ($playlistItems->getItems() as $item) {
                    $published = $item->getSnippet()->getPublishedAt();
                    $videos[] = [
                        'videoId' => $item->getContentDetails()->getVideoId(),
                        'title'   => $item->getSnippet()->getTitle(),
                        'publishedAt' => $published,
                    ];
                }
            }

            if ($from || $to) {
                $videos = array_filter($videos, function($v) use($from,$to){
                    $pv = \Carbon\Carbon::parse($v['publishedAt']);
                    if ($from && $pv->lt(\Carbon\Carbon::parse($from))) return false;
                    if ($to && $pv->gt(\Carbon\Carbon::parse($to)->endOfDay())) return false;
                    return true;
                });
            }

            return $videos;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /* ---------- Instagram & YouTube demographics helpers ---------- */

    public function instagramView($socialAccountId)
    {
        $ins = $this->getInstagramInsights($socialAccountId);
        return view('social.instagram_insights', ['ins'=>$ins]);
    }

    private function getInstagramInsights($socialAccountId, $from = null, $to = null)
    {
        $sa = SocialAccount::findOrFail($socialAccountId);
        $enc = Crypt::decryptString($sa->access_token);
        $tokenJson = json_decode($enc,true);
        $token = $tokenJson['token'] ?? $enc;

        $fb = new \Facebook\Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v15.0',
        ]);

        $igUserId = $sa->account_id;
        $metrics = 'impressions,reach,profile_views,follower_count';
        $resp = $fb->get("/{$igUserId}/insights?metric={$metrics}&period=day", $token);
        $profileInsights = $resp->getDecodedBody()['data'] ?? [];

        $mediaResp = $fb->get("/{$igUserId}/media?fields=id,caption,media_type,media_url,timestamp&limit=25", $token);
        $media = $mediaResp->getDecodedBody()['data'] ?? [];

        $mediaInsights = [];
        foreach ($media as $m) {
            $mid = $m['id'];
            $ins = $fb->get("/{$mid}/insights?metric=engagement,impressions,reach,saved", $token);
            $mediaInsights[$mid] = ['meta'=>$m,'insights'=>$ins->getDecodedBody()['data'] ?? []];
        }
        return ['profile'=>$profileInsights,'media'=>$mediaInsights];
    }

    public function youtubeDemographics($socialAccountId)
    {
        $sa = SocialAccount::findOrFail($socialAccountId);
        $rows = $this->getYouTubeDemographics($sa);
        return view('social.youtube_demographics',['rows'=>$rows]);
    }

    private function getYouTubeDemographics($sa, $from = null, $to = null)
    {
        $enc = Crypt::decryptString($sa->access_token);
        $token = json_decode($enc,true) ?: $enc;

        $client = new \Google_Client();
        if (is_string($token)) $client->setAccessToken(['access_token'=>$token]); else $client->setAccessToken($token);

        if ($client->isAccessTokenExpired() && $sa->refresh_token) {
            $client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($sa->refresh_token));
        }

        $ytAnalytics = new \Google_Service_YouTubeAnalytics($client);
        $start = $from ?? now()->subDays(30)->format('Y-m-d');
        $end = $to ?? now()->format('Y-m-d');

        $result = $ytAnalytics->reports->query('channel==MINE', $start, $end, 'views', ['dimensions'=>'ageGroup,gender']);
        return $result->getRows();
    }
}
