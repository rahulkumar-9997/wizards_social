<?php

namespace App\Helpers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SocialTokenHelper
{
    /**
     * Safely get Facebook/Instagram token from SocialAccount
     *
     * @param object $account
     * @return string
     * @throws Exception
     */
    public static function getFacebookToken($account)
    {
        try {
            if (empty($account->access_token)) {
                throw new Exception('Empty token');
            }
            try {
                $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($account->access_token);
                $data = json_decode($decrypted, true);
                return $data['token'] ?? $decrypted;
            } catch (Exception $e) {
                /* Fallback if token is already plain JSON string */
                $data = json_decode($account->access_token, true);
                return $data['token'] ?? $account->access_token;
            }
        } catch (Exception $e) {
            throw new Exception('Token fetch failed: ' . $e->getMessage());
        }
    }

    public static function getFacebookPageToken($userToken, $pageId)
    {
        try {
            $response = Http::timeout(30)->get("https://graph.facebook.com/v24.0/{$pageId}", [
                'fields' => 'access_token',
                'access_token' => $userToken
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }            
            Log::error('Failed to get Facebook page token: ' . $response->body());
            return null;
            
        } catch (Exception $e) {
            Log::error('Error getting Facebook page token: ' . $e->getMessage());
            return null;
        }
    }
    
}
