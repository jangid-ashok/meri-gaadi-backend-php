<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RbacApiController extends Controller
{
    public function permissions()
    {
        return response()->json(['data' => Permission::orderBy('module')->orderBy('name')->get(['id', 'name', 'slug', 'module', 'description'])]);
    }

    public function roles()
    {
        return response()->json(['data' => Role::withCount(['users', 'permissions'])->latest()->paginate(15)]);
    }

    public function showRole(Role $role)
    {
        return response()->json(['data' => $role->load('permissions')]);
    }

    public function storeRole(Request $request)
    {
        $data = $this->validated($request);
        $this->guardPermissionSet($request, $data['permissions'] ?? []);
        $role = Role::create($data);
        $role->permissions()->sync($request->input('permissions', []));
        return response()->json(['data' => $role->load('permissions')], 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        abort_if($role->is_system, 403, 'System roles cannot be modified.');
        $data = $this->validated($request, $role);
        $this->guardPermissionSet($request, $data['permissions'] ?? []);
        $role->update($data);
        $role->permissions()->sync($request->input('permissions', []));
        return response()->json(['data' => $role->load('permissions')]);
    }

    public function deleteRole(Role $role)
    {
        abort_if($role->is_system, 403, 'System roles cannot be deleted.');
        $role->delete();
        return response()->noContent();
    }

    public function userRoles(User $user)
    {
        abort_unless($user->isAdmin(), 404);
        return response()->json(['data' => $user->roles()->get(['roles.id', 'name', 'slug', 'description'])]);
    }

    public function assignRoles(Request $request, User $user)
    {
        abort_unless($user->isAdmin(), 404);
        $data = $request->validate(['roles' => ['required', 'array'], 'roles.*' => ['integer', 'distinct', Rule::exists('roles', 'id')]]);
        $roles = Role::whereIn('id', $data['roles'])->get();
        abort_if($roles->contains('slug', 'super-admin') && ! $request->user()->isSuperAdmin(), 403, 'Only a Super Admin can assign Super Admin.');
        if (! $request->user()->isSuperAdmin()) {
            $granted = $roles->load('permissions')->flatMap->permissions->pluck('slug');
            abort_if($granted->contains(fn (string $permission) => ! $request->user()->hasPermission($permission)), 403, 'You cannot grant permissions you do not hold.');
        }
        abort_if($request->user()->is($user) && $roles->doesntContain('slug', 'super-admin') && $request->user()->isSuperAdmin(), 403, 'The last Super Admin cannot be removed.');

        $user->roles()->sync($roles->modelKeys());
        return response()->json(['data' => $user->roles()->get(['roles.id', 'name', 'slug'])]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $request->user()->only(['id', 'name', 'email']), 'roles' => $request->user()->roles()->pluck('slug'), 'permissions' => Permission::whereHas('roles.users', fn ($query) => $query->whereKey($request->user()->id))->pluck('slug')]);
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

    private function guardPermissionSet(Request $request, array $permissionIds): void
    {
        if ($request->user()->isSuperAdmin()) {
            return;
        }

        $allowed = $request->user()->roles()->with('permissions')->get()->flatMap->permissions->modelKeys();
        abort_if(collect($permissionIds)->diff($allowed)->isNotEmpty(), 403, 'You cannot grant permissions you do not hold.');
    }
}