<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Inertia\Inertia;

class RolePermissionController extends Controller
{
    public function index()
    {
        // Don't show super_admin role in the toggle list to prevent self-lockout
        $roles = Role::with('permissions')->where('name', '!=', 'super_admin')->get();
        $permissions = Permission::all();

        return Inertia::render('Admin/RolePermissions', [
            'roles' => $roles,
            'permissions' => $permissions
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id']
        ]);

        $role->permissions()->sync($request->permissions);

        return back()->with('success', 'Permissions updated successfully.');
    }
}
