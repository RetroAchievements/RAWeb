<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Resources\AchievementResource;
use App\Models\Achievement;
use App\Models\Trigger;
use App\Models\User;
use App\Platform\Services\TriggerDecoderService;
use App\Platform\Services\TriggerDiffService;
use BackedEnum;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Logic extends Page
{
    use InteractsWithRecord;

    protected static string $resource = AchievementResource::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket';
    protected string $view = 'filament.resources.achievement-resource.pages.logic';

    /**
     * Complexity threshold for lazy loading (in bytes of raw condition strings).
     * ~50KB total is approximately 1000-2500+ conditions across all versions.
     */
    private const LAZY_LOAD_THRESHOLD = 50000;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return "{$this->getAchievement()->title} - Logic";
    }

    public function getBreadcrumb(): string
    {
        return 'Logic';
    }

    public function getBreadcrumbs(): array
    {
        $achievement = $this->getAchievement();
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
     * Returns trigger metadata and optionally pre-computed summaries/diffs.
     * For simple achievements, data is computed inline for instant display.
     * For complex achievements, data is lazy loaded via Livewire to avoid server memory issues.
     *
     * @return array{
     *     triggers: Collection<int, Trigger>,
     *     lazyLoad: bool,
     *     summaries?: array<int|null, string>,
     *     diffs?: array<int|null, array>
     * }
     */
    public function getVersionHistoryData(): array
    {
        $triggers = $this->getOrderedTriggerVersions(withUser: true);

        if ($triggers->isEmpty()) {
            return ['triggers' => $triggers, 'lazyLoad' => false, 'summaries' => [], 'diffs' => []];
        }

        $totalConditionLength = $triggers->sum(fn ($t) => strlen($t->conditions ?? ''));
        $shouldLazyLoad = $totalConditionLength > self::LAZY_LOAD_THRESHOLD;
        if ($shouldLazyLoad) {
            return ['triggers' => $triggers, 'lazyLoad' => true];
        }

        // For simple achievements, compute everything inline for instant display.
        [$summaries, $decodedTriggers] = $this->computeSummariesForTriggers($triggers);
        $diffs = $this->computeDiffsForTriggers($triggers, $decodedTriggers);

        return [
            'triggers' => $triggers,
            'lazyLoad' => false,
            'summaries' => $summaries,
            'diffs' => $diffs,
        ];
    }

    /**
     * Livewire action: Load all version summaries asynchronously.
     * Called via Alpine on page init to populate summary labels.
     *
     * @return array<int|null, string>
     */
    public function loadAllSummaries(): array
    {
        $triggers = $this->getOrderedTriggerVersions();

        if ($triggers->isEmpty()) {
            return [];
        }

        [$summaries] = $this->computeSummariesForTriggers($triggers);

        return $summaries;
    }

    /**
     * Livewire action: Load full diff for a single version on-demand.
     * Called via Alpine when user expands a version row.
     *
     * @return array{diff: array<int, array<string, mixed>>}
     */
    public function loadVersionDiff(?int $version): array
    {
        $triggers = $this->getOrderedTriggerVersions();

        $currentTrigger = $triggers->firstWhere('version', $version);
        if (!$currentTrigger) {
            return ['diff' => []];
        }

        $currentIndex = $triggers->search(fn ($t) => $t->version === $version);

        $decoderService = new TriggerDecoderService();
        $diffService = new TriggerDiffService();

        $currentGroups = $decoderService->decode($currentTrigger->conditions ?? '');

        $isOldestVersion = ($currentIndex === $triggers->count() - 1);
        $previousGroups = $isOldestVersion
            ? []
            : $decoderService->decode($triggers[$currentIndex + 1]->conditions ?? '');

        return ['diff' => $diffService->computeDiff($previousGroups, $currentGroups)];
    }

    private function getAchievement(): Achievement
    {
        /** @var Achievement */
        return $this->record;
    }

    /**
     * @return Collection<int, Trigger>
     */
    private function getOrderedTriggerVersions(bool $withUser = false): Collection
    {
        $query = $this->getAchievement()->triggers()->reorder()->orderByDesc('version');

        if ($withUser) {
            $query->with('user');
        }

        return $query->get();
    }

    /**
     * Compute version summaries for all triggers.
     *
     * @param Collection<int, Trigger> $triggers
     * @return array{0: array<int|null, string>, 1: array<int|null, array>}
     */
    private function computeSummariesForTriggers(Collection $triggers): array
    {
        $diffService = new TriggerDiffService();
        $decoderService = new TriggerDecoderService();

        $summaries = [];
        $decodedTriggers = [];

        foreach ($triggers as $index => $trigger) {
            $currentGroups = $decodedTriggers[$trigger->version]
                ??= $decoderService->decode($trigger->conditions ?? '');

            $isOldestVersion = ($index === $triggers->count() - 1);
            if ($isOldestVersion) {
                $summaries[$trigger->version] = 'Initial version';
            } else {
                $previousTrigger = $triggers[$index + 1];
                $previousGroups = $decodedTriggers[$previousTrigger->version]
                    ??= $decoderService->decode($previousTrigger->conditions ?? '');

                $summaryData = $diffService->computeSummary($previousGroups, $currentGroups);
                $summaries[$trigger->version] = $diffService->formatSummary($summaryData);
            }
        }

        return [$summaries, $decodedTriggers];
    }

    /**
     * Compute diffs for all triggers using pre-decoded trigger data.
     *
     * @param Collection<int, Trigger> $triggers
     * @param array<int|null, array> $decodedTriggers
     * @return array<int|null, array>
     */
    private function computeDiffsForTriggers(Collection $triggers, array $decodedTriggers): array
    {
        $diffService = new TriggerDiffService();

        $diffs = [];

        foreach ($triggers as $index => $trigger) {
            $currentGroups = $decodedTriggers[$trigger->version];

            $isOldestVersion = ($index === $triggers->count() - 1);
            $previousGroups = $isOldestVersion
                ? []
                : $decodedTriggers[$triggers[$index + 1]->version];

            $diffs[$trigger->version] = $diffService->computeDiff($previousGroups, $currentGroups);
        }

        return $diffs;
    }
}
