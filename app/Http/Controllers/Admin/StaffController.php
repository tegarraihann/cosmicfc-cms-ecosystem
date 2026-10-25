<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubProfile;
use App\Models\Media;
use App\Models\MediaUsage;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\StaffAudit;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $staff = Staff::with(['photo', 'assignments' => fn ($query) => $query->latest('id')])
            ->when($request->string('search')->toString(), fn ($query, $search) => $query->where(fn ($nested) => $nested->where('display_name', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%")))
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->string('category')->toString(), fn ($query, $category) => $query->whereHas('assignments', fn ($assignment) => $assignment->where('category', $category)->where('status', 'active')))
            ->latest()->paginate(20)->withQueryString()->through(fn ($member) => $this->formData($member));

        return Inertia::render('Admin/Staff', ['staff' => $staff, 'filters' => $request->only(['search', 'status', 'category']), 'media' => Media::where('status', 'active')->latest()->get()->map(fn ($item) => ['id' => $item->id, 'title' => $item->title, 'url' => $item->url]), 'can' => ['create' => $request->user()->hasPermission('staff.create'), 'update' => $request->user()->hasPermission('staff.update'), 'delete' => $request->user()->hasPermission('staff.delete'), 'browse_media' => $request->user()->hasPermission('media.view'), 'upload_media' => $request->user()->hasPermission('media.upload')]]);
    }

    public function store(Request $request, StaffService $service): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['display_name']);
        DB::transaction(function () use ($data, $request) {
            $staff = Staff::create([...$this->staffPayload($data), 'uuid' => (string) Str::uuid(), 'created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
            $this->saveAssignment($staff, $data);
            $this->syncMedia($staff, $data['primary_media_id'] ?? null);
            StaffAudit::create(['staff_id' => $staff->id, 'user_id' => $request->user()->id, 'action' => 'created', 'after_payload' => $data]);
        });
        $service->forget();

        return back()->with('success', 'Staff berhasil ditambahkan.');
    }

    public function update(Request $request, Staff $staff, StaffService $service): RedirectResponse
    {
        $data = $this->validated($request, $staff);
        $data['slug'] = $staff->slug ?: $this->uniqueSlug($data['display_name'], $staff);
        DB::transaction(function () use ($data, $request, $staff) {
            $before = $this->formData($staff->load('assignments'));
            $staff->update([...$this->staffPayload($data), 'updated_by' => $request->user()->id]);
            $this->saveAssignment($staff, $data);
            $this->syncMedia($staff, $data['primary_media_id'] ?? null);
            StaffAudit::create(['staff_id' => $staff->id, 'user_id' => $request->user()->id, 'action' => 'updated', 'before_payload' => $before, 'after_payload' => $data]);
        });
        $service->forget();

        return back()->with('success', 'Data staff berhasil diperbarui.');
    }

    public function destroy(Request $request, Staff $staff, StaffService $service): RedirectResponse
    {
        if ($staff->assignments()->exists() || ClubProfile::where('founder_staff_id', $staff->id)->orWhere('contact_staff_id', $staff->id)->exists()) {
            return back()->withErrors(['staff' => 'Staff yang memiliki riwayat penugasan tidak dapat dihapus. Ubah status menjadi alumni.']);
        }
        $staff->delete();
        StaffAudit::create(['staff_id' => $staff->id, 'user_id' => $request->user()->id, 'action' => 'deleted']);
        $service->forget();

        return back()->with('success', 'Staff berhasil dihapus.');
    }

    private function validated(Request $request, ?Staff $staff = null): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'], 'display_name' => ['required', 'string', 'max:100'], 'hometown' => ['nullable', 'string', 'max:100'], 'biography' => ['nullable', 'string', 'max:3000'], 'primary_media_id' => ['nullable', 'exists:media,id'], 'image_focal_x' => ['required', 'integer', 'between:0,100'], 'image_focal_y' => ['required', 'integer', 'between:0,100'], 'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'alumni'])],
            'role_title' => ['required', 'string', 'max:100'], 'category' => ['required', Rule::in(['leadership', 'official', 'coaching', 'support', 'creative'])], 'short_description' => ['nullable', 'string', 'max:500'], 'badge' => ['nullable', 'string', 'max:40'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'], 'is_primary_founder' => ['boolean'], 'is_featured' => ['boolean'], 'featured_order' => ['nullable', 'integer', 'between:1,4'], 'is_public' => ['boolean'], 'assignment_status' => ['required', Rule::in(['scheduled', 'active', 'ended'])],
        ]);
        if (($data['is_featured'] ?? false) && StaffAssignment::where('is_featured', true)->where('status', 'active')->when($staff, fn ($query) => $query->where('staff_id', '!=', $staff->id))->count() >= 4) {
            throw ValidationException::withMessages(['is_featured' => 'Maksimal empat staff dapat ditampilkan di People Behind The Club.']);
        }

        return $data;
    }

    private function staffPayload(array $data): array
    {
        return collect($data)->only(['slug', 'full_name', 'display_name', 'hometown', 'biography', 'primary_media_id', 'image_focal_x', 'image_focal_y', 'status'])->all();
    }

    private function uniqueSlug(string $name, ?Staff $staff = null): string
    {
        $base = Str::slug($name) ?: 'staff';
        $slug = $base;
        $suffix = 2;

        while (Staff::where('slug', $slug)->when($staff, fn ($query) => $query->where('id', '!=', $staff->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function saveAssignment(Staff $staff, array $data): void
    {
        if ($data['is_primary_founder'] ?? false) {
            StaffAssignment::where('staff_id', '!=', $staff->id)->update(['is_primary_founder' => false]);
        }
        if ($data['role_title'] === 'Head Coach' && $data['assignment_status'] === 'active') {
            StaffAssignment::where('staff_id', '!=', $staff->id)->where('role_title', 'Head Coach')->where('status', 'active')->update(['status' => 'ended', 'ends_at' => now()->toDateString()]);
        }
        $assignment = $staff->assignments()->latest('id')->first() ?: new StaffAssignment(['staff_id' => $staff->id]);
        $assignment->fill(['role_title' => $data['role_title'], 'category' => $data['category'], 'short_description' => $data['short_description'] ?? null, 'badge' => $data['badge'] ?? null, 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null, 'is_primary_founder' => $data['is_primary_founder'] ?? false, 'is_featured' => $data['is_featured'] ?? false, 'featured_order' => ($data['is_featured'] ?? false) ? ($data['featured_order'] ?? null) : null, 'is_public' => $data['is_public'] ?? false, 'status' => $data['assignment_status']]);
        $assignment->save();
    }

    private function syncMedia(Staff $staff, ?int $mediaId): void
    {
        $staff->mediaUsages()->delete();
        if ($mediaId) {
            MediaUsage::create(['media_id' => $mediaId, 'usable_type' => Staff::class, 'usable_id' => $staff->id, 'field_key' => 'primary_media_id']);
        }
    }

    private function formData(Staff $staff): array
    {
        $assignment = $staff->assignments->sortByDesc('id')->first();

        return [...$staff->only(['id', 'slug', 'full_name', 'display_name', 'hometown', 'biography', 'primary_media_id', 'image_focal_x', 'image_focal_y', 'status']), 'photo_url' => $staff->photo?->url, 'role_title' => $assignment?->role_title, 'category' => $assignment?->category, 'short_description' => $assignment?->short_description, 'badge' => $assignment?->badge, 'starts_at' => $assignment?->starts_at?->format('Y-m-d'), 'ends_at' => $assignment?->ends_at?->format('Y-m-d'), 'is_primary_founder' => $assignment?->is_primary_founder ?? false, 'is_featured' => $assignment?->is_featured ?? false, 'featured_order' => $assignment?->featured_order, 'is_public' => $assignment?->is_public ?? true, 'assignment_status' => $assignment?->status ?? 'active'];
    }
}
