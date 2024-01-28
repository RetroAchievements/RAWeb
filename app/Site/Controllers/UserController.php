<?php

declare(strict_types=1);

namespace App\Site\Controllers;

use App\Http\Controller;
use App\Platform\Models\PlayerGame;
use App\Site\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
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
}
