<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\CreateGameClaimAction;
use App\Community\Actions\DropGameClaimAction;
use App\Community\Actions\UpdateGameClaimAction;
use App\Community\Enums\ClaimStatus;
use App\Community\Requests\UpdateGameClaimRequest;
use App\Http\Controller;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

// TODO once game pages use the React UI, this doesn't need to return 302/redirect responses anymore

class AchievementSetClaimController extends Controller
{
    public function store(
        Game $game,
        CreateGameClaimAction $action,
    ): RedirectResponse {
        $this->authorize('create', [AchievementSetClaim::class, $game]);

        $claim = $action->execute($game);

        if (!$game->forum_topic_id && $this->authorize('update', $game)) {
            generateGameForumTopic(Auth::user(), $game->id);
        }

        return back()->with('success', $this->resourceActionSuccessMessage('claim',
            $claim->Created->equalTo($claim->Updated) ? 'create' : 'update'));
    }

    public function update(
        UpdateGameClaimRequest $request,
        AchievementSetClaim $claim,
        UpdateGameClaimAction $action,
    ): RedirectResponse {
        $status = $request->input('status');

        // Determine which policy to use based on the status change.
        if (isset($status)) {
            $statusInt = (int) $status;

            if (
                in_array($statusInt, [ClaimStatus::InReview, ClaimStatus::Active])
                && $claim->Status !== $statusInt
            ) {
                $this->authorize('review', $claim);
            } elseif ($statusInt === ClaimStatus::Complete) {
                $this->authorize('complete', $claim);
            } else {
                $this->authorize('update', $claim);
            }
        } else {
            $this->authorize('update', $claim);
        }

        $action->execute($claim, $request->validated());

        return back()->with('success', $this->resourceActionSuccessMessage('claim', 'update'));
    }

    public function delete(
        Game $game,
        DropGameClaimAction $action,
    ): RedirectResponse {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $claim = $game->achievementSetClaims()->active()->where('user_id', $currentUser->id)->first();
        if ($claim === null) {
            return back()->with('error', 'You do not have a claim on this game.');
        }

        $this->authorize('delete', $claim);

        $action->execute($claim, $currentUser);

        return back()->with('success', $this->resourceActionSuccessMessage('claim', 'drop'));
    }
}
