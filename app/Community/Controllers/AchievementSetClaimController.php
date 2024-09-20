<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\CreateGameClaimAction;
use App\Community\Actions\DropGameClaimAction;
use App\Community\Actions\UpdateGameClaimAction;
use App\Community\Requests\UpdateGameClaimRequest;
use App\Http\Controller;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AchievementSetClaimController extends Controller
{
    public function create(
        Game $game,
        CreateGameClaimAction $action,
    ): RedirectResponse {
        $this->authorize('create', [AchievementSetClaim::class]);

        $claim = $action->execute($game);

        if (!$game->ForumTopicID && $this->authorize('update', $game)) {
            generateGameForumTopic(Auth::user(), $game->ID);

            // return redirect(route('game.show', $game->ID))
            //     ->with('success', $this->resourceActionSuccessMessage('claim', 'create'));
        }

        return back()->with('success', $this->resourceActionSuccessMessage('claim',
            $claim->Created == $claim->Updated ? 'create' : 'update'));
    }

    public function store(
        UpdateGameClaimRequest $request,
        AchievementSetClaim $claim,
        UpdateGameClaimAction $action,
    ): RedirectResponse {
        $this->authorize('manage', [AchievementSetClaim::class]);

        $action->execute($claim, $request);

        return back()->with('success', $this->resourceActionSuccessMessage('claim', 'update'));
    }

    public function delete(
        Game $game,
        DropGameClaimAction $action,
    ): RedirectResponse {
        $this->authorize('manage', [AchievementSetClaim::class]);

        $claim = $action->execute($game);

        if (!$claim) {
            return back()->with('error', 'You do not have a claim on this game.');
        }

        return back()->with('success', $this->resourceActionSuccessMessage('claim', 'drop'));
    }
}
