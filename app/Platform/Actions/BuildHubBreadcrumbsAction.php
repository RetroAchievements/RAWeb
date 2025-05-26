<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameSet;
use App\Platform\Data\GameSetData;
use App\Platform\Enums\GameSetType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelData\Lazy;

/**
 * Builds breadcrumb navigation paths for hub (game set) hierarchies.
 *
 * This action  generates breadcrumb navigation paths for hubs, handling various
 * special cases and hierarchical relationships. It supports different types of
 * game sets including themes, difficulty levels, and various hub types.
 *
 * - Caches breadcrumb paths with fresh/stale timing.
 * - Handles special cases for Theme and Difficulty hubs.
 * - Supports derived hierarchical navigation through hub types.
 * - Manages hub relationships via predefined mappings.
 */
class BuildHubBreadcrumbsAction
{
    /**
     * Cache configuration for breadcrumb data.
     */
    private const FRESH_SECONDS = 600; // 10 minutes
    private const STALE_SECONDS = 1200; // 20 minutes

    /**
     * Common prefix for central hub entries.
     */
    private const PREFIX_CENTRAL = '[Central - ';

    /**
     * Defines the hierarchical relationships between different hub types.
     * The key represents the child type, and the value represents the parent type.
     *
     * @var array<string, string>
     */
    private const HUB_HIERARCHY = [
        'Custom Awards' => 'Central',
        'Feature' => 'Central',
        'Game Mechanic' => 'Central',
        'Meta' => 'Central',
        'Meta|QA' => 'Meta',
        'Meta|DevComp' => 'Meta',
        'Regional' => 'Central',
        'Series' => 'Central',
        'Subseries' => 'Series',
        'Technical' => 'Central',
    ];

    /**
     * Maps abbreviated or alternative type names to their full display names.
     * This has historically occurred because hubs began their lives in the
     * DB as bastardized games, which have strict max character limits.
     *
     * @var array<string, string>
     */
    private const TYPE_MAPPINGS = [
        'ASB' => 'Arcade System Boards',
        'Bounty Hunters' => 'Community Events',
        'Challenge League Top 100' => 'Community Events',
        'Console Wars I' => 'Community Events',
        'Dev Events' => 'Developer Events',
        'DevJam' => 'Community Events',
        'Events' => 'Community Events',
        'Misc.' => 'Miscellaneous',
        'Genre' => 'Genre & Subgenre',
        'Subgenre' => 'Genre & Subgenre',
        'Series Hacks' => 'Hacks',
    ];

    /**
     * Returns the mapped (full) type name for a given type.
     */
    private function getMappedType(string $type): string
    {
        return self::TYPE_MAPPINGS[$type] ?? $type;
    }

