<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
    ];
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'menu_role');
    }
}
