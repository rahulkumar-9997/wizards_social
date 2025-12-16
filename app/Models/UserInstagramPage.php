<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserInstagramPage extends Model
{
    protected $table = 'user_instagram_pages';
    protected $fillable = [
        'id',
        'user_id',
        'instagram_id',
        'account_name',
        'user_name',
        'access_token',
        'profile_picture',
        'followers_count',
        'connected_page',
        'last_synced_at'
    ];
    protected $casts = [
        'last_synced_at' => 'datetime',
    ];
    /* ================= RELATIONSHIPS ================= */

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /* Instagram → Connected Facebook Page */
    public function facebookPage()
    {
        return $this->belongsTo(
            UserFacebookPage::class,
            'connected_page',
            'name'
        );
    }

    /* Optional: SocialAccount link */
    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class, 'instagram_id', 'account_id');
    }
}
