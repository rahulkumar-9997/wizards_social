<?php

namespace App\Helpers;

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
}
