<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use Spatie\Permission\Models\Role;

class MenuSeeder extends Seeder
{
    public function run()
    {
        // ---------------- Roles ----------------
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin ( Developers)', 'guard_name' => 'web']);
        $mainAdmin = Role::firstOrCreate(['name' => 'Main Admin (Owner)', 'guard_name' => 'web']);

        // ---------------- Dashboard ----------------
        $dashboard = Menu::updateOrCreate([
            'name' => 'Dashboard',
            'url' => 'dashboard',
            'icon' => 'solar:widget-5-bold-duotone',
            'order' => 1,
            'is_active' => 1,
        ]);
        $dashboard->roles()->sync([$superAdmin->id, $mainAdmin->id]);

        // ---------------- Products ----------------
        $menu = Menu::updateOrCreate([
            'name' => 'Manage Menu',
            'url' => 'menus',
            'icon' => 'solar:t-shirt-bold-duotone',
            'order' => 2,
            'is_active' => 1,
        ]);
        $menu->roles()->sync([$superAdmin->id, $mainAdmin->id]);      

        // ---------------- Manage Users ----------------
        $users = Menu::updateOrCreate([
            'name' => 'Manage Users',
            'url' => '#',
            'icon' => 'solar:card-send-bold-duotone',
            'order' => 3,
            'is_active' => 1,
        ]);
        $users->roles()->sync([$superAdmin->id, $mainAdmin->id]);

        $usersSubMenu = [
            ['name' => 'User', 'url' => 'users', 'icon' => 'solar:user-bold-duotone'],
            ['name' => 'Roles & Assign Menu', 'url' => 'roles', 'icon' => 'solar:settings-bold-duotone'],
        ];

        foreach ($usersSubMenu as $index => $submenu) {
            $child = Menu::updateOrCreate([
                'name' => $submenu['name'],
                'url' => $submenu['url'],
                'icon' => $submenu['icon'] ?? null,
                'order' => $index + 1,
                'parent_id' => $users->id,
                'is_active' => 1,
            ]);
            $child->roles()->sync([$superAdmin->id, $mainAdmin->id]);
        }
        $socialMediaMenus = [
            [
                'name' => 'Facebook',
                'url' => 'facebook',
                'icon' => 'solar:facebook-bold-duotone',
                'order' => 4,
            ],
            [
                'name' => 'Instagram', 
                'url' => 'instagram',
                'icon' => 'solar:instagram-bold-duotone', 
                'order' => 5,
            ],
            [
                'name' => 'Youtube',
                'url' => 'youtube', 
                'icon' => 'solar:youtube-bold-duotone', 
                'order' => 6,
            ],
        ];

        foreach ($socialMediaMenus as $menuData) {
            $menuItem = Menu::updateOrCreate([
                'name' => $menuData['name'],
                'url' => $menuData['url'],
                'icon' => $menuData['icon'],
                'order' => $menuData['order'],
                'is_active' => 1,
            ]);
            $menuItem->roles()->sync([$superAdmin->id, $mainAdmin->id]);
        }
    }
}