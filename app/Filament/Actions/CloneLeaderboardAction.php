<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Leaderboard;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class CloneLeaderboardAction extends Action
{
    protected function setup(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = Auth::user();

        $this->label('Clone leaderboard')
            ->icon('heroicon-s-clipboard-document-list')
            ->color('success')
            ->modalDescription('This creates a duplicate of this leaderboard with the same settings, placed at the bottom of the display order. Entries will not be copied to the new leaderboard.')
            ->fillForm(function (Leaderboard $leaderboard) {
                return [
                    'title' => $leaderboard->Title . ' (Clone)',
                    'description' => $leaderboard->Description,
                ];
            })
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->label('Description')
                    ->maxLength(255),
            ])
            ->action(function (Leaderboard $leaderboard, array $data) use ($user) {
                if (!$user->can('create', Leaderboard::class)) {
                    return;
                }

                $clonedLeaderboard = $leaderboard->replicate([
                    'entries_count', // Excludes computed attribute
                    'top_entry_id',  // Also excludes this since it's specific to the original leaderboard
                ]);
                $clonedLeaderboard->Title = $data['title'];
                $clonedLeaderboard->Description = $data['description'];
                $clonedLeaderboard->author_id = $user->id;

                // Set DisplayOrder to be last
                $maxDisplayOrder = Leaderboard::max('DisplayOrder') ?? 0;
                $clonedLeaderboard->DisplayOrder = $maxDisplayOrder + 1;

                $clonedLeaderboard->push();
            })
            ->visible(function (Leaderboard $leaderboard) use ($user) {
                return $user->can('clone', $leaderboard);
            });
    }
}
