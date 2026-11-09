<?php

namespace App\Http\Middleware;

use App\Models\AnalyticsSetting;
use App\Services\ClubProfileService;
use App\Services\SeoMetadataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user()?->loadMissing('roleRelation.permissions');
        $profileService = app(ClubProfileService::class);

        $site = $request->routeIs('preview.profile') ? $profileService->draft() : $profileService->published();
        if (Schema::hasTable('analytics_settings')) {
            $analytics = AnalyticsSetting::query()->first();
            $site['website']['analytics_id'] = $analytics?->enabled ? $analytics->measurement_id : null;
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'pending_email' => $user->pending_email,
                    'must_change_email' => $user->must_change_email,
                    'role' => $user->roleRelation?->slug ?? $user->role?->value,
                    'role_name' => $user->roleRelation?->name ?? $user->role?->label(),
                    'permissions' => $user->isSuperAdmin()
                        ? ['*']
                        : $user->roleRelation?->permissions->pluck('slug')->values() ?? [],
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
            ],
            'site' => $site,
            'seoMeta' => app(SeoMetadataService::class)->forRequest($request),
        ];
    }
}
