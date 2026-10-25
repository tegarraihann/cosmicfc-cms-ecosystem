<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccessManagementController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/AccessManagement', [
            'users' => User::with('roleRelation')->latest()->get()->map(fn (User $user) => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                'role_id' => $user->role_id, 'role' => $user->roleRelation?->name ?? $user->role?->label(),
                'role_slug' => $user->roleRelation?->slug ?? $user->role?->value,
                'is_active' => $user->is_active, 'last_login_at' => $user->last_login_at?->diffForHumans(),
            ]),
            'roles' => Role::with('permissions')->withCount('users')->orderByDesc('is_system')->get(),
            'permissions' => Permission::orderBy('group')->orderBy('name')->get()->groupBy('group'),
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email'], 'role_id' => ['required', 'exists:roles,id'], 'password' => ['required', 'min:8', 'confirmed'], 'is_active' => ['required', 'boolean']]);
        $role = Role::findOrFail($data['role_id']);
        User::create([...$data, 'role' => $this->legacyRole($role), 'email_verified_at' => now()]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin() && $user->id !== $request->user()->id, 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('users')->ignore($user)], 'role_id' => ['required', 'exists:roles,id'], 'password' => ['nullable', 'min:8', 'confirmed'], 'is_active' => ['required', 'boolean']]);
        $role = Role::findOrFail($data['role_id']);
        abort_if($user->id === $request->user()->id && (! $data['is_active'] || $role->slug !== 'superadmin'), 422, 'Akun sendiri harus tetap aktif sebagai superadmin.');
        if (blank($data['password'])) {
            unset($data['password']);
        }
        $user->update([...$data, 'role' => $this->legacyRole($role)]);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id || $user->isSuperAdmin(), 422, 'Pengguna ini tidak dapat dihapus.');
        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);
        $role = Role::create(['name' => $data['name'], 'slug' => Str::slug($data['name']), 'description' => $data['description'] ?? null, 'is_active' => true]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return back()->with('success', 'Role berhasil dibuat.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->slug === 'superadmin', 422, 'Role superadmin tidak dapat diubah.');
        $data = $this->validateRole($request, $role);
        $role->update(['name' => $data['name'], 'description' => $data['description'] ?? null, 'is_active' => $data['is_active'] ?? false]);
        $role->permissions()->sync($data['permissions'] ?? []);

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        abort_if($role->is_system || $role->users()->exists(), 422, 'Role sistem atau role yang sedang digunakan tidak dapat dihapus.');
        $role->delete();

        return back()->with('success', 'Role berhasil dihapus.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('roles')->ignore($role)], 'description' => ['nullable', 'string', 'max:500'], 'is_active' => ['boolean'], 'permissions' => ['array'], 'permissions.*' => ['exists:permissions,id']]);
    }

    private function legacyRole(Role $role): UserRole
    {
        return match ($role->slug) {
            'superadmin' => UserRole::SuperAdmin, 'admin' => UserRole::Admin, default => UserRole::Member
        };
    }
}
