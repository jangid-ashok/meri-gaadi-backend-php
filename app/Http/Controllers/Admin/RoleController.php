<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['users', 'permissions'])->latest()->paginate(15);
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.form', ['role' => new Role(), 'permissions' => Permission::orderBy('module')->orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->guardPermissionSet($request, $data['permissions'] ?? []);
        $role = Role::create($data);
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.roles.show', $role)->with('status', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        return view('admin.roles.show', ['role' => $role->load(['permissions', 'users'])]);
    }

    public function edit(Role $role)
    {
        $this->guardSystemRole($role);
        return view('admin.roles.form', ['role' => $role, 'permissions' => Permission::orderBy('module')->orderBy('name')->get()]);
    }

    public function update(Request $request, Role $role)
    {
        $this->guardSystemRole($role);
        $data = $this->validated($request, $role);
        $this->guardPermissionSet($request, $data['permissions'] ?? []);
        $role->update($data);
        $role->permissions()->sync($request->input('permissions', []));

        return redirect()->route('admin.roles.show', $role)->with('status', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $this->guardSystemRole($role);
        $role->delete();
        return redirect()->route('admin.roles.index')->with('status', 'Role deleted successfully.');
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('roles', 'slug')->ignore($role)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'distinct', Rule::exists('permissions', 'id')],
        ]);
    }

    private function guardSystemRole(Role $role): void
    {
        abort_if($role->is_system, 403, 'System roles cannot be modified.');
    }

    private function guardPermissionSet(Request $request, array $permissionIds): void
    {
        if ($request->user()->isSuperAdmin()) {
            return;
        }

        $allowed = $request->user()->roles()->with('permissions')->get()->flatMap->permissions->modelKeys();
        abort_if(collect($permissionIds)->diff($allowed)->isNotEmpty(), 403, 'You cannot grant permissions you do not hold.');
    }
}