<?php
namespace App\Http\Controllers\Backend;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Menu;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::with('menus')->latest()->paginate(10);
        return view('backend.pages.manage-user.roles.index', compact('roles'));
    }

    public function create()
    {
        // $permissions = Permission::all()->groupBy('module');
        // return view('backend.manage-user.roles.create', compact('permissions'));
        $menus = Menu::with('children')->whereNull('parent_id')->where('is_active', 1)->orderBy('order')->get();
        return view('backend.pages.manage-user.roles.create', compact('menus'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255|unique:roles,name',
    //         'permissions' => 'nullable|array',
    //         'permissions.*' => 'exists:permissions,id',
    //     ]);
    //     $role = Role::create([
    //         'name' => $request->name,
    //         'guard_name' => 'web'
    //     ]);
    //     if ($request->has('permissions')) {
    //         $permissions = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
    //         $role->syncPermissions($permissions);
    //     }
    //     return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'menus' => 'nullable|array',
            'menus.*' => 'exists:menus,id',
        ]);
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);
        if ($request->filled('menus')) {
            $role->menus()->sync($request->menus);
        }
        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }



    public function show(Role $role)
    {
        $role->load('permissions');
        return view('backend.pages.manage-user.roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        //dd(get_class(new Role));
        // $permissions = Permission::all()->groupBy('module'); 
        // $rolePermissions = $role->permissions->pluck('id')->toArray();
        // return view('backend.manage-user.roles.edit', compact('role', 'permissions', 'rolePermissions'));
        $menus = Menu::with('children')->whereNull('parent_id')->where('is_active', 1)->orderBy('order')->get();
        $assignedMenus = $role->menus()->pluck('menus.id')->toArray();
        return view('backend.pages.manage-user.roles.edit', compact('role', 'menus', 'assignedMenus'));
    }

    /*public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);
        $role->name = $request->name;
        $role->save();
        if ($request->has('permissions')) {
            $permissionNames = Permission::whereIn('id', $request->permissions)->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);
        } else {
            $role->syncPermissions([]);
        }
        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }*/

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'menus' => 'nullable|array',
            'menus.*' => 'exists:menus,id',
        ]);
        $role->update(['name' => $request->name]);
        $role->menus()->sync($request->menus ?? []);
        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['Super Admin (Wizards)', 'admin'])) {
            return redirect()->route('roles.index')->with('error', 'This role cannot be deleted.');
        }
        if ($role->users()->count() > 0) {
            return redirect()->route('roles.index')->with('error', 'Cannot delete role assigned to users.');
        }
        if (method_exists($role, 'menus')) {
            $role->menus()->sync([]);
        }
        if (method_exists($role, 'permissions')) {
            $role->syncPermissions([]);
        }
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }


}