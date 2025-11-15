<?php
namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Menu;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
class MenuController extends Controller
{
    public function index()
    {
        $menus = Menu::query()
            ->with(['children' => function($q) {
                $q->active()->orderBy('order');
            }, 'roles'])
            ->parent()
            ->orderBy('order')
            ->paginate(20);

        $roles = Role::all();
        return view('backend.pages.manage-user.menus.index', compact('menus', 'roles'));
    }

    public function create()
    {
        $parentMenus = Menu::whereNull('parent_id')->active()->ordered()->get();
        $roles = Role::all();
        $lastOrder = Menu::max('order') ?? 0;
        $nextOrder = $lastOrder + 1;
        $icons = $this->getMenuIcons();
        return view('backend.pages.manage-user.menus.create', compact('parentMenus', 'roles', 'nextOrder', 'icons'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'is_active' => $request->has('is_active'),
            'display_sidebar_status' => $request->has('display_sidebar_status'),
        ]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'display_sidebar_status' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);
        DB::beginTransaction();
        try {
            $menu = Menu::create([
                'name' => $validated['name'],
                'url' => $validated['url'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'order' => $validated['order'],
                'is_active' => $request->has('is_active'),
                'display_sidebar_status' => $request->has('display_sidebar_status'),
            ]);
            if (!empty($validated['roles'])) {
                $menu->roles()->sync($validated['roles']);
            }
            DB::commit();
            return redirect()->route('menus.index')->with('success', 'Menu created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Menu creation failed: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $menu = Menu::findOrFail($id);
        $parentMenus = Menu::whereNull('parent_id')->where('id', '!=', $id)->ordered()->get();
        $roles = Role::all();
        $icons = $this->getMenuIcons();
        return view('backend.pages.manage-user.menus.edit', compact('menu', 'parentMenus', 'roles', 'icons'));
    }

    public function update(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $request->merge([
            'is_active' => $request->has('is_active'),
            'display_sidebar_status' => $request->has('display_sidebar_status'),
        ]);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'display_sidebar_status' => 'boolean',
            'roles' => 'nullable|array',
            'roles.*' => 'exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            $menu->update([
                'name' => $validated['name'],
                'url' => $validated['url'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'parent_id' => $validated['parent_id'] ?? null,
                'order' => $validated['order'],
                'is_active' => $request->has('is_active'),
                'display_sidebar_status' => $request->has('display_sidebar_status'),
            ]);
            if (!empty($validated['roles'])) {
                $menu->roles()->sync($validated['roles']);
            } else {
                $menu->roles()->detach();
            }
            DB::commit();
            return redirect()->route('menus.index')->with('success', 'Menu updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Menu update failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', $e->getMessage());
        }
    }


    public function destroy(Menu $menu)
    {
        try {
            //return response()->json($menu->roles());
            DB::beginTransaction();
            if ($menu->roles()->exists()) {
                return redirect()->route('menus.index')->with('error', 'Cannot delete menu because it is assigned to one or more roles.');
            }
            if ($menu->children()->count() > 0) {
                foreach ($menu->children as $child) {
                    $this->deleteChildren($child);
                }
            }
            $menu->roles()->detach();
            $menu->delete();
            DB::commit();
            return redirect()->route('menus.index')->with('success', 'Menu and its sub-menus deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Menu deletion failed: ' . $e->getMessage());
            return redirect()->route('menus.index')->with('error', $e->getMessage());
        }
    }

    private function deleteChildren($menu)
    {
        
        if ($menu->roles()->exists()) {
            throw new \Exception("Cannot delete '{$menu->name}' because it is assigned to a role.");
        }
        foreach ($menu->children as $child) {
            $this->deleteChildren($child);
        }
        $menu->roles()->detach();
        $menu->delete();
    }

    public function updateStatus(Request $request, Menu $menu)
    {
        try {
            $request->validate([
                'is_active' => 'required|boolean',
            ]);
            $menu->is_active = $request->is_active;
            $menu->save();
            return response()->json([
                'status' => true,
                'message' => 'Menu status updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Menu status update failed: '.$e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function reorder(Request $request)
    {
        $order = $request->order;
        foreach ($order as $index => $id) {
            Menu::where('id', $id)->update(['order' => $index + 1]);
        }
        return response()->json(['status' => true, 'message' => 'Menu order updated successfully.']);
    }

    public function updateSidebarStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'display_sidebar_status' => 'required|boolean',
            ]);
            $menu = Menu::findOrFail($id);
            
            $menu->display_sidebar_status = $request->display_sidebar_status;
            $menu->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Menu sidebar visibility updated successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Menu sidebar status update failed: '.$e->getMessage());
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function getMenuIcons()
    {
        return [
            'solar:home-2-bold-duotone' => '🏠 Home',
            'solar:chart-square-bold-duotone' => '📊 Dashboard',
            'solar:graph-up-bold-duotone' => '📈 Analytics',
            'solar:pie-chart-3-bold-duotone' => '🥧 Reports',
            'solar:user-bold-duotone' => '👤 User',
            'solar:users-group-rounded-bold-duotone' => '👥 Users Group',
            'solar:shield-keyhole-bold-duotone' => '🔒 Roles & Permissions',
            'solar:box-bold-duotone' => '📦 Products',
            'solar:bag-3-bold-duotone' => '🛍️ Orders',
            'solar:barcode-bold-duotone' => '🏷️ Categories',
            'solar:cart-bold-duotone' => '🛒 Cart',
            'solar:store-bold-duotone' => '🏬 Inventory',
            'solar:wallet-bold-duotone' => '💰 Wallet',
            'solar:credit-card-bold-duotone' => '💳 Payments',
            'solar:bill-list-bold-duotone' => '🧾 Invoices',
            'solar:chat-round-dots-bold-duotone' => '💬 Messages',
            'solar:envelope-bold-duotone' => '📧 Email',
            'solar:bell-bold-duotone' => '🔔 Notifications',
            'solar:settings-bold-duotone' => '⚙️ Settings',
            'solar:slider-vertical-bold-duotone' => '🎚️ Controls',
            'solar:globe-bold-duotone' => '🌐 Website',
            'solar:file-text-bold-duotone' => '📄 Documents',
            'solar:clipboard-list-bold-duotone' => '🗒️ Tasks',
            'solar:book-2-bold-duotone' => '📘 Knowledge Base',
            'solar:calendar-bold-duotone' => '📅 Calendar',
            'solar:location-bold-duotone' => '📍 Locations',
            'solar:heart-bold-duotone' => '❤️ Favorites',
            'solar:star-bold-duotone' => '⭐ Ratings',
            'solar:trash-bin-minimalistic-bold-duotone' => '🗑️ Trash',
            'ti ti-tie' => '👔 Salesman',
            'solar:menu-dots-bold-duotone' => '⋮ Default',
        ];
    }

}