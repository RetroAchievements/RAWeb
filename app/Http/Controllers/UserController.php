<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controller;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Actions\RequestAccountDeletion;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Log;
use Jenssegers\Optimus\Optimus;

class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('resource.index')
            ->with('resource', 'user');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['lastGame']);

        $games = $user->playerGames()
            ->with([
                'achievements' => function ($query) use ($user) {
                    $query->withUnlocksByUser($user);
                    $query->orderByDesc('unlocked_hardcore_at');
                    $query->orderByDesc('unlocked_at');
                },
            ])
            ->withCount('achievements');

        $comments = null;
        // $comments = $user->comments()->paginate();

        /**
         * eager load models referenced in content
         */
        // ContentModelCollector::collect($comments->pluck('body'));

        // $games->withPivot(['achievements_unlocked']);
        // $games->wherePivot('achievements_unlocked', '>', 0);
        $gamesPlayedCount = $games->count();
        $games->take(3);
        $games = $games->get();

        $games->map(function (PlayerGame $playerGame) {
            // TODO aggregate at query time or have it cached in db. this should've already been calculated at this point
            // @phpstan-ignore-next-line
            $playerGame->achievements_unlocked = $playerGame->achievements->where('unlocked_at')->count();

            // $playerGame->achievements->each->setRelation('game', $playerGame->game);

            return $playerGame;
        });

        return view('user.show')
            ->with('comments', $comments)
            ->with('games', $games)
            ->with('gamesPlayedCount', $gamesPlayedCount)
            ->with('user', $user);
    }

    public function permalink(Optimus $optimus, int $hashId): Redirector|Application|RedirectResponse
    {
        $userId = $optimus->decode($hashId);
        $user = User::findOrFail($userId);

        $this->authorize('view', $user);

        return redirect(route('user.show', $user));
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('updateAvatar', $user);

        try {
            UploadAvatar($user->username, $request->imageData);

            return response()->json(['success' => true]);
        } catch (Exception $exception) {
            $error = $exception->getMessage();

            // Handle specific error messages
            if ($error == 'Invalid file type' || $error == 'File too large') {
                return response()->json(['message' => $error], 400);
            }

            if (preg_match('/(not a .* file)/i', $exception->getMessage(), $match)) {
                return response()->json(['message' => ucfirst($match[0])], 400);
            }

            // Log unexpected errors and return a 500 error
            Log::error($exception->getMessage());

            return response()->json(['message' => __('legacy.error.server')], 500);
        }
    }

    public function deleteAvatar(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('updateAvatar', $user);

        removeAvatar($user->username);

        return response()->json(['success' => true]);
    }

    public function requestAccountDeletion(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize('update', $user);

        (new RequestAccountDeletion())->execute($user);

        return response()->json(['success' => true]);
    }

    public function cancelAccountDeletion(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var User $user */
        $this->authorize('update', $user);

        cancelDeleteRequest($user->username);

        return response()->json(['success' => true]);
    }
}
