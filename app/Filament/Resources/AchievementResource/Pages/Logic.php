<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\User;
use App\Platform\Services\TriggerDecoderService;
use App\Platform\Services\TriggerDiffService;
use BackedEnum;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class Logic extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AchievementResource::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket';
    protected string $view = 'filament.resources.achievement-resource.pages.logic';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;

        return "{$achievement->title} - Logic";
    }

    public function getBreadcrumb(): string
    {
        return 'Logic';
    }

    public function getBreadcrumbs(): array
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;
        $game = $achievement->game;

        return [
            route('filament.admin.resources.achievements.index') => 'Achievements',
            route('filament.admin.resources.games.view', $game) => $game->title,
            route('filament.admin.resources.achievements.view', $achievement) => $achievement->title,
            'Logic',
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        /** @var User $user */
        $user = Auth::user();

        if (!isset($parameters['record'])) {
            return false;
        }

        $achievement = $parameters['record'];
        if (!$achievement instanceof Achievement) {
            $achievement = Achievement::find($parameters['record']);
        }

        if (!$achievement) {
            return false;
        }

        return $user->can('viewLogic', $achievement);
    }

    /**
     * @return array{
     *     triggers: \Illuminate\Support\Collection<int, \App\Models\Trigger>,
     *     summaries: array<int, string>,
     *     diffs: array<int, array>
     * }
     */
    public function getVersionHistoryData(): array
    {
        /** @var Achievement $achievement */
        $achievement = $this->record;

        $triggers = $achievement->triggers()->with('user')->reorder()->orderByDesc('version')->get();

        if ($triggers->isEmpty()) {
            return ['triggers' => $triggers, 'summaries' => [], 'diffs' => []];
        }

        $diffService = new TriggerDiffService();
        $decoderService = new TriggerDecoderService();

        $summaries = [];
        $diffs = [];

        for ($i = 0; $i < $triggers->count(); $i++) {
            $currentConditions = $triggers[$i]->conditions ?? '';
            $currentGroups = $decoderService->decode($currentConditions);

            // For the oldest version (last in the list), compare against empty to show everything as "added".
            $isOldestVersion = ($i === $triggers->count() - 1);
            if ($isOldestVersion) {
                $previousGroups = [];
            } else {
                $previousConditions = $triggers[$i + 1]->conditions ?? '';
                $previousGroups = $decoderService->decode($previousConditions);
            }

            $diff = $diffService->computeDiff($previousGroups, $currentGroups);
            $summary = $diffService->computeSummary($previousGroups, $currentGroups);

            $summaries[$triggers[$i]->version] = $isOldestVersion ? 'Initial version' : $diffService->formatSummary($summary);
            $diffs[$triggers[$i]->version] = $diff;
        }

        return ['triggers' => $triggers, 'summaries' => $summaries, 'diffs' => $diffs];
    }
}
