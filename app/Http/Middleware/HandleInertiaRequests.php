<?php

namespace App\Http\Middleware;

use App\Actions\GetUserDeviceKindAction;
use App\Data\UserData;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Symfony\Component\HttpFoundation\Response;
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

    public function handle(Request $request, Closure $next): Response
    {
        $response = parent::handle($request, $next);

        /**
         * We want to prevent browsers from caching Intertia's 409 version mismatch responses.
         *
         * When Inertia detects that the client's asset version doesn't match the server's
         * (after a deployment), it returns a 409 status code with an X-Inertia-Location header.
         * The client-side Inertia.js library then performs a full page reload to the current location.
         *
         * 4xx responses are cacheable unless the origin says otherwise.
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/410#cacheability
         *
         * If Inertia.js's 409 response gets cached (particularly by Firefox, which strictly follows
         * HTTP caching specs even for error responses), subsequent navigation attempts will receive
         * the cached 409 instead of fresh content, leaving the user with a broken UI until they manually
         * clear their cache.
         *
         * The fix is to mark every 409 response from Inertia.js as strictly non-storeable. We need
         * to instruct browsers/Cloudflare to _never_ write the response to disk or memory.
         *
         * @see https://inertiajs.com/asset-versioning
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cache-Control#no-store
         */
        if ($response->getStatusCode() === 409) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
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
                    'isEmailVerified',
                    'isMuted',
                    'isNew',
                    'legacyPermissions',
                    'locale',
                    'mutedUntil',
                    'playerPreferredMode',
                    'points',
                    'pointsSoftcore',
                    'preferences',
                    'roles',
                    'unreadMessageCount',
                    'username',
                    'visibleRole',
                    'websitePrefs',
                ),
            ] : null,

            'config' => [
                'app' => [
                    'url' => config('app.url'),
                ],
                'services' => [
                    'patreon' => ['userId' => config('services.patreon.user_id')],
                ],
            ],

            'ziggy' => fn () => [
                ...(new Ziggy())->toArray(),
                'device' => (new GetUserDeviceKindAction())->execute(),
                'location' => $request->url(),
                'query' => $request->query(),
            ],
        ]);
    }
}
