<?php

declare(strict_types=1);

namespace App\Filament\Actions;

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
            ->action(function ($record) use ($user) {
                if (!$user->can('resetAllEntries', $record)) {
                    return;
                }

                $record->entries()->delete();

                activity()
                    ->useLog('default')
                    ->causedBy($user->id)
                    ->performedOn($record)
                    ->event('resetAllLeaderboardEntries')
                    ->log('Reset All Leaderboard Entries');
            })
            ->visible(function ($record) use ($user) {
                return $user->can('resetAllEntries', $record);
            });
    }
}
