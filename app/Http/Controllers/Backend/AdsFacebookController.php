<?php
namespace App\Http\Controllers\Backend;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SocialAccount;
use App\Helpers\SocialTokenHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdsFacebookController extends Controller
{
    public function mainIndex(Request $request, $adAccountId)
    {
        $user = Auth::user();
        $mainAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', 'facebook')
            ->whereNull('parent_account_id')
            ->first();
        if (!$mainAccount) {
            return redirect()->route('facebook.index')->with('error', 'Facebook account not connected');
        }
        $token = SocialTokenHelper::getFacebookToken($mainAccount);
        $adAccount = Http::timeout(10)->get("https://graph.facebook.com/v24.0/me/adaccounts", [
            'fields' => 'id,name,account_status,amount_spent,currency',
            'access_token' => $token,
        ])->json();
        Log::info('Get adds account details: ' . print_r($adAccount, true));
        return view('backend.pages.facebook.ads.index', compact('adAccount'));    
        /* Fetch Instagram account basic info */
    }
}
