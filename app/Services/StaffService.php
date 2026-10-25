<?php

namespace App\Services;

use App\Models\StaffAssignment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class StaffService
{
    public function publicData(): array
    {
        if (! Schema::hasTable('staff')) {
            return ['people' => [], 'coaching' => [], 'support' => [], 'founder' => null];
        }

        return Cache::remember('staff:public', 3600, function () {
            $items = StaffAssignment::with('staff.photo')->where('status', 'active')->where('is_public', true)->whereHas('staff', fn ($query) => $query->where('status', 'active'))->orderByRaw('featured_order is null, featured_order')->get()->map(fn ($assignment) => $this->transform($assignment));

            return ['people' => $items->filter(fn ($item) => $item['featured'])->take(4)->values(), 'coaching' => $items->where('category', 'coaching')->values(), 'support' => $items->filter(fn ($item) => in_array($item['category'], ['support', 'creative']))->values(), 'founder' => $items->firstWhere('primaryFounder', true)];
        });
    }

    public function forget(): void
    {
        Cache::forget('staff:public');
    }

    private function transform(StaffAssignment $assignment): array
    {
        $staff = $assignment->staff;
        $image = $staff->photo?->url ?: '/Assets/Dokumentasi/Docs-wahyu.jpeg';

        return ['id' => $staff->id, 'name' => $staff->display_name, 'role' => $assignment->role_title, 'category' => $assignment->category, 'meta' => $assignment->short_description, 'note' => $assignment->short_description, 'badge' => $assignment->badge ?: ($assignment->category === 'leadership' ? 'Founder Spotlight' : 'Official'), 'photo' => $image, 'image' => $image, 'position' => "{$staff->image_focal_x}% {$staff->image_focal_y}%", 'focalPoint' => "{$staff->image_focal_x}% {$staff->image_focal_y}%", 'featured' => $assignment->is_featured, 'founder' => $assignment->is_primary_founder, 'primaryFounder' => $assignment->is_primary_founder];
    }
}
