<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Enums\TriggerableType;
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
            ->modalSubmitAction(fn (Action $action) => $action->color('primary'))
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
                $clonedLeaderboard = $leaderboard->replicate([
                    'entries_count', // Excludes computed attribute
                    'top_entry_id',  // Also excludes this since it's specific to the original leaderboard
                    'trigger_id', // Exclude original trigger association
                ]);
                $clonedLeaderboard->Title = $data['title'];
                $clonedLeaderboard->Description = $data['description'];
                $clonedLeaderboard->author_id = $user->id;

                // Set DisplayOrder to be last
                $maxDisplayOrder = Leaderboard::where('GameID', $leaderboard->GameID)
                    ->max('DisplayOrder') ?? 0;
                $clonedLeaderboard->DisplayOrder = $maxDisplayOrder + 1;

                $clonedLeaderboard->push();

                if ($leaderboard->trigger) {
                    $clonedTrigger = $leaderboard->trigger->replicate([
                        'parent_id', // Exclude original parent association
                    ]);

                    $clonedTrigger->version = 1;
                    $clonedTrigger->triggerable_type = TriggerableType::Leaderboard;
                    $clonedTrigger->triggerable_id = $clonedLeaderboard->id;
                    $clonedTrigger->user_id = $user->id;
                    $clonedTrigger->push();

                    $clonedLeaderboard->trigger_id = $clonedTrigger->id;
                    $clonedLeaderboard->push();
                }
            })
            ->visible(function (Leaderboard $leaderboard) use ($user) {
                return $user->can('clone', $leaderboard);
            });
    }
}
