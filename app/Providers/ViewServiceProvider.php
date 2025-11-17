<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Models\Menu;
use App\Http\View\Composers\SidebarComposer;
use App\Http\View\Composers\SidebarMenuComposer;
use App\Providers\MenuServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('backend.pages.layouts.second-sidebar', SidebarComposer::class);       
        View::composer('backend.pages.layouts.sidebar', SidebarMenuComposer::class);       
    }    
}