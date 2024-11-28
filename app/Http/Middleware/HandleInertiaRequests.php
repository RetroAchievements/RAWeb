<?php

namespace App\Http\Middleware;

use App\Data\UserData;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Jenssegers\Agent\Agent;
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
                'user' => UserData::fromUser($user)->include(
                    'id',
                    'isMuted',
                    'legacyPermissions',
                    'locale',
                    'points',
                    'pointsSoftcore',
                    'mutedUntil',
                    'preferences',
                    'roles',
                    'unreadMessageCount',
                    'username',
                    'websitePrefs',
                ),
            ] : null,

            'config' => [
                'services' => [
                    'patreon' => ['userId' => config('services.patreon.user_id')],
                ],
            ],

            'ziggy' => fn () => [
                ...(new Ziggy())->toArray(),
                'device' => (new Agent())->isMobile() ? 'mobile' : 'desktop',
                'location' => $request->url(),
                'query' => $request->query(),
            ],
        ]);
    }
}
