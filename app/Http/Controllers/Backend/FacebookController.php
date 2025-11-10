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
                return redirect()->back()->with('error', 'Facebook account not connected');
            }
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            /* Fetch Facebook Profile */
            $facebookProfile = Http::timeout(10)->get("https://graph.facebook.com/v24.0/{$id}", [
                'fields' => 'name,username,biography,followers_count,follows_count,media_count,profile_picture_url',
                'access_token' => $token,
            ])->json();          
            return view('backend.pages.facebook.fb-summary.fb-report', compact('facebookProfile'));
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

}
