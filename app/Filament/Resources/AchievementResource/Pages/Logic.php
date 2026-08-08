<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\Pages;

use App\Filament\Resources\AchievementResource;
use App\Filament\Resources\AchievementResource\Concerns\HasAchievementSetNavigation;
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
use Illuminate\Support\HtmlString;

class Logic extends Page
{
    use HasAchievementSetNavigation;
    use InteractsWithRecord;

    public ?int $requestedVersion = null;

    protected static string $resource = AchievementResource::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket';
    protected string $view = 'filament.resources.achievement-resource.pages.logic';

    /**
     * Complexity threshold for lazy loading (in bytes of raw condition strings).
     * ~50KB total is approximately 1000-2500+ conditions across all versions.
     */
    private const LAZY_LOAD_THRESHOLD = 50000;

    /**
     * The number of versions the history shows before collapsing the rest
     * behind a "See N more" button.
     */
    private const VISIBLE_VERSION_COUNT = 8;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $requestedVersion = request()->query('version');
        $this->requestedVersion = is_numeric($requestedVersion) ? (int) $requestedVersion : null;
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

    public function getSubheading(): string|Htmlable|null
    {
        $navData = $this->getAchievementSetNavigationData();
        if (!$navData) {
            return null;
        }

        return new HtmlString(
            view('filament.resources.achievement-resource.partials.achievement-navigator', [
                'navData' => $navData,
                'pageType' => 'logic',
            ])->render()
        );
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
     *     focusedVersion: int|null,
     *     shouldShowAllVersions: bool,
     *     visibleVersionCount: int,
     *     summaries?: array<int|null, string>,
     *     diffs?: array<int|null, array>
     * }
     */
    public function getVersionHistoryData(): array
    {
        $triggers = $this->getOrderedTriggerVersions(withUser: true);

        if ($triggers->isEmpty()) {
            return [
                'triggers' => $triggers,
                'lazyLoad' => false,
                'focusedVersion' => null,
                'shouldShowAllVersions' => false,
                'visibleVersionCount' => self::VISIBLE_VERSION_COUNT,
                'summaries' => [],
                'diffs' => [],
            ];
        }

        [$focusedVersion, $shouldShowAllVersions] = $this->resolveFocusedVersion($triggers);

        $totalConditionLength = $triggers->sum(fn ($t) => strlen($t->conditions ?? ''));
        $shouldLazyLoad = $totalConditionLength > self::LAZY_LOAD_THRESHOLD;
        if ($shouldLazyLoad) {
            return [
                'triggers' => $triggers,
                'lazyLoad' => true,
                'focusedVersion' => $focusedVersion,
                'shouldShowAllVersions' => $shouldShowAllVersions,
                'visibleVersionCount' => self::VISIBLE_VERSION_COUNT,
            ];
        }

        // For simple achievements, compute everything inline for instant display.
        [$summaries, $decodedTriggers] = $this->computeSummariesForTriggers($triggers);
        $diffs = $this->computeDiffsForTriggers($triggers, $decodedTriggers);

        return [
            'triggers' => $triggers,
            'lazyLoad' => false,
            'focusedVersion' => $focusedVersion,
            'shouldShowAllVersions' => $shouldShowAllVersions,
            'visibleVersionCount' => self::VISIBLE_VERSION_COUNT,
            'summaries' => $summaries,
            'diffs' => $diffs,
        ];
    }

    /**
     * Resolve the requested deep-link version against real trigger rows.
     *
     * @param Collection<int, Trigger> $triggers ordered descending by version
     * @return array{0: int|null, 1: bool} the version to expand, and whether the older
     *                                     versions hidden behind "See N more" must be
     *                                     shown for that version to be reachable
     */
    private function resolveFocusedVersion(Collection $triggers): array
    {
        if ($this->requestedVersion === null) {
            return [null, false];
        }

        $index = $triggers->search(fn (Trigger $trigger) => $trigger->version === $this->requestedVersion);
        if ($index === false) {
            return [null, false];
        }

        return [$this->requestedVersion, $index >= self::VISIBLE_VERSION_COUNT];
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
    public function loadVersionDiff(int|string|null $version): array
    {
        $triggers = $this->getOrderedTriggerVersions();

        $dbVersion = $version === 'draft' ? null : $version;

        $currentTrigger = $triggers->firstWhere('version', $dbVersion);
        if (!$currentTrigger) {
            return ['diff' => []];
        }

        $currentIndex = $triggers->search(fn ($t) => $t->version === $dbVersion);

        $decoderService = new TriggerDecoderService();
        $diffService = new TriggerDiffService();

        $currentGroups = $decoderService->decode($currentTrigger->conditions ?? '');

        $isOldestVersion = ($currentIndex === $triggers->count() - 1);
        $previousGroups = $isOldestVersion
            ? []
            : $decoderService->decode($triggers[$currentIndex + 1]->conditions ?? '');

        $this->alignAddressWidths($decoderService, $previousGroups, $currentGroups);

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
            $versionKey = $trigger->version ?? 'draft';

            $currentGroups = $decodedTriggers[$versionKey]
                ??= $decoderService->decode($trigger->conditions ?? '');

            $isOldestVersion = ($index === $triggers->count() - 1);
            if ($isOldestVersion) {
                $summaries[$versionKey] = 'Initial version';
            } else {
                $previousTrigger = $triggers[$index + 1];
                $previousKey = $previousTrigger->version ?? 'draft';
                $previousGroups = $decodedTriggers[$previousKey]
                    ??= $decoderService->decode($previousTrigger->conditions ?? '');

                $summaryData = $diffService->computeSummary($previousGroups, $currentGroups);
                $summaries[$versionKey] = $diffService->formatSummary($summaryData);
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
        $decoderService = new TriggerDecoderService();

        $diffs = [];

        foreach ($triggers as $index => $trigger) {
            $versionKey = $trigger->version ?? 'draft';

            $currentGroups = $decodedTriggers[$versionKey];

            $isOldestVersion = ($index === $triggers->count() - 1);
            $previousKey = $isOldestVersion ? null : ($triggers[$index + 1]->version ?? 'draft');
            $previousGroups = $isOldestVersion
                ? []
                : $decodedTriggers[$previousKey];

            $this->alignAddressWidths($decoderService, $previousGroups, $currentGroups);

            $diffs[$versionKey] = $diffService->computeDiff($previousGroups, $currentGroups);
        }

        return $diffs;
    }

    /**
     * Pads both versions of a trigger to a shared address width so a diff
     * never renders the same address with two different paddings.
     *
     * @param array<int, array<string, mixed>> $previousGroups
     * @param array<int, array<string, mixed>> $currentGroups
     */
    private function alignAddressWidths(TriggerDecoderService $decoderService, array &$previousGroups, array &$currentGroups): void
    {
        $width = max(
            $decoderService->getAddressWidth($previousGroups),
            $decoderService->getAddressWidth($currentGroups),
        );

        $decoderService->applyUniformAddressWidth($previousGroups, $width);
        $decoderService->applyUniformAddressWidth($currentGroups, $width);
    }
}
