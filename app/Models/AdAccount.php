<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'social_account_id', 
        'provider', 
        'ad_account_id', 
        'ad_account_name', 
        'amount_spent',
        'balance', 
        'meta_data' 
    ];

    protected $casts = [
        'amount_spent' => 'decimal:2',
        'balance' => 'decimal:2',
        'meta_data' => 'array',
    ];

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
