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


}