    /**
     * Generates the breadcrumb navigation path for a given game set (hub).
     *
     * This method handles the complexity of building a hierarchical breadcrumb trail by:
     * 1. Checking for cached data first.
     * 2. Handling special cases (Theme, Difficulty).
     * 3. Building the hierarchy based on game set (hub) relationships.
     * 4. Ensuring proper central hub inclusion.
     *
     * @param GameSet $gameSet the game set (hub) to generate breadcrumbs for
     * @return array<GameSetData> the final ordered list of GameSetData objects representing the breadcrumb path
     */
    public function execute(GameSet $gameSet): array
    {
        $cacheKey = "hub_breadcrumbs:{$gameSet->id}";

        /** @var array[] $cachedData */
        $cachedData = Cache::flexible($cacheKey, [self::FRESH_SECONDS, self::STALE_SECONDS], function () use ($gameSet) {
            // If this is the central hub itself, we can return early.
            if ($gameSet->id === GameSet::CentralHubId) {
                return [$this->toPathArray($gameSet)];
            }

            $visited = [];
            $remainingPath = [];
            $currentGameSet = $gameSet;

            // Handle "[DevQuest N Sets] Title".
            if (preg_match('/^\[DevQuest \d+ Sets\]/', $currentGameSet->title)) {
                return $this->handleDevQuestPath($gameSet);
            }

            // Extract the type from the title if it matches the pattern "[Type - ...]".
            if (preg_match('/^\[(.*?) - /', $currentGameSet->title, $matches)) {
                $currentType = $matches[1];
                $visited[] = $currentGameSet->id;

                // Handle the "Theme" type.
                if ($currentType === 'Theme') {
                    return $this->buildThemeBreadcrumbs($currentGameSet);
                }

                // Handle the "Difficulty" type.
                if ($currentType === 'Difficulty') {
                    return $this->buildDifficultyBreadcrumbs($currentGameSet);
                }

                // Add the current hub to the path.
                $remainingPath[] = $this->toPathArray($currentGameSet);

                $mappedType = $this->getMappedType($currentType);

                // Process either a standard hierarchy or a custom parent chain.
                if (!isset(self::HUB_HIERARCHY[$currentType])) {
                    $this->processCustomHierarchy($currentGameSet, $currentType, $mappedType, $visited, $remainingPath);
                } else {
                    $this->processStandardHierarchy($currentGameSet, $currentType, $mappedType, $visited, $remainingPath);
                }
            } else {
                // For invalid/non-standard titles, just add the current hub.
                $remainingPath[] = $this->toPathArray($gameSet);
            }

            // Add Central hub unless it's a Theme hub.
            // `buildThemeBreadcrumbs()` handles this internally for Theme hubs.
            if (!str_starts_with($gameSet->title, '[Theme - ')) {
                $this->prependCentralHub($remainingPath);
            }

            return $remainingPath;
        });

        // Transform cached data into GameSetData objects.
        return array_map(
            fn ($data) => new GameSetData(
                id: $data['id'],
                type: GameSetType::from($data['type']),
                title: $data['title'],
                badgeUrl: media_asset($data['image_asset_path']),
                gameCount: 0,
                linkCount: 0,
                forumTopicId: null,
                updatedAt: new Carbon($data['updated_at']),
                gameId: 0,
                game: Lazy::create(fn () => null),
                hasMatureContent: false, // doesn't matter, this is just a breadcrumb
                isEventHub: false, // doesn't matter, this is just a breadcrumb
            ),
            $cachedData ?? [],
        );
    }

    /**
     * Converts a GameSet instance into an array suitable for breadcrumb navigation.
     *
     * @param GameSet $gameSet The GameSet to convert
     * @return array<string, mixed> Array containing essential GameSet data
     */
    private function toPathArray(GameSet $gameSet): array
    {
        return [
            'id' => $gameSet->id,
            'title' => $gameSet->title,
            'type' => $gameSet->type instanceof GameSetType ? $gameSet->type->value : $gameSet->type,
            'image_asset_path' => $gameSet->image_asset_path,
            'updated_at' => $gameSet->updated_at->toDateTimeString(),
        ];
    }

    /**
     * Builds a breadcrumb path for "Theme" hubs.
     */
    private function buildThemeBreadcrumbs(GameSet $gameSet): array
    {
        $breadcrumbs = [];

        $centralHub = GameSet::centralHub()->first();
        if ($centralHub) {
            $breadcrumbs[] = $this->toPathArray($centralHub);
        }

        $centralThemeHub = GameSet::whereTitle(self::PREFIX_CENTRAL . 'Theme]')
            ->whereType(GameSetType::Hub)
            ->whereNull('deleted_at')
            ->first();
        if ($centralThemeHub) {
            $breadcrumbs[] = $this->toPathArray($centralThemeHub);
        }

        $breadcrumbs[] = $this->toPathArray($gameSet);

        return $breadcrumbs;
    }

