<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFacebookPage extends Model
{
    protected $table = 'user_facebook_page';
    protected $fillable = [
        'id',
        'user_id',
        'facebook_page_id',
        'name',
        'category',
        'access_token',
        'profile_picture',
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

    /* Optional: link to SocialAccount */
    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class, 'facebook_page_id', 'account_id');
    }
}
