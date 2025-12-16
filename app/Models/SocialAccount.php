<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'account_id',
        'account_name',
        'account_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'permission_level', 
        'granted_permissions',
        'meta_data', 
        'posts_data',
        'avatar',
        'parent_account_id',
        'connected_assets',
        'asset_count',
        'last_synced_at'
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'granted_permissions' => 'array',
        'meta_data' => 'array',
        'posts_data' => 'array',
        'connected_assets' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adAccounts()
    {
        return $this->hasMany(AdAccount::class, 'social_account_id');
    }

    public function parentAccount()
    {
        return $this->belongsTo(SocialAccount::class, 'parent_account_id');
    }

    public function childAccounts()
    {
        return $this->hasMany(SocialAccount::class, 'parent_account_id');
    }

    // Helper method to get Instagram accounts
    public function instagramAccounts()
    {
        return $this->hasMany(SocialAccount::class, 'parent_account_id')
                    ->where('provider', 'instagram');
    }

    // Helper method to get Facebook pages
    public function facebookPages()
    {
        return $this->hasMany(SocialAccount::class, 'parent_account_id')
                    ->where('provider', 'facebook')
                    ->whereNotNull('account_id');
    }

    public function isTokenExpired()
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->isPast();
    }

    public function isTokenExpiringSoon($days = 7)
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return $this->token_expires_at->diffInDays(now()) <= $days;
    }

     public function disconnect()
    {
        Cache::forget('fb_dashboard_' . $this->id);
        Cache::forget('social_account_' . $this->user_id . '_' . $this->provider);
        $this->update([
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
            'updated_at' => now(),
        ]);
        SocialAccount::where('parent_account_id', $this->id)
            ->update([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
            ]);
    }

    public function isConnected()
    {
        return !empty($this->access_token) && !$this->isTokenExpired();
    }
}