    /**
     * Builds a breadcrumb path for "Difficulty" hubs.
     */
    private function buildDifficultyBreadcrumbs(GameSet $gameSet): array
    {
        $breadcrumbs = [];

        $centralHub = GameSet::centralHub()->first();
        if ($centralHub) {
            $breadcrumbs[] = $this->toPathArray($centralHub);
        }

        $centralDifficultyHub = GameSet::whereTitle('[Central - Difficulty]')
            ->whereType(GameSetType::Hub)
            ->whereNull('deleted_at')
            ->first();
        if ($centralDifficultyHub) {
            $breadcrumbs[] = $this->toPathArray($centralDifficultyHub);
        }

        $breadcrumbs[] = $this->toPathArray($gameSet);

        return $breadcrumbs;
    }

    /**
     * Handles the special case of DevQuest paths.
     * These often have titles like "[DevQuest 021 Sets] Homebrew Heaven".
     * Notice the square brackets are oddly-placed.
     */
    private function handleDevQuestPath(GameSet $gameSet): array
    {
        // Add items in reverse order. We want them to appear as:
        // [Central] -> [Central - Developer Events] -> [Dev Events - DevQuest] -> [DevQuest Sets]

        $remainingPath = [$this->toPathArray($gameSet)];

        // Find and add the DevQuest hub.
        $devQuestHub = GameSet::where('title', '[Dev Events - DevQuest]')
            ->where('type', GameSetType::Hub)
            ->whereNull('deleted_at')
            ->first();

        if ($devQuestHub) {
            array_unshift($remainingPath, $this->toPathArray($devQuestHub));

            // Find and add the Developer Events hub.
            $devEventsHub = GameSet::where('title', '[Central - Developer Events]')
                ->where('type', GameSetType::Hub)
                ->whereNull('deleted_at')
                ->first();

            if ($devEventsHub) {
                array_unshift($remainingPath, $this->toPathArray($devEventsHub));
            }
        }

        // Add Central hub at the start of the path.
        $centralHub = GameSet::centralHub()->first();
        if ($centralHub) {
            array_unshift($remainingPath, $this->toPathArray($centralHub));
        }

        return $remainingPath;
    }

    /**
     * Process custom hierarchies for types not defined in `HUB_HIERARCHY`.
     *
     * This method handles special cases in the hub hierarchy that don't follow the
     * standard parent-child relationships defined in HUB_HIERARCHY. It has specific
     * logic for:
     *
     * 1. Regular hubs: walk up the chain of same-type parents until reaching a central hub.
     * 2. Subgenre hubs: skip parent chain and link directly to central hub for cleaner navigation.
     * 3: Misc. hubs: there are two different behaviors--
     *   - Simple Misc. hubs (eg: "[Misc. - Virtual Console]"): link directly to the central hub.
     *   - Nested Misc. hubs (eg: "[Misc. - Virtual Console - Nintendo 3DS]"): try to include
     *     intermediate parents to preserve the user's navigation context.
     *
     * The complexity here stems from balancing two competing needs:
     * - Keeping breadcrumbs short and direct for simple cases.
     * - Preserving important context in nested hierarchies.
     */
    private function processCustomHierarchy(
        GameSet $currentGameSet,
        string $currentType,
        string $mappedType,
        array &$visited,
        array &$remainingPath
    ): void {
        // Detect if this is a nested Misc. hub by counting title parts.
        $titleParts = explode(' - ', trim($currentGameSet->title, '[]'));
        $isNestedMisc = $currentType === 'Misc.' && count($titleParts) > 2;
        $isEventSubHub = (
            !str_starts_with($currentGameSet->title, '[Events - ')
            && str_starts_with($titleParts[0], 'Events - ')
        );

        if ($currentType !== 'Subgenre' && !($currentType === 'Misc.' && !$isNestedMisc)) {
            // Walk up the chain of parents until we can't find any more.
            while (true) {
                $sameTypeParent = $this->findSameTypeParent($currentGameSet, $currentType, $visited);

                if (!$sameTypeParent) {
                    break;
                }

                $visited[] = $sameTypeParent->id;
                array_unshift($remainingPath, $this->toPathArray($sameTypeParent));

                $currentGameSet = $sameTypeParent;
            }

            // For event sub-hubs, ensure we include the central community events hub.
            if (($isEventSubHub || str_starts_with($currentGameSet->title, '[Events - '))
                && !$this->hasCentralEventsHub($remainingPath)) {
                // Find the Central - Community Events hub.
                $centralEventsHub = GameSet::where('title', 'like', '%Central - Community Events%')
                    ->whereType(GameSetType::Hub)
                    ->whereNull('deleted_at')
                    ->first();

                if ($centralEventsHub && !in_array($centralEventsHub->id, $visited)) {
                    array_unshift($remainingPath, $this->toPathArray($centralEventsHub));
                    $visited[] = $centralEventsHub->id;
                }
            }
        }

        // Find and add the appropriate central hub.
        $centralParent = $this->findCentralParent($currentGameSet, $currentType, $mappedType, $visited);

        if ($centralParent) {
            array_unshift($remainingPath, $this->toPathArray($centralParent));
        }
    }

