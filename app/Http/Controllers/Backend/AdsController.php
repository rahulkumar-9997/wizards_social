<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use App\Models\AdAccount;
use App\Models\SocialAccount;

class AdsController extends Controller
{
    public function insights(Request $request, $adAccountId)
    {
        $user = Auth::user();
        $ad = AdAccount::where('user_id',$user->id)->where('ad_account_id',$adAccountId)->firstOrFail();

        $sa = SocialAccount::findOrFail($ad->social_account_id);
        $enc = Crypt::decryptString($sa->access_token);
        $tokenJson = json_decode($enc, true);
        $token = $tokenJson['token'] ?? $enc;

        $fb = new \Facebook\Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v15.0',
        ]);

        $since = $request->get('since', now()->subDays(30)->format('Y-m-d'));
        $until = $request->get('until', now()->format('Y-m-d'));

        $params = http_build_query([
            'time_range' => json_encode(['since'=>$since,'until'=>$until]),
            'fields' => 'impressions,spend,clicks,unique_actions',
            'breakdowns' => 'age,gender',
            'level' => 'ad',
            'limit' => 1000,
        ]);

        $endpoint = "/act_{$adAccountId}/insights?{$params}";

        try {
            $resp = $fb->get($endpoint, $token);
            $data = $resp->getDecodedBody()['data'] ?? [];

            $pivot = [];
            foreach ($data as $row) {
                $age = $row['age'] ?? 'unknown';
                $gender = $row['gender'] ?? 'unknown';
                $impr = is_numeric($row['impressions'] ?? 0) ? (int)$row['impressions'] : 0;
                $spend = is_numeric($row['spend'] ?? 0) ? (float)$row['spend'] : 0;
                $pivot[$age][$gender]['impressions'] = ($pivot[$age][$gender]['impressions'] ?? 0) + $impr;
                $pivot[$age][$gender]['spend'] = ($pivot[$age][$gender]['spend'] ?? 0) + $spend;
            }

            return view('ads.insights', compact('pivot','data','since','until'));
        } catch (\Exception $e) {
            return back()->with('error',$e->getMessage());
        }
    }
}
