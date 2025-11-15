<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Menu extends Model
{
    protected $fillable = [
        'name', 
        'url', 
        'icon', 
        'parent_id', 
        'order', 
        'is_active',
        'display_sidebar_status'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'display_sidebar_status' => 'boolean',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'menu_role');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'menu_permission');
    }

    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeParent($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }
}