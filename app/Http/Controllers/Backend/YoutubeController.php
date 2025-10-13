<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\SocialAccount;
use Carbon\Carbon;
use Exception;

class YoutubeController extends Controller
{
    /**
     * YouTube Integration Page
     */
    public function index()
    {
        try {
            $user = Auth::user();
            
            // Get YouTube/Google account
            $youtubeAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'google')
                ->whereNull('account_id')
                ->first();

            // Get YouTube data if connected
            $youtubeData = [];
            if ($youtubeAccount) {
                $youtubeData = $this->getYouTubeChannelData($youtubeAccount);
            }

            return view('backend.pages.youtube.index', compact(
                'youtubeAccount',
                'youtubeData'
            ));

        } catch (Exception $e) {
            Log::error('YouTube index error: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to load YouTube page.');
        }
    }

    /**
     * Get YouTube Channel Data
     */
    public function getYouTubeChannelData($account)
    {
        try {
            $token = $this->decryptToken($account->access_token);

            $client = new \Google_Client();
            $client->setAccessToken($token);

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                if ($account->refresh_token) {
                    $client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($account->refresh_token));
                    $newToken = $client->getAccessToken();
                    
                    $account->access_token = Crypt::encryptString(json_encode($newToken));
                    if (isset($newToken['refresh_token'])) {
                        $account->refresh_token = Crypt::encryptString($newToken['refresh_token']);
                    }
                    $account->token_expires_at = now()->addSeconds($newToken['expires_in'] ?? 3600);
                    $account->save();
                    
                    $token = $newToken;
                } else {
                    return ['error' => 'Token expired. Please reconnect your YouTube account.'];
                }
            }

            $youtube = new \Google_Service_YouTube($client);
            $data = [];

            // Get channel information
            $channelsResponse = $youtube->channels->listChannels('snippet,statistics,contentDetails', ['mine' => true]);
            
            foreach ($channelsResponse->getItems() as $channel) {
                $channelData = [
                    'id' => $channel->getId(),
                    'title' => $channel->getSnippet()->getTitle(),
                    'description' => $channel->getSnippet()->getDescription(),
                    'thumbnail' => $channel->getSnippet()->getThumbnails()->getDefault()->getUrl(),
                    'subscribers' => $channel->getStatistics()->getSubscriberCount(),
                    'views' => $channel->getStatistics()->getViewCount(),
                    'videos' => $channel->getStatistics()->getVideoCount(),
                    'uploads_playlist_id' => $channel->getContentDetails()->getRelatedPlaylists()->getUploads()
                ];

                // Get recent videos from uploads playlist
                $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet,contentDetails', [
                    'playlistId' => $channelData['uploads_playlist_id'],
                    'maxResults' => 10
                ]);

                $videos = [];
                foreach ($playlistItemsResponse->getItems() as $item) {
                    $videoId = $item->getContentDetails()->getVideoId();
                    
                    // Get video statistics
                    $videoResponse = $youtube->videos->listVideos('snippet,statistics', ['id' => $videoId]);
                    $video = $videoResponse->getItems()[0] ?? null;
                    
                    if ($video) {
                        $videos[] = [
                            'id' => $videoId,
                            'title' => $video->getSnippet()->getTitle(),
                            'description' => $video->getSnippet()->getDescription(),
                            'thumbnail' => $video->getSnippet()->getThumbnails()->getMedium()->getUrl(),
                            'published_at' => $video->getSnippet()->getPublishedAt(),
                            'views' => $video->getStatistics()->getViewCount(),
                            'likes' => $video->getStatistics()->getLikeCount(),
                            'comments' => $video->getStatistics()->getCommentCount()
                        ];
                    }
                }

                $channelData['recent_videos'] = $videos;
                $data['channels'][] = $channelData;
            }

            return $data;

        } catch (Exception $e) {
            Log::error('YouTube data fetch error: ' . $e->getMessage());
            return ['error' => 'Failed to fetch YouTube data: ' . $e->getMessage()];
        }
    }

    /**
     * Get YouTube Analytics
     */
    public function getAnalytics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'metrics' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        try {
            $account = SocialAccount::where('user_id', Auth::id())
                ->where('provider', 'google')
                ->whereNull('account_id')
                ->firstOrFail();

            $token = $this->decryptToken($account->access_token);

            $client = new \Google_Client();
            $client->setAccessToken($token);

            if ($client->isAccessTokenExpired() && $account->refresh_token) {
                $client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($account->refresh_token));
            }

            $ytAnalytics = new \Google_Service_YouTubeAnalytics($client);
            
            $startDate = $request->start_date ?? now()->subDays(30)->format('Y-m-d');
            $endDate = $request->end_date ?? now()->format('Y-m-d');
            $metrics = $request->metrics ?? 'views,estimatedMinutesWatched,subscribersGained';

            $result = $ytAnalytics->reports->query(
                'channel==MINE',
                $startDate,
                $endDate,
                $metrics,
                ['dimensions' => 'day']
            );

            return response()->json([
                'success' => true,
                'analytics' => $result->getRows(),
                'period' => ['start' => $startDate, 'end' => $endDate]
            ]);

        } catch (Exception $e) {
            Log::error('YouTube analytics error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch analytics'], 500);
        }
    }

    /**
     * Get YouTube Demographics
     */
    public function getDemographics($socialAccountId)
    {
        try {
            $account = SocialAccount::findOrFail($socialAccountId);
            
            if ($account->user_id !== Auth::id()) {
                abort(403, 'Unauthorized');
            }

            $token = $this->decryptToken($account->access_token);

            $client = new \Google_Client();
            $client->setAccessToken($token);

            if ($client->isAccessTokenExpired() && $account->refresh_token) {
                $client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($account->refresh_token));
            }

            $ytAnalytics = new \Google_Service_YouTubeAnalytics($client);
            
            $startDate = now()->subDays(30)->format('Y-m-d');
            $endDate = now()->format('Y-m-d');

            $result = $ytAnalytics->reports->query(
                'channel==MINE',
                $startDate,
                $endDate,
                'views',
                ['dimensions' => 'ageGroup,gender']
            );

            return response()->json([
                'success' => true,
                'demographics' => $result->getRows() ?? []
            ]);

        } catch (Exception $e) {
            Log::error('YouTube demographics error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch demographics'], 500);
        }
    }

    /**
     * Get YouTube Videos
     */
    public function getVideos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'max_results' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        try {
            $account = SocialAccount::where('user_id', Auth::id())
                ->where('provider', 'google')
                ->whereNull('account_id')
                ->firstOrFail();

            $token = $this->decryptToken($account->access_token);

            $client = new \Google_Client();
            $client->setAccessToken($token);

            if ($client->isAccessTokenExpired() && $account->refresh_token) {
                $client->fetchAccessTokenWithRefreshToken(Crypt::decryptString($account->refresh_token));
            }

            $youtube = new \Google_Service_YouTube($client);
            $maxResults = $request->get('max_results', 20);

            // Get channels to find uploads playlist
            $channelsResponse = $youtube->channels->listChannels('contentDetails', ['mine' => true]);
            $videos = [];

            foreach ($channelsResponse->getItems() as $channel) {
                $uploadsPlaylistId = $channel->getContentDetails()->getRelatedPlaylists()->getUploads();
                
                $playlistItemsResponse = $youtube->playlistItems->listPlaylistItems('snippet,contentDetails', [
                    'playlistId' => $uploadsPlaylistId,
                    'maxResults' => $maxResults
                ]);

                foreach ($playlistItemsResponse->getItems() as $item) {
                    $videoId = $item->getContentDetails()->getVideoId();
                    
                    // Get detailed video information
                    $videoResponse = $youtube->videos->listVideos('snippet,statistics,contentDetails', ['id' => $videoId]);
                    $video = $videoResponse->getItems()[0] ?? null;
                    
                    if ($video) {
                        $videos[] = [
                            'id' => $videoId,
                            'title' => $video->getSnippet()->getTitle(),
                            'description' => $video->getSnippet()->getDescription(),
                            'thumbnail' => $video->getSnippet()->getThumbnails()->getMedium()->getUrl(),
                            'published_at' => $video->getSnippet()->getPublishedAt(),
                            'duration' => $video->getContentDetails()->getDuration(),
                            'views' => $video->getStatistics()->getViewCount(),
                            'likes' => $video->getStatistics()->getLikeCount(),
                            'comments' => $video->getStatistics()->getCommentCount()
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'videos' => $videos
            ]);

        } catch (Exception $e) {
            Log::error('YouTube videos error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch videos'], 500);
        }
    }

    /**
     * Decrypt token
     */
    private function decryptToken($encryptedToken)
    {
        try {
            $decrypted = Crypt::decryptString($encryptedToken);
            $tokenData = json_decode($decrypted, true);
            return $tokenData['token'] ?? $decrypted;
        } catch (Exception $e) {
            Log::error('Token decryption error: ' . $e->getMessage());
            throw new Exception('Failed to decrypt access token.');
        }
    }
}