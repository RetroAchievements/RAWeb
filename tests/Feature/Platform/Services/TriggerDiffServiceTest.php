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
        $groups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];

        $result = $this->service->computeDiff($groups, $groups);

        $this->assertCount(1, $result);
        $this->assertEquals('unchanged', $result[0]['DiffStatus']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
    }

    public function testComputeDiffDetectsAddedConditions(): void
    {
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

        $result = $this->service->computeDiff($oldGroups, $newGroups);

        $this->assertCount(1, $result);
        $this->assertEquals('modified', $result[0]['DiffStatus']);
        $this->assertCount(2, $result[0]['Conditions']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals('added', $result[0]['Conditions'][1]['DiffStatus']);
    }

    public function testComputeDiffDetectsRemovedConditions(): void
    {
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

        $result = $this->service->computeDiff($oldGroups, $newGroups);

        $this->assertCount(1, $result);
        $this->assertEquals('modified', $result[0]['DiffStatus']);
        $this->assertCount(2, $result[0]['Conditions']);
        $this->assertEquals('unchanged', $result[0]['Conditions'][0]['DiffStatus']);
        $this->assertEquals('removed', $result[0]['Conditions'][1]['DiffStatus']);
    }

    public function testComputeDiffDetectsModifiedConditions(): void
    {
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

        $result = $this->service->computeDiff($oldGroups, $newGroups);

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

        $result = $this->service->computeDiff($oldGroups, $newGroups);

        $this->assertCount(2, $result);
        $this->assertEquals('unchanged', $result[0]['DiffStatus']);
        $this->assertEquals('added', $result[1]['DiffStatus']);
        $this->assertEquals('Alt Group 1', $result[1]['Label']);
    }

    public function testComputeDiffDetectsRemovedAltGroup(): void
    {
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

        $result = $this->service->computeDiff($oldGroups, $newGroups);

        $this->assertCount(2, $result);
        $this->assertEquals('unchanged', $result[0]['DiffStatus']);
        $this->assertEquals('removed', $result[1]['DiffStatus']);
        $this->assertEquals('Alt Group 1', $result[1]['Label']);
    }

    public function testComputeSummaryCountsChanges(): void
    {
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

        $result = $this->service->computeSummary($oldGroups, $newGroups);

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
}
