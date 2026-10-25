<?php

namespace App\Http\Middleware;

use App\Services\ClubProfileService;
use Illuminate\Http\Request;
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

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
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
            'site' => $request->routeIs('preview.profile') ? $profileService->draft() : $profileService->published(),
        ];
    }
}
