<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'account_id',
        'account_name',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'permission_level', 
        'granted_permissions',
        'meta_data', 
        'posts_data'   
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'granted_permissions' => 'array',
        'meta_data' => 'array',
        'posts_data' => 'array',      
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function adAccounts()
    {
        return $this->hasMany(AdAccount::class, 'social_account_id');
    }
}
