<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Platform\Services\TriggerDiffService;
use Tests\TestCase;

class TriggerDiffServiceTest extends TestCase
{
    private TriggerDiffService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TriggerDiffService();
    }

    public function testComputeDiffWithEmptyGroups(): void
    {
        $result = $this->service->computeDiff([], []);

        $this->assertEmpty($result);
    }

    public function testComputeDiffWithIdenticalGroups(): void
    {
        // Arrange
        $groups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];

        // Act
        $result = $this->service->computeDiff($groups, $groups);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('unchanged', $result[0]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
    }

    public function testComputeDiffDetectsAddedConditions(): void
    {
        // Arrange
        $oldGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];
        $newGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x005678', 'Operator' => '>', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000005', 'HitTarget' => '0'],
                ],
            ],
        ];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('modified', $result[0]['DiffStatus']);
        $this->assertCount(2, $result[0]['Conditions']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals('added', $result[0]['Conditions'][1]['DiffStatus']);
    }

    public function testComputeDiffDetectsRemovedConditions(): void
    {
        // Arrange
        $oldGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x005678', 'Operator' => '>', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000005', 'HitTarget' => '0'],
                ],
            ],
        ];
        $newGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('modified', $result[0]['DiffStatus']);
        $this->assertCount(2, $result[0]['Conditions']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals('removed', $result[0]['Conditions'][1]['DiffStatus']);
    }

    public function testComputeDiffDetectsModifiedConditions(): void
    {
        // Arrange
        $oldGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];
        $newGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    // ... changed HitTarget from 0 to 10 ...
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '10'],
                ],
            ],
        ];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('modified', $result[0]['DiffStatus']);

        // ... the modified condition shows as single row with changed fields tracked ...
        $this->assertCount(1, $result[0]['Conditions']);
        $this->assertEquals('modified', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals(1, $result[0]['Conditions'][0]['RowIndex']);
        $this->assertEquals(['HitTarget'], $result[0]['Conditions'][0]['ChangedFields']);
        $this->assertEquals('0', $result[0]['Conditions'][0]['OldValues']['HitTarget']);
        $this->assertEquals('10', $result[0]['Conditions'][0]['HitTarget']);
    }

    public function testComputeDiffDetectsAddedAltGroup(): void
    {
        // Arrange
        $oldGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];
        $newGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
            [
                'Label' => 'Alt Group 1',
                'Conditions' => [
                    ['Flag' => 'Reset If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x009999', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'],
                ],
            ],
        ];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('unchanged', $result[0]['DiffStatus']);
        $this->assertEquals('added', $result[1]['DiffStatus']);
        $this->assertEquals('Alt Group 1', $result[1]['Label']);
    }

    public function testComputeDiffDetectsRemovedAltGroup(): void
    {
        // Arrange
        $oldGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
            [
                'Label' => 'Alt Group 1',
                'Conditions' => [
                    ['Flag' => 'Reset If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x009999', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'],
                ],
            ],
        ];
        $newGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('unchanged', $result[0]['DiffStatus']);
        $this->assertEquals('removed', $result[1]['DiffStatus']);
        $this->assertEquals('Alt Group 1', $result[1]['Label']);
    }

    public function testComputeSummaryCountsChanges(): void
    {
        // Arrange
        $oldGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x005678', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'],
                ],
            ],
        ];
        $newGroups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x009999', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000003', 'HitTarget' => '0'],
                ],
            ],
        ];

        // Act
        $result = $this->service->computeSummary($oldGroups, $newGroups);

        // Assert
        // ... the second condition had two field modifications ...
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['removed']);
        $this->assertEquals(1, $result['modified']);
    }

    public function testFormatSummaryWithAddedOnly(): void
    {
        $summary = ['added' => 3, 'removed' => 0, 'modified' => 0];

        $result = $this->service->formatSummary($summary);

        $this->assertEquals('3 added', $result);
    }

    public function testFormatSummaryWithRemovedOnly(): void
    {
        $summary = ['added' => 0, 'removed' => 2, 'modified' => 0];

        $result = $this->service->formatSummary($summary);

        $this->assertEquals('2 removed', $result);
    }

    public function testFormatSummaryWithMixedChanges(): void
    {
        $summary = ['added' => 3, 'removed' => 1, 'modified' => 2];

        $result = $this->service->formatSummary($summary);

        $this->assertEquals('3 added, 1 removed, 2 modified', $result);
    }

    public function testFormatSummaryWithNoChanges(): void
    {
        $summary = ['added' => 0, 'removed' => 0, 'modified' => 0];

        $result = $this->service->formatSummary($summary);

        $this->assertEquals('No changes', $result);
    }

    public function testComputeDiffDetectsConditionInsertedAtBeginning(): void
    {
        // Arrange
        // ... old has [A, B], new has [X, A, B] (X is inserted at the beginning) ...
        $conditionA = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'];
        $conditionB = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x005678', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'];
        $conditionX = ['Flag' => 'Reset If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x009999', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'];

        $oldGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB]]];
        $newGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionX, $conditionA, $conditionB]]];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        // ... X should be "added", A and B should be "unchanged" (shifted) ...
        $this->assertCount(3, $result[0]['Conditions']);
        $this->assertEquals('added', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][1]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][2]['DiffStatus']);
    }

    public function testComputeDiffDetectsConditionInsertedInMiddle(): void
    {
        // Arrange
        // ... old has [A, B, C, D], new has [A, B, X, C, D] (X is inserted at position 3) ...
        $conditionA = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001111', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'];
        $conditionB = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x002222', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'];
        $conditionC = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x003333', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000003', 'HitTarget' => '0'];
        $conditionD = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x004444', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000004', 'HitTarget' => '0'];
        $conditionX = ['Flag' => 'Reset If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x009999', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'];

        $oldGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB, $conditionC, $conditionD]]];
        $newGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB, $conditionX, $conditionC, $conditionD]]];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        // ... A, B should be unchanged, X added, C, D unchanged (shifted) ...
        $this->assertCount(5, $result[0]['Conditions']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][1]['DiffStatus']);
        $this->assertEquals('added', $result[0]['Conditions'][2]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][3]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][4]['DiffStatus']);
    }

    public function testComputeDiffDetectsConditionRemovedFromMiddle(): void
    {
        // Arrange
        // ... old has [A, B, C, D], new has [A, B, D] (C removed) ...
        $conditionA = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001111', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'];
        $conditionB = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x002222', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'];
        $conditionC = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x003333', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000003', 'HitTarget' => '0'];
        $conditionD = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x004444', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000004', 'HitTarget' => '0'];

        $oldGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB, $conditionC, $conditionD]]];
        $newGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB, $conditionD]]];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        // ... A, B unchanged, C removed, D unchanged (shifted) ...
        $this->assertCount(4, $result[0]['Conditions']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][1]['DiffStatus']);
        $this->assertEquals('removed', $result[0]['Conditions'][2]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][3]['DiffStatus']);
    }

    public function testComputeDiffHandlesMultipleInsertionsAndDeletions(): void
    {
        // Arrange
        // ... old has [A, B, C], new has [X, A, C, Y] (X added, B removed, Y added) ...
        $conditionA = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001111', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'];
        $conditionB = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x002222', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'];
        $conditionC = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x003333', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000003', 'HitTarget' => '0'];
        $conditionX = ['Flag' => 'Reset If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x009999', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'];
        $conditionY = ['Flag' => 'Pause If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x008888', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'];

        $oldGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB, $conditionC]]];
        $newGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionX, $conditionA, $conditionC, $conditionY]]];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        $summary = $this->service->computeSummary($oldGroups, $newGroups);
        $this->assertEquals(2, $summary['added']);
        $this->assertEquals(1, $summary['removed']);
        $this->assertEquals(0, $summary['modified']);
    }

    public function testComputeDiffHandlesInsertionWithModification(): void
    {
        // Arrange
        // ... old has [A, B], new has [X, A, B'] where B' is B with modified hit target ...
        // ... when an insertion shifts positions, modifications at shifted positions appear as removed+added ...
        $conditionA = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001111', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'];
        $conditionB = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x002222', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'];
        $conditionBModified = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x002222', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '10'];
        $conditionX = ['Flag' => 'Reset If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x009999', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'];

        $oldGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB]]];
        $newGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionX, $conditionA, $conditionBModified]]];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        // ... when positions shift, B appears as removed and B' appears as added ...
        $summary = $this->service->computeSummary($oldGroups, $newGroups);
        $this->assertEquals(2, $summary['added']);
        $this->assertEquals(1, $summary['removed']);
        $this->assertEquals(0, $summary['modified']);
    }

    public function testComputeDiffDetectsModificationAtSamePosition(): void
    {
        // Arrange
        // ... old has [A, B], new has [A, B'] where B' is B with modified hit target (no position shift) ...
        $conditionA = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001111', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'];
        $conditionB = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x002222', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'];
        $conditionBModified = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x002222', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '10'];

        $oldGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB]]];
        $newGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionBModified]]];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        // ... B at position 1 is paired with B' at position 1 as "modified" ...
        $summary = $this->service->computeSummary($oldGroups, $newGroups);
        $this->assertEquals(0, $summary['added']);
        $this->assertEquals(0, $summary['removed']);
        $this->assertEquals(1, $summary['modified']);
        $this->assertEquals(['HitTarget'], $result[0]['Conditions'][1]['ChangedFields']);
    }

    public function testComputeDiffGroupsRemovedConditionsBeforeUnchanged(): void
    {
        /**
         * Old: [A, B, C, D] -> New: [C, D, E, F]
         * Expected order: removed(A), removed(B), unchanged(C), unchanged(D), added(E), added(F)
         */

        // Arrange
        $conditionA = ['Flag' => '', 'SourceType' => 'Delta', 'SourceSize' => '16-bit', 'SourceAddress' => '0x001ec676', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x00ffff', 'HitTarget' => '0'];
        $conditionB = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '16-bit', 'SourceAddress' => '0x001ec676', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000036', 'HitTarget' => '0'];
        $conditionC = ['Flag' => 'Or Next', 'SourceType' => 'Mem', 'SourceSize' => '32-bit', 'SourceAddress' => '0x001ebf88', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x0e4897', 'HitTarget' => '0'];
        $conditionD = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '32-bit', 'SourceAddress' => '0x001ebf88', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x0e4898', 'HitTarget' => '0'];
        $conditionE = ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => 'Bit6', 'SourceAddress' => '0x001ec7ce', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'];
        $conditionF = ['Flag' => '', 'SourceType' => 'Delta', 'SourceSize' => 'Bit6', 'SourceAddress' => '0x001ec7ce', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000000', 'HitTarget' => '0'];

        $oldGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionA, $conditionB, $conditionC, $conditionD]]];
        $newGroups = [['Label' => 'Core Group', 'Conditions' => [$conditionC, $conditionD, $conditionE, $conditionF]]];

        // Act
        $result = $this->service->computeDiff($oldGroups, $newGroups);

        // Assert
        $conditions = $result[0]['Conditions'];
        $this->assertCount(6, $conditions);

        // ... removals should be grouped before the unchanged conditions they precede ...
        $this->assertEquals('removed', $conditions[0]['DiffStatus']);
        $this->assertEquals('0x001ec676', $conditions[0]['SourceAddress']); // A

        $this->assertEquals('removed', $conditions[1]['DiffStatus']);
        $this->assertEquals('0x001ec676', $conditions[1]['SourceAddress']); // B

        $this->assertEquals('unchanged', $conditions[2]['DiffStatus']);
        $this->assertEquals('Or Next', $conditions[2]['Flag']); // C

        $this->assertEquals('unchanged', $conditions[3]['DiffStatus']);
        $this->assertEquals('0x001ebf88', $conditions[3]['SourceAddress']); // D

        $this->assertEquals('added', $conditions[4]['DiffStatus']);
        $this->assertEquals('Bit6', $conditions[4]['SourceSize']); // E

        $this->assertEquals('added', $conditions[5]['DiffStatus']);
        $this->assertEquals('Delta', $conditions[5]['SourceType']); // F

        // ... verify the summary counts ...
        $summary = $this->service->computeSummary($oldGroups, $newGroups);
        $this->assertEquals(2, $summary['added']);
        $this->assertEquals(2, $summary['removed']);
        $this->assertEquals(0, $summary['modified']);
    }
}
