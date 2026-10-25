<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_staff_are_available_to_admin_and_public_pages(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.staff.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/Staff')->has('staff.data', 8));
        $this->get(route('home'))->assertOk()->assertInertia(fn ($page) => $page->has('staffData.people', 4)->where('staffData.founder.name', 'Nama Founder'));
        $this->get(route('team'))->assertOk()->assertInertia(fn ($page) => $page->has('staffData.coaching', 3)->has('staffData.support', 2));
    }

    public function test_setting_primary_founder_replaces_the_previous_founder(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $member = Staff::where('slug', 'club-manager')->firstOrFail();
        $payload = $this->payload($member, ['is_primary_founder' => true]);
        $this->actingAs($admin)->put(route('admin.staff.update', $member), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff_assignments', ['staff_id' => $member->id, 'is_primary_founder' => true]);
        $this->assertSame(1, StaffAssignment::where('is_primary_founder', true)->count());
    }

    public function test_fifth_featured_staff_is_rejected(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $member = Staff::where('slug', 'rian-prakoso')->firstOrFail();
        $this->actingAs($admin)->put(route('admin.staff.update', $member), $this->payload($member, ['is_featured' => true, 'featured_order' => 4]))->assertSessionHasErrors('is_featured');
    }

    public function test_regular_admin_cannot_delete_staff(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();
        $this->actingAs($admin)->delete(route('admin.staff.destroy', Staff::first()))->assertForbidden();
    }

    public function test_staff_slug_is_generated_automatically_and_checkbox_values_are_saved(): void
    {
        $admin = User::where('email', 'admin@cosmic.test')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.staff.store'), [
            'full_name' => 'Dimas Pratama',
            'display_name' => 'Coach Dimas',
            'hometown' => 'Pekanbaru',
            'biography' => 'Pelatih pengembangan pemain.',
            'primary_media_id' => null,
            'image_focal_x' => 50,
            'image_focal_y' => 40,
            'status' => 'active',
            'role_title' => 'Assistant Coach',
            'category' => 'coaching',
            'short_description' => 'Mendampingi persiapan tim.',
            'badge' => 'Coach',
            'starts_at' => null,
            'ends_at' => null,
            'is_primary_founder' => false,
            'is_featured' => false,
            'featured_order' => null,
            'is_public' => false,
            'assignment_status' => 'active',
        ])->assertSessionHasNoErrors();

        $staff = Staff::where('slug', 'coach-dimas')->firstOrFail();
        $assignment = $staff->assignments()->latest('id')->firstOrFail();

        $this->assertFalse($assignment->is_primary_founder);
        $this->assertFalse($assignment->is_featured);
        $this->assertFalse($assignment->is_public);
    }

    private function payload(Staff $staff, array $changes = []): array
    {
        $assignment = $staff->assignments()->latest('id')->firstOrFail();

        return array_merge(['full_name' => $staff->full_name, 'display_name' => $staff->display_name, 'slug' => $staff->slug, 'hometown' => $staff->hometown, 'biography' => $staff->biography, 'primary_media_id' => $staff->primary_media_id, 'image_focal_x' => 50, 'image_focal_y' => 40, 'status' => 'active', 'role_title' => $assignment->role_title, 'category' => $assignment->category, 'short_description' => $assignment->short_description, 'badge' => $assignment->badge, 'starts_at' => null, 'ends_at' => null, 'is_primary_founder' => $assignment->is_primary_founder, 'is_featured' => $assignment->is_featured, 'featured_order' => $assignment->featured_order, 'is_public' => true, 'assignment_status' => 'active'], $changes);
    }
}
