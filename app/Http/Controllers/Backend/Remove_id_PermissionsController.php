<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class PermissionsController extends Controller
{
    public function index()
    {   
        try {
            $permissions = Permission::withCount('roles')->orderBy('id','DESC')->get();
            return view('backend.pages.permissions.index', compact('permissions'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load permissions: ' . $e->getMessage());
        }
    }

    public function create() 
    {   
        return view('backend.permissions.create');
    }

    public function store(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:permissions,name|max:255|regex:/^[a-z\-]+$/',
            'guard_name' => 'sometimes|string|max:255'
        ], [
            'name.regex' => 'Permission name should contain only lowercase letters and hyphens.',
            'name.unique' => 'This permission already exists.'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->only('name');
            $data['guard_name'] = $request->guard_name ?? 'web';
            
            Permission::create($data);
            
            return redirect()->route('permissions.index')->with('success','Permission created successfully');
            
        } catch (\Exception $e) {
            Log::error('Permission creation failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to create permission: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Permission $permission)
    {
        try {
            return view('backend.pages.permissions.edit', compact('permission'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to load edit form: ' . $e->getMessage());
        }
    }

    public function update(Request $request, Permission $permission)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'max:255',
                'regex:/^[a-z\-]+$/',
                Rule::unique('permissions')->ignore($permission->id)
            ],
            'guard_name' => 'sometimes|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->only('name');
            if ($request->has('guard_name')) {
                $data['guard_name'] = $request->guard_name;
            }
            
            $permission->update($data);
            
            return redirect()->route('permissions.index')->with('success','Permission updated successfully');
          
        } catch (\Exception $e) {
            Log::error('Permission update failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update permission: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            // Check if permission is being used by any role
            if ($permission->roles()->count() > 0) {
                return redirect()->back()->with('error', 'Cannot delete permission that is assigned to roles.');
            }
            
            $permission->delete();
            
            return redirect()->route('permissions.index')->with('success','Permission deleted successfully');
            
        } catch (\Exception $e) {
            Log::error('Permission deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }
}