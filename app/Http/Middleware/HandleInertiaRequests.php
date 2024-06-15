<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'layouts/app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = request()->user();

        return array_merge(parent::share($request), [
            'auth' => $user ? [
                'user' => [
                    'avatarUrl' => $user->avatar_url,
                    'displayName' => $user->display_name ?? $user->username,
                    'id' => $user->id,
                    'legacyPermissions' => (int) $user->getAttribute('Permissions'),
                    'preferences' => [
                        'prefersAbsoluteDates' => $user->prefers_absolute_dates,
                    ],
                    'unreadMessageCount' => $user->UnreadMessageCount,
                ],

                'roles' => $user->getRoleNames(),
            ] : null,

            'ziggy' => fn () => [
                ...(new Ziggy())->toArray(),
                'location' => $request->url(),
            ],
        ]);
    }
}
