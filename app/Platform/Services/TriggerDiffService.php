<?php

declare(strict_types=1);

namespace App\Platform\Services;

class TriggerDiffService
{
    /**
     * Keys used when comparing conditions for equality.
     *
     * @var array<int, string>
     */
    private const COMPARABLE_KEYS = [
        'Flag',
        'SourceType',
        'SourceSize',
        'SourceAddress',
        'Operator',
        'TargetType',
        'TargetSize',
        'TargetAddress',
        'HitTarget',
    ];

    /**
     * Compute the detailed diff between two decoded trigger groups.
     * Returns groups with conditions marked with DiffStatus: 'added', 'removed', 'modified', 'unchanged'.
     *
     * @param array<int, array<string, mixed>> $oldGroups decoded groups from previous version
     * @param array<int, array<string, mixed>> $newGroups decoded groups from current version
     * @return array<int, array<string, mixed>> merged groups with DiffStatus on each condition
     */
    public function computeDiff(array $oldGroups, array $newGroups): array
    {
        $oldByLabel = $this->indexGroupsByLabel($oldGroups);
        $newByLabel = $this->indexGroupsByLabel($newGroups);

        $allLabels = array_unique(array_merge(array_keys($oldByLabel), array_keys($newByLabel)));

        // The core group needs to come first, followed by alt groups in ascending order.
        usort($allLabels, function ($a, $b) {
            if ($a === 'Core Group') {
                return -1;
            }
            if ($b === 'Core Group') {
                return 1;
            }

            return strnatcmp($a, $b);
        });

        $result = [];

        foreach ($allLabels as $label) {
            $oldGroup = $oldByLabel[$label] ?? null;
            $newGroup = $newByLabel[$label] ?? null;

            if ($oldGroup === null) {
                // This entire group was added.
                $result[] = [
                    'Label' => $label,
                    'DiffStatus' => 'added',
                    'Conditions' => array_map(
                        fn ($c) => array_merge($c, ['DiffStatus' => 'added']),
                        $newGroup['Conditions']
                    ),
                ];
            } elseif ($newGroup === null) {
                // This entire group was removed.
                $result[] = [
                    'Label' => $label,
                    'DiffStatus' => 'removed',
                    'Conditions' => array_map(
                        fn ($c) => array_merge($c, ['DiffStatus' => 'removed']),
                        $oldGroup['Conditions']
                    ),
                ];
            } else {
                // This group exists in both sets of logic, so we'll diff the conditions.
                $diffedConditions = $this->diffConditions(
                    $oldGroup['Conditions'],
                    $newGroup['Conditions']
                );

                $hasChanges = false;
                foreach ($diffedConditions as $c) {
                    if (($c['DiffStatus'] ?? 'unchanged') !== 'unchanged') {
                        $hasChanges = true;
                        break;
                    }
                }

                $result[] = [
                    'Label' => $label,
                    'DiffStatus' => $hasChanges ? 'modified' : 'unchanged',
                    'Conditions' => $diffedConditions,
                ];
            }
        }

        return $result;
    }

    /**
     * Compute diff summary metrics between two decoded group arrays.
     *
     * @param array<int, array<string, mixed>> $oldGroups decoded groups from the previous version
     * @param array<int, array<string, mixed>> $newGroups decoded groups from the current version
     * @return array{added: int, removed: int, modified: int}
     */
    public function computeSummary(array $oldGroups, array $newGroups): array
    {
        $diff = $this->computeDiff($oldGroups, $newGroups);

        $numAdded = 0;
        $numRemoved = 0;
        $numModified = 0;

        foreach ($diff as $group) {
            foreach ($group['Conditions'] as $condition) {
                switch ($condition['DiffStatus'] ?? 'unchanged') {
                    case 'added':
                        $numAdded++;
                        break;
                    case 'removed':
                        $numRemoved++;
                        break;
                    case 'modified':
                        $numModified++;
                        break;
                }
            }
        }

        return ['added' => $numAdded, 'removed' => $numRemoved, 'modified' => $numModified];
    }

    /**
     * Format the diff summary as a human-readable string.
     *
     * @param array{added: int, removed: int, modified: int} $summary
     */
    public function formatSummary(array $summary): string
    {
        $parts = [];

        if ($summary['added'] > 0) {
            $parts[] = $summary['added'] . ' added';
        }
        if ($summary['removed'] > 0) {
            $parts[] = $summary['removed'] . ' removed';
        }
        if ($summary['modified'] > 0) {
            $parts[] = $summary['modified'] . ' modified';
        }

        if (empty($parts)) {
            return 'No changes';
        }

        return implode(', ', $parts);
    }

    /**
     * Index groups by their label for easier lookup.
     *
     * @param array<int, array<string, mixed>> $groups
     * @return array<string, array<string, mixed>>
     */
    private function indexGroupsByLabel(array $groups): array
    {
        $indexed = [];
        foreach ($groups as $group) {
            $indexed[$group['Label']] = $group;
        }

        return $indexed;
    }

    /**
     * Create a hash string for a condition for fast comparison.
     *
     * @param array<string, mixed> $condition
     */
    private function hashCondition(array $condition): string
    {
        $parts = [];
        foreach (self::COMPARABLE_KEYS as $key) {
            $parts[] = $condition[$key] ?? '';
        }

        return implode('|', $parts);
    }

