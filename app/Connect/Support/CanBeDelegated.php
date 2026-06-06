<?php

declare(strict_types=1);

namespace App\Connect\Support;

use App\Actions\FindUserByIdentifierAction;
use App\Models\Achievement;
use App\Models\Game;
use Illuminate\Http\Request;

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
        return $this->applyDelegation($request, function () use ($achievements) {
            foreach ($achievements as $achievement) {
                if (!$achievement->getCanDelegateUnlocks($this->user)) {
                    return false;
                }
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
