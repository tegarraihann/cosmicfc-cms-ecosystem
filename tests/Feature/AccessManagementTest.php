<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_superadmin_can_open_access_management(): void
    {
        $user = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($user)->get(route('admin.access.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AccessManagement')
                ->has('users', 1)
                ->has('roles', 3)
                ->has('permissions'));
    }

    public function test_regular_admin_cannot_open_access_management(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('admin.access.index'))->assertForbidden();
    }

    public function test_superadmin_can_create_a_role_and_user(): void
    {
        Notification::fake();
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($superAdmin)->post(route('admin.access.roles.store'), [
            'name' => 'Editor Konten',
            'description' => 'Mengelola konten publik.',
            'permissions' => [],
        ])->assertSessionHasNoErrors();

        $role = Role::where('slug', 'editor-konten')->firstOrFail();
        $this->actingAs($superAdmin)->post(route('admin.access.users.store'), [
            'name' => 'Content Editor',
            'email' => 'editor@cosmic.test',
            'role_id' => $role->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => true,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['email' => 'editor@cosmic.test', 'role_id' => $role->id]);
        $created = User::where('email', 'editor@cosmic.test')->firstOrFail();
        $this->assertNull($created->email_verified_at);
        $this->assertNotNull($created->invitation_token);
        Notification::assertSentTo($created, UserInvitationNotification::class);
    }

    public function test_superadmin_cannot_delete_own_account(): void
    {
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();

        $this->actingAs($superAdmin)->delete(route('admin.access.users.destroy', $superAdmin))->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $superAdmin->id, 'deleted_at' => null]);
    }

    public function test_account_active_checkbox_value_is_saved_and_inactive_user_cannot_login(): void
    {
        Notification::fake();
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $role = Role::where('slug', 'admin')->firstOrFail();

        $this->actingAs($superAdmin)->post(route('admin.access.users.store'), [
            'name' => 'Admin Nonaktif',
            'email' => 'inactive@cosmic.test',
            'role_id' => $role->id,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => false,
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'inactive@cosmic.test')->firstOrFail();
        $this->assertFalse($user->is_active);
        $user->forceFill(['password' => 'Password123!', 'email_verified_at' => now()])->save();

        auth()->logout();
        $this->post(route('login'), [
            'email' => 'inactive@cosmic.test',
            'password' => 'Password123!',
        ])->assertSessionHasErrors([
            'email' => 'Akun ini sedang dinonaktifkan.',
        ]);
        $this->assertGuest();
    }

    public function test_custom_role_can_enter_dashboard_when_permission_is_granted(): void
    {
        $role = Role::create(['name' => 'Editor', 'slug' => 'editor', 'is_active' => true]);
        $role->permissions()->attach(Permission::where('slug', 'dashboard.view')->firstOrFail());
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_superadmin_can_toggle_custom_role_active_status_and_permissions(): void
    {
        $superAdmin = User::where('role', UserRole::SuperAdmin)->firstOrFail();
        $role = Role::create(['name' => 'Editor Konten', 'slug' => 'editor-konten', 'is_active' => true]);
        $permission = Permission::where('slug', 'dashboard.view')->firstOrFail();

        $this->actingAs($superAdmin)->put(route('admin.access.roles.update', $role), [
            'name' => $role->name,
            'description' => 'Role pengujian.',
            'is_active' => false,
            'permissions' => [$permission->id],
        ])->assertSessionHasNoErrors();

        $this->assertFalse($role->fresh()->is_active);
        $this->assertTrue($role->fresh()->permissions->contains($permission));

        $this->actingAs($superAdmin)->put(route('admin.access.roles.update', $role), [
            'name' => $role->name,
            'description' => 'Role pengujian.',
            'is_active' => true,
            'permissions' => [],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($role->fresh()->is_active);
        $this->assertCount(0, $role->fresh()->permissions);
    }
}
