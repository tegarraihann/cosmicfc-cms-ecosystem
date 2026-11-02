<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

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
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'invitation_expires_at' => $user->invitation_expires_at?->toIso8601String(),
                'invitation_status' => $this->invitationStatus($user),
            ]),
            'roles' => Role::with('permissions')->withCount('users')->orderByDesc('is_system')->get(),
            'permissions' => Permission::orderBy('group')->orderBy('name')->get()->groupBy('group'),
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'unique:users,email', 'unique:users,pending_email'], 'role_id' => ['required', 'exists:roles,id'], 'is_active' => ['required', 'boolean']]);
        $role = Role::findOrFail($data['role_id']);
        abort_if($role->slug === 'superadmin', 422, 'Akun superadmin tambahan tidak dapat dibuat melalui undangan.');
        try {
            DB::transaction(function () use ($data, $role): void {
                $token = Str::random(64);
                $user = User::create([
                    ...$data,
                    'role' => $this->legacyRole($role),
                    'password' => Str::random(64),
                    'email_verified_at' => null,
                    'invitation_token' => hash('sha256', $token),
                    'invitation_expires_at' => now()->addHours(24),
                    'invited_at' => now(),
                ]);
                $user->notify(new UserInvitationNotification($token));
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'Email undangan gagal dikirim. Periksa konfigurasi email lalu coba kembali.']);
        }

        return back()->with('success', 'Pengguna berhasil ditambahkan dan email undangan telah dikirim.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isSuperAdmin() && $user->id !== $request->user()->id, 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', Rule::unique('users')->ignore($user)], 'role_id' => ['required', 'exists:roles,id'], 'password' => ['nullable', 'min:8', 'confirmed'], 'is_active' => ['required', 'boolean']]);
        $role = Role::findOrFail($data['role_id']);
        abort_if(strtolower($data['email']) !== strtolower($user->email), 422, 'Email tidak dapat diubah dari Kelola Akses. Pemilik akun harus mengubah dan memverifikasinya melalui halaman Profil.');
        abort_if($user->id === $request->user()->id && (! $data['is_active'] || $role->slug !== 'superadmin'), 422, 'Akun sendiri harus tetap aktif sebagai superadmin.');
        if (blank($data['password'] ?? null)) {
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

    public function resendInvitation(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasVerifiedEmail(), 422, 'Akun ini sudah terverifikasi.');
        abort_if($user->isSuperAdmin(), 422, 'Undangan tidak digunakan untuk akun superadmin.');

        $token = Str::random(64);
        try {
            DB::transaction(function () use ($user, $token): void {
                $user->forceFill([
                    'invitation_token' => hash('sha256', $token),
                    'invitation_expires_at' => now()->addHours(24),
                    'invited_at' => now(),
                ])->save();
                $user->notify(new UserInvitationNotification($token));
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors(['email' => 'Undangan gagal dikirim ulang. Periksa konfigurasi email lalu coba kembali.']);
        }

        return back()->with('success', 'Undangan aktivasi berhasil dikirim ulang.');
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

    private function invitationStatus(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            return $user->is_active ? 'active' : 'inactive';
        }

        return $user->invitation_expires_at?->isPast() ? 'expired' : 'pending';
    }
}
