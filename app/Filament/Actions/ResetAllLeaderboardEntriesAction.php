<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Leaderboard;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;

class ResetAllLeaderboardEntriesAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = Auth::user();

        $this->label('Delete all entries')
            ->icon('heroicon-s-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription("Are you sure you want to permanently delete all entries of this leaderboard?")
            ->action(function ($record = null) use ($user) {
                $leaderboard = $record ?? $this->getLeaderboardParent();

                if (!$user->can('resetAllEntries', $leaderboard)) {
                    return;
                }

                $leaderboard->entries()->delete();

                activity()
                    ->useLog('default')
                    ->causedBy($user->id)
                    ->performedOn($leaderboard)
                    ->event('resetAllLeaderboardEntries')
                    ->log('Reset All Leaderboard Entries');
            })
            ->visible(function () use ($user) {
                return $user->can('resetAllEntries', [Leaderboard::class]);
            });
    }

    protected function getLeaderboardParent(): ?Leaderboard
    {
        $livewire = $this->getLivewire();

        if (!method_exists($livewire, 'getRelationship')) {
            return null;
        }

        $parentLeaderboardId = $livewire->getRelationship()->getParent()->id;

        return Leaderboard::findOrFail($parentLeaderboardId);
    }
}
