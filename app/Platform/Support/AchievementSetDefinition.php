<?php

declare(strict_types=1);

namespace App\Platform\Support;

/**
 * Helpers for reasoning about an achievement set's "definition" snapshot payload.
 *
 * A definition payload looks like:
 * ['version' => 1, 'achievements' => [['id' => int, 'points' => int, 'is_promoted' => bool, 'type' => ?string], ...]].
 */
final class AchievementSetDefinition
{
    /**
     * Current version of the stored definition shape. Bump this whenever the shape changes,
     * and record what changed below so a reader can tell an old payload from a new one and
     * migrate it.
     *
     * - v1: { version, achievements: [{ id, points, is_promoted, type }] }.
     */
    public const SCHEMA_VERSION = 1;

    /**
     * Pull just the promoted achievements out of a snapshot, as an `id => points` map.
     * Unpromoted achievements are skipped.
     *
     * The map is sorted by id so it can be compared with `===` against another snapshot's map:
     * if they are equal, the published set is identical (same achievements, same points), and if
     * they differ, something published changed. That comparison is how the version trigger decides
     * whether to fork a new version, and the same map gives it the published count (`count`) and
     * total points (`array_sum`) to store on the new row.
     *
     * @param array{version?: int, achievements?: array<int, array{id: int, points: int, is_promoted: bool, type: ?string}>}|null $definition
     * @return array<int, int> map of published achievement id => points, sorted by id
     */
    public static function publishedProjection(?array $definition): array
    {
        if ($definition === null) {
            return [];
        }

        $projection = [];
        foreach ($definition['achievements'] ?? [] as $achievement) {
            if (($achievement['is_promoted'] ?? false) === true) {
                $projection[(int) $achievement['id']] = (int) $achievement['points'];
            }
        }

        ksort($projection);

        return $projection;
    }
}
