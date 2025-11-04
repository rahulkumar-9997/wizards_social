<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SocialAccount;
use App\Helpers\SocialTokenHelper;
use Exception;

class AdsFacebookController extends Controller
{
    public function mainIndex(Request $request)
    {
        try {
            $user = Auth::user();
            $mainAccount = SocialAccount::where('user_id', $user->id)
                ->where('provider', 'facebook')
                ->whereNull('parent_account_id')
                ->first();

            if (!$mainAccount) {
                return redirect()->route('facebook.index')->with('error', 'Facebook account not connected.');
            }
            $token = SocialTokenHelper::getFacebookToken($mainAccount);
            $response = Http::timeout(10)->get('https://graph.facebook.com/v24.0/me/adaccounts', [
                'fields' => 'id,name,account_status,amount_spent,currency',
                'access_token' => $token,
            ]);
            if ($response->failed()) {
                $error = $response->json('error.message') ?? $response->body();
                throw new Exception('Facebook API error: ' . $error);
            }
            $adAccount = $response->json();
            Log::info('Fetched Facebook Ad Account details', ['user_id' => $user->id, 'data' => $adAccount]);
            return view('backend.pages.facebook.ads.index', compact('adAccount'));

        } catch (Exception $e) {
            Log::error('Facebook Ads Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
}
