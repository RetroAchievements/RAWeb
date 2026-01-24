<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Actions\MergeLeaderboardsAction as DomainMergeLeaderboardsAction;
use App\Platform\Enums\ValueFormat;
use Closure;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component as Livewire;

class MergeLeaderboardsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = Auth::user();

        $this->label('Merge leaderboards')
            ->icon('heroicon-s-arrow-path-rounded-square')
            ->color('warning')
            ->modalHeading(fn (Leaderboard $leaderboard) => "Merge Leaderboard Entries into \"{$leaderboard->title}\"")
            ->modalSubmitActionLabel('Merge Leaderboards')
            ->modalSubmitAction(fn (Action $action) => $action->color('danger'))
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalWidth('lg')
            ->schema(fn (Leaderboard $leaderboard) => $this->getFormSchema($leaderboard, $user))
            ->action(fn (Leaderboard $leaderboard, array $data) => $this->executeMerge($leaderboard, $data, $user))
            ->visible(fn (Leaderboard $leaderboard) => $user->can('merge', $leaderboard));
    }

    private function executeMerge(Leaderboard $leaderboard, array $data, User $user): void
    {
        $childLeaderboard = Leaderboard::findOrFail($data['child_leaderboard_id']);

        try {
            $result = (new DomainMergeLeaderboardsAction())->execute(
                parentLeaderboard: $leaderboard,
                childLeaderboard: $childLeaderboard,
                user: $user
            );

            $totalProcessed = $result['entries_transferred'] + $result['entries_merged'] + $result['entries_skipped'];

            Notification::make()
                ->title('Leaderboards merged successfully')
                ->body("Processed {$totalProcessed} entries.")
                ->success()
                ->send();
        } catch (InvalidArgumentException $e) {
            // this catch is largely defensive
            // we'll probably only fall into this block if the user is doing something nefarious
            Notification::make()
                ->title('Merge failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * @return array<\Filament\Schemas\Components\Component>
     */
    private function getFormSchema(Leaderboard $parentLeaderboard, User $user): array
    {
        $isValidSelection = fn (Get $get): bool => $get('child_leaderboard_id') && $get('_top_entry_preview') !== null;
        $canMergeAny = $user->can('mergeAny', Leaderboard::class);

        return [
            Forms\Components\Select::make('child_leaderboard_id')
                ->label('Merge entries from')
                ->searchable()
                ->required()
                ->getSearchResultsUsing(function (string $search) use ($parentLeaderboard, $user, $canMergeAny) {
                    $query = Leaderboard::with('game')
                        ->where('id', '!=', $parentLeaderboard->id)
                        ->where(function ($query) use ($search) {
                            $query->where('title', 'like', "%{$search}%")
                                ->orWhere('id', 'like', "{$search}%")
                                ->orWhereHas('game', fn ($q) => $q->where('title', 'like', "%{$search}%"));
                        });

                    // Developers can only see leaderboards they authored.
                    if (!$canMergeAny) {
                        $query->where('author_id', $user->id);
                    }

                    return $query
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Leaderboard $lb) => [
                            $lb->id => $this->formatLeaderboardLabel($lb),
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value) {
                    $lb = Leaderboard::with('game')->find($value);

                    return $lb ? $this->formatLeaderboardLabel($lb) : '';
                })
                ->live()
                ->afterStateUpdated(function (Set $set, ?string $state, Livewire $livewire) use ($parentLeaderboard) {
                    // Clear any previous validation errors when the selection changes.
                    $livewire->resetValidation();

                    if (!$state) {
                        $set('_child_entries_count', null);
                        $set('_child_title', null);
                        $set('_top_entry_preview', null);

                        return;
                    }

                    $child = Leaderboard::withCount('entries')
                        ->with(['topEntry.user'])
                        ->find($state);

                    if (!$child) {
                        return;
                    }

                    $set('_child_entries_count', $child->entries_count);
                    $set('_child_title', $child->title);

                    // Only calculate the preview if leaderboards are compatible to merge.
                    $isCompatible =
                        $child->format === $parentLeaderboard->format
                        && $child->rank_asc === $parentLeaderboard->rank_asc;

                    $set('_top_entry_preview', $isCompatible
                        ? $this->calculateTopEntryPreview($parentLeaderboard, $child)
                        : null
                    );
                })
                ->rules([
                    fn (): Closure => function (string $attribute, $value, Closure $fail) use ($parentLeaderboard, $user) {
                        if (!$value) {
                            return;
                        }

                        $child = Leaderboard::find($value);
                        if (!$child) {
                            $fail('The selected leaderboard does not exist.');

                            return;
                        }

                        if (!$user->can('merge', $child)) {
                            $fail('You do not have permission to merge this leaderboard.');

                            return;
                        }

                        if ($child->format !== $parentLeaderboard->format) {
                            $parentFormat = ValueFormat::toString($parentLeaderboard->format);
                            $childFormat = ValueFormat::toString($child->format);
                            $fail("Format mismatch: Destination uses '{$parentFormat}', source uses '{$childFormat}'.");

                            return;
                        }

                        if ($child->rank_asc !== $parentLeaderboard->rank_asc) {
                            $parentDirection = $this->formatRankDirection($parentLeaderboard->rank_asc);
                            $childDirection = $this->formatRankDirection($child->rank_asc);
                            $fail("Rank direction mismatch: Destination is '{$parentDirection}', source is '{$childDirection}'.");
                        }
                    },
                ]),

            // Hidden state used for display in other form components.
            Forms\Components\Hidden::make('_child_entries_count'),
            Forms\Components\Hidden::make('_child_title'),
            Forms\Components\Hidden::make('_top_entry_preview'),

            Forms\Components\Placeholder::make('merge_preview')
                ->hiddenLabel()
                ->content(function (Get $get) {
                    if (!$get('child_leaderboard_id')) {
                        return '';
                    }

                    $entriesCount = $get('_child_entries_count') ?? 0;
                    $topEntryPreview = $get('_top_entry_preview') ?? '';

                    return "{$entriesCount} entries will be merged. {$topEntryPreview} The source leaderboard will be set to Unpublished.";
                })
                ->visible($isValidSelection),

            Forms\Components\Checkbox::make('confirm_move_entries')
                ->label(fn (Get $get) => sprintf(
                    'I understand this will merge %d entries into the "%s" leaderboard',
                    $get('_child_entries_count') ?? 0,
                    $parentLeaderboard->title
                ))
                ->required()
                ->accepted()
                ->visible($isValidSelection),

            Forms\Components\Checkbox::make('confirm_disable_source')
                ->label(fn (Get $get) => sprintf(
                    'I understand this will set leaderboard ID %s ("%s") to Unpublished and delete all its entries',
                    $get('child_leaderboard_id') ?? '?',
                    $get('_child_title') ?? ''
                ))
                ->required()
                ->accepted()
                ->visible($isValidSelection),
        ];
    }

    private function formatLeaderboardLabel(Leaderboard $leaderboard): string
    {
        return "ID: {$leaderboard->id} - [{$leaderboard->game->title}] {$leaderboard->title}";
    }

    private function formatRankDirection(bool $rankAsc): string
    {
        return $rankAsc ? 'Lower is better' : 'Higher is better';
    }

    private function calculateTopEntryPreview(Leaderboard $parent, Leaderboard $child): string
    {
        $parent->load(['topEntry.user']);

        $parentTop = $parent->topEntry;
        $childTop = $child->topEntry;

        if (!$parentTop && !$childTop) {
            return '';
        }

        $childWins = $childTop && (!$parentTop || $parent->isBetterScore($childTop->score, $parentTop->score));

        $winningEntry = $childWins ? $childTop : $parentTop;
        $formattedScore = ValueFormat::format($winningEntry->score, $parent->format);
        $prefix = $childWins ? 'The new top entry will be' : 'The top entry will remain';

        return "{$prefix} {$formattedScore} by {$winningEntry->user->display_name}.";
    }
}