    /**
     * Process standard hierarchies for types specifically defined in `HUB_HIERARCHY`.
     */
    private function processStandardHierarchy(
        GameSet $currentGameSet,
        string $currentType,
        string $mappedType,
        array &$visited,
        array &$remainingPath
    ): void {
        while (isset(self::HUB_HIERARCHY[$currentType])) {
            $parentType = self::HUB_HIERARCHY[$currentType];

            $parent = $this->findParentByType($currentGameSet, $currentType, $parentType, $visited, $mappedType);
            if (!$parent) {
                break;
            }

            $visited[] = $parent->id;
            array_unshift($remainingPath, $this->toPathArray($parent));
            $currentGameSet = $parent;

            $currentType = $parentType;
        }
    }

    /**
     * Find a parent of the same type in the hub hierarchy.
     */
    private function findSameTypeParent(GameSet $gameSet, string $type, array $visited): ?GameSet
    {
        if ($type === 'Misc.') {
            // For titles like "[Misc. - Virtual Console - Nintendo 3DS]", find the parent "[Misc. - Virtual Console]".
            $titleParts = explode(' - ', trim($gameSet->title, '[]'));

            if (count($titleParts) > 2) {
                // Remove the last part to get the parent title pattern.
                array_pop($titleParts);
                $parentTitle = '[' . implode(' - ', $titleParts) . ']';

                $query = $gameSet->parents()
                    ->select('game_sets.*')
                    ->where('game_sets.type', GameSetType::Hub)
                    ->where('game_sets.title', $parentTitle)
                    ->whereNotIn('game_sets.id', $visited)
                    ->whereNull('game_sets.deleted_at');

                $parent = $query->first();

                return $parent;
            }

            return null;
        }

        // Extract the prefix/base name from the current hub's title.
        // eg: "RA Awards" from "[RA Awards - RA Awards 2021]"
        $titleParts = explode(' - ', trim($gameSet->title, '[]'));
        $basePrefix = $titleParts[0];

        // If we're looking at a specific event's sub-hub (eg: "[RA Awards - RA Awards 2021]"),
        // try to find its main event hub (eg: "[Events - RA Awards]").
        if (
            !array_key_exists($basePrefix, self::HUB_HIERARCHY)
            && !str_starts_with($gameSet->title, '[Events - ')
        ) {
            // First look for a parent event hub.
            $eventParent = $gameSet->parents()
                ->select('game_sets.*')
                ->where('game_sets.type', GameSetType::Hub)
                ->where('game_sets.title', 'like', '[Events - ' . $basePrefix . ']')
                ->whereNotIn('game_sets.id', $visited)
                ->whereNull('game_sets.deleted_at')
                ->first();

            if ($eventParent) {
                return $eventParent;
            }

            // If no direct event parent found, look for other hubs in the same event.
            $sameEventParent = $gameSet->parents()
                ->select('game_sets.*')
                ->where('game_sets.type', GameSetType::Hub)
                ->where('game_sets.title', 'like', '[' . $basePrefix . ' - %')
                ->whereNotIn('game_sets.id', $visited)
                ->whereNull('game_sets.deleted_at')
                ->first();

            if ($sameEventParent) {
                return $sameEventParent;
            }
        }

        // For event hubs, we'll connect to central community events through the standard hierarchy.
        if (str_starts_with($gameSet->title, '[Events - ')) {
            return null;
        }

        // Fall back to standard type-based parent finding.
        $query = $gameSet->parents()
            ->select('game_sets.*')
            ->where('game_sets.type', GameSetType::Hub)
            ->where('game_sets.title', 'like', '[' . $type . ' - %')
            ->whereNotIn('game_sets.id', $visited)
            ->whereNull('game_sets.deleted_at');

        return $query->first();
    }

