<?php

namespace App\Http\View\Composers;
use App\Services\FacebookService;
use Illuminate\Support\Facades\Cache;
use App\Helpers\SocialTokenHelper;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use App\Models\Menu;

class SidebarMenuComposer
{
    

    public function compose(View $view): void
    {
        $user = auth()->user();
        $menus = collect();
        if ($user) {
            $menus = Menu::with([
                    'childrenRecursive' => function ($q) use ($user) {
                        $q->where('display_sidebar_status', 1)
                            ->ordered()
                            ->when(!$user->hasRole('Super Admin ( Developers)'), 
                                fn($q) => $this->applyAccessFilter($q, $user)
                            )
                            ->with(['roles', 'permissions', 'childrenRecursive']);
                    },
                    'roles',
                    'permissions'
                ])
                ->parent()
                ->active()
                ->where('display_sidebar_status', 1)
                ->ordered()
                ->when(!$user->hasRole('Super Admin ( Developers)'), 
                    fn($q) => $this->applyAccessFilter($q, $user)
                )
                ->get();
            $menus = $menus
                ->map(fn($menu) => $this->filterMenuRecursive($menu, $user))
                ->filter();
            $menus->each(fn($menu) => $this->processMenuRecursive($menu));
        }
        //dd(json_encode($menus, JSON_PRETTY_PRINT));
         $view->with('menus', $menus);
    }

    private function filterMenuRecursive($menu, $user)
		{
			$hasAccess = $menu->roles->isEmpty() && $menu->permissions->isEmpty()
				|| $user->hasAnyRole($menu->roles->pluck('name')->toArray())
				|| $user->hasAnyPermission($menu->permissions->pluck('name')->toArray());

			if (!$hasAccess || !$menu->display_sidebar_status) {
				return null;
			}
			if ($menu->childrenRecursive->isNotEmpty()) {
				$menu->childrenRecursive = $menu->childrenRecursive
					->map(fn($child) => $this->filterMenuRecursive($child, $user))
					->filter();
			}

			return $menu;
		}

		private function processMenuRecursive($menu)
		{
			$this->processMenuUrl($menu);

			if ($menu->childrenRecursive->isNotEmpty()) {
				$menu->childrenRecursive->each(fn($child) => $this->processMenuRecursive($child));
			}
		}

		private function processMenuUrl($menuItem)
		{
			if (!$menuItem->url) {
				$menuItem->resolved_url = '#';
				return;
			}
			if (Route::has($menuItem->url)) {
				$route = Route::getRoutes()->getByName($menuItem->url);
				$parameters = $route->parameterNames();
				if (empty($parameters)) {
					$menuItem->resolved_url = route($menuItem->url);
				} else {
					$menuItem->resolved_url = '#';
				}
			} elseif (str_starts_with($menuItem->url, '/') || filter_var($menuItem->url, FILTER_VALIDATE_URL)) {
				$menuItem->resolved_url = url($menuItem->url);
			} else {
				$menuItem->resolved_url = url('/' . ltrim($menuItem->url, '/'));
			}
		}

		private function applyAccessFilter($query, $user)
		{
			$query->where(function ($q) use ($user) {
				$q->doesntHave('roles')->doesntHave('permissions');
				if ($user->roles->count()) {
					$roleIds = $user->roles->pluck('id');
					$q->orWhereHas('roles', fn($roleQuery) => $roleQuery->whereIn('roles.id', $roleIds));
				}
				$userPermissionIds = $user->getAllPermissions()->pluck('id');
				if ($userPermissionIds->count()) {
					$q->orWhereHas('permissions', fn($permQuery) => $permQuery->whereIn('permissions.id', $userPermissionIds));
				}
			});
		}
}