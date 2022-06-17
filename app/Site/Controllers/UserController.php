<?php

declare(strict_types=1);

namespace App\Site\Controllers;

use App\Http\Controller;
use App\Platform\Models\PlayerGame;
use App\Site\Actions\DeleteAvatarAction;
use App\Site\Actions\UpdateRolesAction;
use App\Site\Models\User;
use App\Site\Requests\UserRequest;
use Carbon\Carbon;
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

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('user.edit')->with('user', $user);
    }

    public function update(UserRequest $request, User $user, UpdateRolesAction $updateRolesAction): Redirector|Application|RedirectResponse
    {
        $this->authorize('update', $user);

        /**
         * TODO: check if request user can give/remove this user's roles
         * - cannot remove oneselves' highest role (sys admin)
         * - cannot remove a role with one's same highest role
         */
        $data = $request->validated();

        $updateRolesAction->execute($user, $request);

        $user->update($data);

        return redirect(route('user.edit', $user))
            ->with('success', $this->resourceActionSuccessMessage('user', 'update'));
    }

    public function destroy(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('delete', $user);

        /*
         * TODO: user should be marked to-be-deleted instead with the option to be restored
         * actual deletion happens via scheduler after defined time passed
         */
        // dd('test');
        // $user->delete();
        // return redirect(route('user.index'))->with('success', $this->resourceActionSuccessMessage('user', 'delete'));

        return back();
    }

    public function destroyAvatar(User $user, DeleteAvatarAction $deleteAvatarAction): Redirector|Application|RedirectResponse
    {
        $this->authorize('deleteAvatar', $user);

        $deleteAvatarAction->execute($user);

        return back()->with('success', $this->resourceActionSuccessMessage('user.avatar', 'delete'));
    }

    public function destroyMotto(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('deleteMotto', $user);

        $user->motto = null;
        $user->save();

        return back()->with('success', $this->resourceActionSuccessMessage('user.motto', 'delete'));
    }

    public function ban(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('ban', $user);

        $user->banned_at = Carbon::now();
        $user->save();

        return back()->with('success', $this->resourceActionSuccessMessage('user', 'ban'));
    }

    public function unban(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('ban', $user);

        $user->banned_at = null;
        $user->save();

        return back()->with('success', $this->resourceActionSuccessMessage('user', 'unban'));
    }

    public function mute(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('mute', $user);

        $user->muted_until = Carbon::now()->addDays(7);
        $user->save();

        return back()->with('success', $this->resourceActionSuccessMessage('user', 'mute'));
    }

    public function unmute(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('mute', $user);

        $user->muted_until = null;
        $user->save();

        return back()->with('success', $this->resourceActionSuccessMessage('user', 'unmute'));
    }

    public function rank(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('rank', $user);

        $user->unranked_at = null;
        $user->save();

        return back()->with('success', $this->resourceActionSuccessMessage('user', 'rank'));
    }

    public function unrank(User $user): Redirector|Application|RedirectResponse
    {
        $this->authorize('rank', $user);

        $user->unranked_at = Carbon::now();
        $user->save();

        return back()->with('success', $this->resourceActionSuccessMessage('user', 'unrank'));
    }
}
