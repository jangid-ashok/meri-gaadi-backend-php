<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Services\AuditLogger;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => ['nullable', Rule::in(['active', 'inactive'])], 'role' => ['nullable', 'integer', Rule::exists('roles', 'id')]]);
        $users = $this->paginateRequest($request, $this->query($request));
        if ($request->expectsJson()) {
            $users->getCollection()->transform(fn (User $user) => $this->resource($user));
            return response()->json(['data' => $users]);
        }
        return view('admin.users.index', ['users' => $users, 'roles' => $this->assignableRoles($request->user()), 'page_title' => 'Users', 'leftMenuActive' => 'users']);
    }

    public function create(Request $request)
    {
        return view('admin.users.form', ['user' => new User(['status' => 'active']), 'roles' => $this->assignableRoles($request->user()), 'page_title' => 'Add User', 'leftMenuActive' => 'users']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $role = $this->assignableRole($request->user(), $data['role_id']);
        $data['password'] = Hash::make($data['password']);
        unset($data['password_confirmation'], $data['role_id']);
        $user = User::create($data + ['is_admin' => true]);
        $user->roles()->sync([$role->id]);
        app(AuditLogger::class)->record('permission_changed', 'User', 'Assigned role "'.$role->name.'" to admin "'.$user->name.'"', $user, [], ['roles' => [$role->slug]]);
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($user->fresh()->load('roles'))], 201);
        return redirect()->route('admin.users.show', $user)->with('status', 'User created successfully.');
    }

    public function show(Request $request, User $user)
    {
        abort_unless($user->is_admin, 404);
        $user->load('roles');
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($user)]);
        return view('admin.users.show', ['user' => $user, 'page_title' => $user->name, 'leftMenuActive' => 'users']);
    }

    public function edit(Request $request, User $user)
    {
        abort_unless($user->is_admin, 404);
        $user->load('roles');
        return view('admin.users.form', ['user' => $user, 'roles' => $this->assignableRoles($request->user()), 'page_title' => 'Edit User', 'leftMenuActive' => 'users']);
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->is_admin, 404);
        $data = $this->validated($request, $user, false);
        $role = $this->assignableRole($request->user(), $data['role_id']);
        $this->protectSuperAdmin($user, $role, $data['status']);
        $oldRoles = $user->roles()->pluck('slug')->all();
        if (! empty($data['password'])) $data['password'] = Hash::make($data['password']);
        else unset($data['password']);
        unset($data['role_id']);
        $user->update($data);
        $user->roles()->sync([$role->id]);
        if ($oldRoles !== [$role->slug]) app(AuditLogger::class)->record('permission_changed', 'User', 'Changed roles for admin "'.$user->name.'"', $user, ['roles' => $oldRoles], ['roles' => [$role->slug]]);
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($user->fresh()->load('roles'))]);
        return redirect()->route('admin.users.show', $user)->with('status', 'User updated successfully.');
    }

    public function status(Request $request, User $user)
    {
        abort_unless($user->is_admin, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        $this->protectSuperAdmin($user, $user->roles()->first(), $data['status']);
        $user->update($data);
        if ($request->expectsJson()) return response()->json(['data' => $this->resource($user->fresh()->load('roles'))]);
        return back()->with('status', 'User status updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->is_admin, 404);
        abort_if($request->user()->is($user), 409, 'You cannot delete your own account.');
        $this->protectSuperAdmin($user, null, null, true);
        $user->roles()->detach();
        $user->delete();
        if ($request->expectsJson()) return response()->noContent();
        return redirect()->route('admin.users.index')->with('status', 'User deleted successfully.');
    }

    private function query(Request $request)
    {
        return User::query()->where('is_admin', true)->with('roles:id,name,slug')
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%')->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->when($request->filled('role'), fn ($q) => $q->whereHas('roles', fn ($r) => $r->whereKey($request->input('role'))))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest('id');
    }

    private function validated(Request $request, ?User $user = null, bool $passwordRequired = true): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$passwordRequired ? 'required' : 'nullable', 'confirmed', Password::defaults()],
        ]);
    }

    private function assignableRoles(User $actor)
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        if ($actor->isSuperAdmin()) return $roles;
        $held = $actor->roles()->with('permissions')->get()->flatMap->permissions->pluck('slug')->unique();
        return $roles->reject(fn ($role) => $role->slug === 'super-admin' || $role->permissions->contains(fn ($p) => ! $held->contains($p->slug)))->values();
    }

    private function assignableRole(User $actor, int $roleId): Role
    {
        $role = $this->assignableRoles($actor)->firstWhere('id', $roleId);
        abort_unless($role, 403, 'You cannot assign that role.');
        return $role;
    }

    private function protectSuperAdmin(User $user, ?Role $replacementRole, ?string $status, bool $deleting = false): void
    {
        $isSuper = $user->roles()->where('slug', 'super-admin')->exists();
        if ($status === 'inactive' && auth()->user()->is($user)) abort(409, 'You cannot deactivate your own account.');
        if (! $isSuper) return;
        $removing = $deleting || $status === 'inactive' || ($replacementRole && $replacementRole->slug !== 'super-admin');
        if (! $removing) return;
        abort_if(auth()->user()->is($user), 409, 'You cannot disable or remove your own Super Admin access.');
        $remaining = User::where('is_admin', true)->where('status', 'active')->whereKeyNot($user->id)->whereHas('roles', fn ($q) => $q->where('slug', 'super-admin'))->exists();
        abort_unless($remaining, 409, 'The last active Super Admin cannot be deactivated, deleted, or reassigned.');
    }

    private function resource(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'status', 'created_at', 'updated_at']) + ['role' => $user->roles->map(fn ($role) => $role->only(['id', 'name', 'slug']))->first()];
    }
}