    /**
     * Diff two arrays of conditions using hybrid matching.
     *
     * Pass 1: Find exact matches greedily (handles insertions/deletions properly).
     * Pass 2: Pair remaining unmatched conditions by proximity to detect modifications.
     *
     * @param array<int, array<string, mixed>> $oldConditions
     * @param array<int, array<string, mixed>> $newConditions
     * @return array<int, array<string, mixed>> conditions with DiffStatus and RowIndex added
     */
    private function diffConditions(array $oldConditions, array $newConditions): array
    {
        // Pass 1: Build a hash map and find exact matches greedily.
        $oldByHash = [];
        foreach ($oldConditions as $i => $c) {
            $hash = $this->hashCondition($c);
            $oldByHash[$hash][] = $i;
        }

        $matchedOld = [];
        $matchedNew = [];
        $lastMatchedOldIdx = -1;
        foreach ($newConditions as $newIdx => $newCond) {
            $hash = $this->hashCondition($newCond);

            if (isset($oldByHash[$hash])) {
                foreach ($oldByHash[$hash] as $oldIdx) {
                    if ($oldIdx > $lastMatchedOldIdx && !isset($matchedOld[$oldIdx])) {
                        $matchedOld[$oldIdx] = $newIdx;
                        $matchedNew[$newIdx] = $oldIdx;
                        $lastMatchedOldIdx = $oldIdx;

                        break;
                    }
                }
            }
        }

        // Pass 2: Pair unmatched conditions for "modified" detection.
        $unmatchedOld = [];
        $unmatchedNew = [];
        for ($i = 0; $i < count($oldConditions); $i++) {
            if (!isset($matchedOld[$i])) {
                $unmatchedOld[] = $i;
            }
        }
        for ($i = 0; $i < count($newConditions); $i++) {
            if (!isset($matchedNew[$i])) {
                $unmatchedNew[] = $i;
            }
        }

        // Pair unmatched conditions as "modified" only when they're at the exact same position.
        $modifiedPairs = [];
        foreach ($unmatchedOld as $oldIdx) {
            if (in_array($oldIdx, $unmatchedNew, true)) {
                $modifiedPairs[$oldIdx] = $oldIdx;
            }
        }

        // Build the result using a two-pointer approach for proper ordering.
        // This ensures removals appear before the unchanged lines they precede.
        $result = [];
        $rowIndex = 1;
        $oldPtr = 0;
        $newPtr = 0;
        $processedNew = [];

        $oldCount = count($oldConditions);
        $newCount = count($newConditions);

        while ($oldPtr < $oldCount || $newPtr < $newCount) {
            $oldHasMore = $oldPtr < $oldCount;
            $newHasMore = $newPtr < $newCount;

            $oldIsMatched = $oldHasMore && isset($matchedOld[$oldPtr]);
            $newIsMatched = $newHasMore && isset($matchedNew[$newPtr]);

            // Current positions form a match pair (both point to each other).
            if (
                $oldIsMatched && $newIsMatched
                && $matchedOld[$oldPtr] === $newPtr
                && $matchedNew[$newPtr] === $oldPtr
            ) {
                $result[] = array_merge($newConditions[$newPtr], [
                    'DiffStatus' => 'unchanged',
                    'RowIndex' => $rowIndex++,
                ]);
                $oldPtr++;
                $newPtr++;

                continue;
            }

            // old is unmatched - output as removed (or modified if paired).
            if ($oldHasMore && !$oldIsMatched) {
                if (isset($modifiedPairs[$oldPtr])) {
                    $targetNewIdx = $modifiedPairs[$oldPtr];
                    $changedFields = $this->getChangedFields($oldConditions[$oldPtr], $newConditions[$targetNewIdx]);

                    $result[] = array_merge($newConditions[$targetNewIdx], [
                        'DiffStatus' => 'modified',
                        'RowIndex' => $rowIndex++,
                        'OldValues' => $oldConditions[$oldPtr],
                        'ChangedFields' => $changedFields,
                    ]);

                    $processedNew[$targetNewIdx] = true;
                } else {
                    $result[] = array_merge($oldConditions[$oldPtr], [
                        'DiffStatus' => 'removed',
                        'RowIndex' => $rowIndex++,
                    ]);
                }
                $oldPtr++;

                continue;
            }

            // old is matched but to a later new position.
            // Output any unmatched new conditions before that target.
            if ($oldIsMatched && $matchedOld[$oldPtr] > $newPtr) {
                while ($newPtr < $matchedOld[$oldPtr]) {
                    if (!isset($matchedNew[$newPtr]) && !isset($processedNew[$newPtr])) {
                        $result[] = array_merge($newConditions[$newPtr], [
                            'DiffStatus' => 'added',
                            'RowIndex' => $rowIndex++,
                        ]);

                        $processedNew[$newPtr] = true;
                    }

                    $newPtr++;
                }

                continue;
            }

            // new is unmatched - output as added.
            if ($newHasMore && !$newIsMatched && !isset($processedNew[$newPtr])) {
                $result[] = array_merge($newConditions[$newPtr], [
                    'DiffStatus' => 'added',
                    'RowIndex' => $rowIndex++,
                ]);

                $processedNew[$newPtr] = true;
                $newPtr++;

                continue;
            }

            // Otherwise, advance pointers for remaining matched-but-shifted cases.
            if ($newHasMore) {
                $newPtr++;
            } elseif ($oldHasMore) {
                $oldPtr++;
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Get the list of field names that differ between two conditions.
     *
     * @param array<string, mixed> $old
     * @param array<string, mixed> $new
     * @return array<int, string>
     */
    private function getChangedFields(array $old, array $new): array
    {
        $changed = [];
        foreach (self::COMPARABLE_KEYS as $key) {
            if (($old[$key] ?? '') !== ($new[$key] ?? '')) {
                $changed[] = $key;
            }
        }

        return $changed;
    }
}
