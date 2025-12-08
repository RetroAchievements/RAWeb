<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Leaderboard;
use App\Models\User;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

// Action to disable a leaderboard by modifying its Mem attribute
// This is only required until we are able to set leaderboards to 'unofficial'

class DisableLeaderboardAction extends Action
{
    protected function setup(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = Auth::user();

        $this->label('Disable leaderboard')
            ->icon('heroicon-s-x-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription("Are you sure you want to disable this leaderboard? This will prevent new entries from being added. To re-enable it you will need to claim the game and edit the leaderboard start conditions to remove the '0=1' condition")
            ->action(function (Leaderboard $leaderboard) {
                // Get the current Mem value
                $mem = $leaderboard->Mem ?? '';

                // Find the position of the first ::
                $firstDoubleColon = strpos($mem, '::');

                // Insert _0=1 before the first ::
                $modifiedMem = substr_replace($mem, '_0=1', $firstDoubleColon, 0);

                $leaderboard->Mem = $modifiedMem;
                $leaderboard->push();
            })
            ->visible(function (Leaderboard $leaderboard) use ($user) {
                return $user->can('disable', $leaderboard);
            });
    }
}