    /**
     * Find the central parent hub for a given type.
     */
    private function findCentralParent(GameSet $gameSet, string $currentType, string $mappedType, array $visited): ?GameSet
    {
        // Try one of our naive mapped types first.
        $parent = $gameSet->parents()
            ->select('game_sets.*')
            ->where('game_sets.type', GameSetType::Hub)
            ->where('game_sets.title', 'like', self::PREFIX_CENTRAL . $mappedType . ']')
            ->whereNotIn('game_sets.id', $visited)
            ->whereNull('game_sets.deleted_at')
            ->first();

        // If the mapped type failed, try the original type.
        if (!$parent && $mappedType !== $currentType) {
            $parent = GameSet::whereTitle(self::PREFIX_CENTRAL . $currentType . ']')
                ->whereType(GameSetType::Hub)
                ->whereNull('deleted_at')
                ->first();
        }

        // Try the base type if the given type contains a pipe character.
        if (!$parent && str_contains($currentType, '|')) {
            $baseType = explode('|', $currentType)[0];
            $parent = GameSet::whereTitle(self::PREFIX_CENTRAL . $baseType . ']')
                ->whereType(GameSetType::Hub)
                ->whereNull('deleted_at')
                ->first();
        }

        // For special cases, try "Meta".
        if (!$parent && (str_contains($currentType, 'Meta') || str_contains($mappedType, 'Developer Compliance'))) {
            $parent = GameSet::whereTitle('[Central - Meta]')
                ->whereType(GameSetType::Hub)
                ->whereNull('deleted_at')
                ->first();
        }

        return $parent;
    }

    /**
     * Finds a parent by type according to the hierarchy.
     */
    private function findParentByType(
        GameSet $gameSet,
        string $currentType,
        string $parentType,
        array $visited,
        string $mappedType
    ): ?GameSet {
        if ($parentType === 'Central') {
            $parent = $gameSet->parents()
                ->select('game_sets.*')
                ->where('game_sets.type', GameSetType::Hub)
                ->where('game_sets.title', 'like', self::PREFIX_CENTRAL . $currentType . ']')
                ->whereNotIn('game_sets.id', $visited)
                ->whereNull('game_sets.deleted_at')
                ->first();

            if (!$parent && (str_contains($currentType, 'Meta') || str_contains($mappedType, 'Developer Compliance'))) {
                $parent = GameSet::where('title', '[Central - Meta]')
                    ->where('type', GameSetType::Hub)
                    ->whereNull('deleted_at')
                    ->first();
            }

            return $parent;
        }

        return $gameSet->parents()
            ->select('game_sets.*')
            ->where('game_sets.type', GameSetType::Hub)
            ->where('game_sets.title', 'like', '[' . $parentType . ' - %')
            ->whereNotIn('game_sets.id', $visited)
            ->whereNull('game_sets.deleted_at')
            ->first();
    }

    /**
     * Prepends the central hub to the path if it exists.
     */
    private function prependCentralHub(array &$remainingPath): void
    {
        $centralHub = GameSet::centralHub()->first();
        if ($centralHub) {
            array_unshift($remainingPath, $this->toPathArray($centralHub));
        }
    }

    private function hasCentralEventsHub(array $path): bool
    {
        foreach ($path as $entry) {
            if (trim($entry['title'], '[]') === 'Central - Community Events') {
                return true;
            }
        }

        return false;
    }
}
