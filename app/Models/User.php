<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'user_type',
        'user_id',
        'password',
        'profile_img',
        'phone_number',
        'gender',
        'address',        
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's role names
     */
    public function getRoleNamesAttribute()
    {
        return $this->getRoleNames();
    }

    /* User Facebook Pages*/
    public function facebookPages()
    {
        return $this->hasMany(UserFacebookPage::class, 'user_id');
    }

    /* User Instagram Pages*/
    public function instagramPages()
    {
        return $this->hasMany(UserInstagramPage::class, 'user_id');
    }

    /* User Social Accounts */
    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class, 'user_id');
    }
}