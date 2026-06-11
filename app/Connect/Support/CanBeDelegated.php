<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Actions\FindUserByIdentifierAction;
use App\Models\Achievement;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait CanBeDelegated
{
    protected function applyDelegationForGame(Request $request, Game $game): ?array
    {
        return $this->applyDelegation($request, function () use ($game) {
            return $game->getCanDelegateActivity($this->user);
        });
    }

    protected function applyDelegationForUnlock(Request $request, Achievement $achievement): ?array
    {
        return $this->applyDelegation($request, function () use ($achievement) {
            return $achievement->getCanDelegateUnlocks($this->user);
        });
    }

    protected function applyDelegationForUnlocks(Request $request, array $achievements): ?array
    {
        return $this->applyDelegation($request, function () use ($achievements, $request) {
            $blockedAchievementIds = [];

            foreach ($achievements as $achievement) {
                if (!$achievement->getCanDelegateUnlocks($this->user)) {
                    $blockedAchievementIds[] = $achievement->id;
                }
            }

            if (!empty($blockedAchievementIds)) {
                Log::warning('Delegated awardachievements request rejected because one or more achievements cannot be delegated.', [
                    'acting_user_id' => $this->user?->id,
                    'acting_username' => $this->user?->username,
                    'target_user' => $request->input('k'),
                    'achievement_ids' => array_map(
                        static fn (Achievement $achievement): int => $achievement->id,
                        $achievements
                    ),
                    'blocked_achievement_ids' => $blockedAchievementIds,
                ]);

                return false;
            }

            return true;
        });
    }

    private function applyDelegation(Request $request, callable $canDelegateFunction): ?array
    {
        if (!$request->has('k')) {
            return null;
        }

        $delegateTo = $request->input('k');

        if ($request->method() !== 'POST') {
            return [
                'Success' => false,
                'Status' => 405,
                'Code' => 'access_denied',
                'Error' => 'Access denied.',
            ];
        }

        $delegateUser = (new FindUserByIdentifierAction())->execute($delegateTo);
        if (!$delegateUser) {
            return $this->resourceNotFound('target user');
        }

        if (!$canDelegateFunction()) {
            return $this->accessDenied();
        }

        $this->user = $delegateUser;

        return null;
    }
}
