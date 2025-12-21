<?php

declare(strict_types=1);

namespace App\Platform\Services;

class TriggerDiffService
{
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
     * Diff two arrays of conditions.
     *
     * @param array<int, array<string, mixed>> $oldConditions
     * @param array<int, array<string, mixed>> $newConditions
     * @return array<int, array<string, mixed>> conditions with DiffStatus and RowIndex added
     */
    private function diffConditions(array $oldConditions, array $newConditions): array
    {
        // Compare conditions by position since order matters in triggers.
        $result = [];
        $maxLen = max(count($oldConditions), count($newConditions));

        for ($i = 0; $i < $maxLen; $i++) {
            $old = $oldConditions[$i] ?? null;
            $new = $newConditions[$i] ?? null;
            $rowIndex = $i + 1;

            if ($old === null) {
                // This condition was added.
                $result[] = array_merge($new, [
                    'DiffStatus' => 'added',
                    'RowIndex' => $rowIndex,
                ]);
            } elseif ($new === null) {
                // This condition was removed.
                $result[] = array_merge($old, [
                    'DiffStatus' => 'removed',
                    'RowIndex' => $rowIndex,
                ]);
            } elseif ($this->conditionsMatch($old, $new)) {
                // These conditions are identical.
                $result[] = array_merge($new, [
                    'DiffStatus' => 'unchanged',
                    'RowIndex' => $rowIndex,
                ]);
            } else {
                // This condition was modified, so track what fields actually changed.
                $changedFields = $this->getChangedFields($old, $new);
                $result[] = array_merge($new, [
                    'DiffStatus' => 'modified',
                    'RowIndex' => $rowIndex,
                    'OldValues' => $old,
                    'ChangedFields' => $changedFields,
                ]);
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
        $keysToCompare = [
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

        $changed = [];
        foreach ($keysToCompare as $key) {
            if (($old[$key] ?? '') !== ($new[$key] ?? '')) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    /**
     * Check if two conditions are identical.
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private function conditionsMatch(array $a, array $b): bool
    {
        $keysToCompare = [
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

        foreach ($keysToCompare as $key) {
            if (($a[$key] ?? '') !== ($b[$key] ?? '')) {
                return false;
            }
        }

        return true;
    }
}
