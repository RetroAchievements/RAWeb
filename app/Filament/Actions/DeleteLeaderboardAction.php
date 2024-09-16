<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Leaderboard;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class DeleteLeaderboardAction extends Action
{
    protected function setup(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = Auth::user();

        $this->label('Delete leaderboard')
            ->icon('heroicon-s-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription("Are you sure you want to permanently delete this leaderboard?")
            ->action(function (Leaderboard $leaderboard) use ($user) {
                // TODO use soft deletes
                if (!$user->can('forceDelete', $leaderboard)) {
                    return;
                }

                $leaderboard->forceDelete();
            })
            ->visible(function (Leaderboard $leaderboard) use ($user) {
                return $user->can('forceDelete', $leaderboard);
            });
    }
}
