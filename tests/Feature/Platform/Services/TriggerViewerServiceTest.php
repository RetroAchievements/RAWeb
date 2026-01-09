<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Platform\Services\TriggerViewerService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TriggerViewerServiceTest extends TestCase
{
    private TriggerViewerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TriggerViewerService();
    }

    #[DataProvider('flagColorClassProvider')]
    public function testGetFlagColorClassReturnsCorrectColorForFlag(string $flag, string $expectedColor): void
    {
        $result = $this->service->getFlagColorClass($flag);

        if ($expectedColor === '') {
            $this->assertEmpty($result);
        } else {
            $this->assertStringContainsString($expectedColor, $result);
        }
    }

    public static function flagColorClassProvider(): array
    {
        return [
            // Resets are red.
            'Reset If' => ['Reset If', 'text-red'],
            'Reset Next If' => ['Reset Next If', 'text-red'],

            // Pauses are amber.
            'Pause If' => ['Pause If', 'text-amber'],

            // Modifiers are purple.
            'Add Source' => ['Add Source', 'text-purple'],
            'Sub Source' => ['Sub Source', 'text-purple'],
            'Add Hits' => ['Add Hits', 'text-purple'],
            'Sub Hits' => ['Sub Hits', 'text-purple'],
            'Add Address' => ['Add Address', 'text-purple'],

            // Chains are cyan.
            'And Next' => ['And Next', 'text-cyan'],
            'Or Next' => ['Or Next', 'text-cyan'],

            // Measured conditions are emerald.
            'Measured' => ['Measured', 'text-emerald'],
            'Measured If' => ['Measured If', 'text-emerald'],
            'Measured %' => ['Measured %', 'text-emerald'],

            // Trigger conditions are sky.
            'Trigger' => ['Trigger', 'text-sky'],

            // R/R is pink.
            'Remember' => ['Remember', 'text-pink'],

            'Unknown Flag' => ['Unknown Flag', ''],
        ];
    }

    public function testResolveAddressAliasStripsNonPointerBrackets(): void
    {
        $tooltip = "[8-bit] Player Health";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertStringNotContainsString('[8-bit]', $result['alias']);
        $this->assertStringContainsString('Player Health', $result['alias']);
    }

    public function testResolveAddressAliasStripsMultipleBrackets(): void
    {
        $tooltip = "[32-bit] [128 bytes] Memory Block";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertStringNotContainsString('[32-bit]', $result['alias']);
        $this->assertStringNotContainsString('[128 bytes]', $result['alias']);
        $this->assertEquals('Memory Block', $result['alias']);
    }

    public function testResolveAddressAliasPreservesPointerBrackets(): void
    {
        $tooltip = "[16 bit Pointer] Various Pointer (Barrel 1st Boss, Switch on Final Boss etc)";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertStringContainsString('[Pointer]', $result['alias']);
    }

    public function testResolveAddressAliasNormalizesPointerVariants(): void
    {
        $variants = [
            "[32-bit pointer]" => "[Pointer]",
            "[Pointer to data]" => "[Pointer]",
            "[POINTER]" => "[Pointer]",
            "[16-bit Pointer]" => "[Pointer]",
        ];

        foreach ($variants as $input => $expected) {
            $result = $this->service->resolveAddressAlias("$input Test", []);

            $this->assertStringContainsString('[Pointer]', $result['alias']);
        }
    }

    public function testResolveAddressAliasNormalizesWhitespace(): void
    {
        $tooltip = "[8-bit]   Extra   Spaces   Here";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertEquals('Extra Spaces Here', $result['alias']);
    }

    public function testResolveAddressAliasExtractsDeltaSuffix(): void
    {
        $tooltip = "[0x001234 + 12]\n[128 bytes] Inventory";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertEquals(' +0xC', $result['deltaSuffix']);
        $this->assertStringContainsString('Inventory', $result['alias']);
    }

    public function testResolveAddressAliasHandlesZeroDeltaOffset(): void
    {
        $tooltip = "[0x001234 + 0]\nSome Field";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertEquals('', $result['deltaSuffix']);
    }

    public function testResolveAddressAliasHandlesLargeDeltaOffset(): void
    {
        $tooltip = "[0x001234 + 255]\n[8-bit] Some Value";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertEquals(' +0xFF', $result['deltaSuffix']);
    }

    public function testResolveAddressAliasResolvesIndirectAddress(): void
    {
        $tooltip = "[Indirect 0x001234 + 0x000064]\nNested Value";
        $groupNotes = [
            0x001234 => "[32-bit] pointer\n+0x00000064 | Field Name\n+0x00000068 | Other Field",
        ];

        $result = $this->service->resolveAddressAlias($tooltip, $groupNotes);

        $this->assertEquals('Field Name', $result['alias']);
    }

    public function testResolveAddressAliasResolvesDoubleIndirectAddress(): void
    {
        $tooltip = "[Indirect 0x001234 + 0x000004 + 0x000002]\n[16-bit] value2";
        $groupNotes = [
            0x001234 => "[32-bit] pointer\n+0x00000004 | nested pointer\n++0x00000002 | value2\n++0x00000004 | value3",
        ];

        $result = $this->service->resolveAddressAlias($tooltip, $groupNotes);

        $this->assertEquals('value2', $result['alias']);
    }

    public function testResolveAddressAliasResolvesTripleIndirectAddress(): void
    {
        /**
         * Tests depth 3 pointer traversal using the format found in real code notes.
         * Based on memory_notes ID 165752 from game 2646:
         * [32-bit]
         * +0x38
         * ++0xdc
         * +++0xec | Times talked to the epic guys
         *
         * NOTE: The tooltip's fallback value is intentionally different from the code note
         * so we can verify the pattern matching is working, not the fallback mechanism.
         */

        // The tooltip fallback says "fallback" but the code note has "Epic Guys Counter".
        // If pattern matching works, we get "Epic Guys Counter". If it falls back, we get "fallback".
        $tooltip = "[Indirect 0x001234 + 0x000038 + 0x0000dc + 0x0000ec]\nfallback";
        $groupNotes = [
            0x001234 => "[32-bit]\n+0x38 | first pointer\n++0xdc | second pointer\n+++0xec | Epic Guys Counter",
        ];

        $result = $this->service->resolveAddressAlias($tooltip, $groupNotes);

        $this->assertEquals('Epic Guys Counter', $result['alias']);
    }

    public function testResolveAddressAliasHandlesShortHexOffsetFormat(): void
    {
        /**
         * Tests that short hex format (++0xec instead of ++0x000000ec) is matched correctly.
         * Code notes in the DB often use compact hex notation without leading zeroes.
         */
        $tooltip = "[Indirect 0x001234 + 0x000064]\nfallback";
        $groupNotes = [
            0x001234 => "[32-bit] pointer\n+0x64 | Field Name\n+0x68 | Other Field",
        ];

        $result = $this->service->resolveAddressAlias($tooltip, $groupNotes);

        $this->assertEquals('Field Name', $result['alias']);
    }

    public function testResolveAddressAliasHandlesDecimalOffsetFormat(): void
    {
        /**
         * Tests that decimal format (++100 instead of ++0x00000064) is matched correctly.
         * Some code notes use decimal offsets without any hex prefix.
         */
        $tooltip = "[Indirect 0x001234 + 0x000064]\nfallback";
        $groupNotes = [
            // 0x64 = 100 decimal.
            0x001234 => "[32-bit] pointer\n+100 | Field Name\n+104 | Other Field",
        ];

        $result = $this->service->resolveAddressAlias($tooltip, $groupNotes);

        $this->assertEquals('Field Name', $result['alias']);
    }

    /**
     * Tests double indirect with short hex format used in real code notes.
     * Based on memory_notes from game 2657 which use formats like ++b47, ++02, etc.
     */
    public function testResolveAddressAliasHandlesDoubleIndirectWithShortHex(): void
    {
        $tooltip = "[Indirect 0x78ae50 + 0x00ffffb6fc + 0x00000b47]\nfallback";
        $groupNotes = [
            0x78AE50 => "[32-bit] Pointer Chain Wheel of Fortune\n+ffffb6fc | pointer to pointer\n++b47 | Wheel\n++b62 | Land Value",
        ];

        $result = $this->service->resolveAddressAlias($tooltip, $groupNotes);

        $this->assertEquals('Wheel', $result['alias']);
    }

    public function testResolveAddressAliasHandlesMissingIndirectNote(): void
    {
        $tooltip = "[Indirect 0x001234 + 0x000064]\nFallback";
        $groupNotes = [];

        $result = $this->service->resolveAddressAlias($tooltip, $groupNotes);

        $this->assertNotEmpty($result['alias']);
    }

    public function testResolveAddressAliasFallsBackOnEmptyFirstLine(): void
    {
        $tooltip = "\n[8-bit] Player Lives\n0=Dead\n1=Alive";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertEquals('Player Lives', $result['alias']);
    }

    public function testResolveAddressAliasSkipsSizeIndicatorLines(): void
    {
        $tooltip = "\n[128 bytes]\nActual Description";

        $result = $this->service->resolveAddressAlias($tooltip, []);

        $this->assertEquals('Actual Description', $result['alias']);
    }

    public function testResolveValueAliasReturnsFalseForBitFieldZero(): void
    {
        $result = $this->service->resolveValueAlias(0, '0x000000', 'Bit3', '');

        $this->assertEquals('false', $result);
    }

    public function testResolveValueAliasReturnsTrueForBitFieldOne(): void
    {
        $result = $this->service->resolveValueAlias(1, '0x000001', 'Bit3', '');

        $this->assertEquals('true', $result);
    }

    public function testResolveValueAliasReturnsLabelForBitFieldOne(): void
    {
        $noteSection = "Bit 3 = Has Key\nBit 4 = Has Sword";

        $result = $this->service->resolveValueAlias(1, '0x000001', 'Bit3', $noteSection);

        $this->assertEquals('Has Key', $result);
    }

    public function testResolveValueAliasReturnsNullForBitFieldValueGreaterThanOne(): void
    {
        $result = $this->service->resolveValueAlias(5, '0x000005', 'Bit3', '');

        $this->assertNull($result);
    }

    public function testResolveValueAliasBitFieldWithColonSeparator(): void
    {
        $noteSection = "Bit 2 : Has Armor";

        $result = $this->service->resolveValueAlias(1, '0x000001', 'Bit2', $noteSection);

        $this->assertEquals('Has Armor', $result);
    }

    public function testResolveValueAliasMatchesFloatValue(): void
    {
        $hexValue = '0x3f000000';
        $noteSection = "0.5 = Half Health\n1.0 = Full Health";

        $result = $this->service->resolveValueAlias(0, $hexValue, 'Float', $noteSection);

        $this->assertEquals('Half Health', $result);
    }

    public function testResolveValueAliasMatchesNegativeFloat(): void
    {
        $hexValue = '0xbf800000';
        $noteSection = "-1.0 = Reversed\n1.0 = Normal";

        $result = $this->service->resolveValueAlias(0, $hexValue, 'Float', $noteSection);

        $this->assertEquals('Reversed', $result);
    }

    public function testResolveValueAliasFloatWithEpsilonTolerance(): void
    {
        $hexValue = '0x3f000000';
        $noteSection = "0.5000001 = Close Enough";

        $result = $this->service->resolveValueAlias(0, $hexValue, 'Float', $noteSection);

        $this->assertEquals('Close Enough', $result);
    }

    public function testResolveValueAliasFloatBEDoesNotReverseBytes(): void
    {
        $hexValue = '0x0000003f';
        $noteSection = "0.5 = Half Health";

        $result = $this->service->resolveValueAlias(0, $hexValue, 'Float BE', $noteSection);

        $this->assertEquals('Half Health', $result);
    }

    public function testResolveValueAliasMatchesDecimalFormat(): void
    {
        $difficultyNote = "Difficulty\n0=practice\n1=normal\n2=difficult";

        $result = $this->service->resolveValueAlias(1, '0x000001', '8-bit', $difficultyNote);

        $this->assertEquals('normal', $result);
    }

    public function testResolveValueAliasMatchesHexFormat(): void
    {
        $zoneNote = "[8-bit] Current Zone\n" .
            "0x00=Green Hill Zone (first zone)\n" .
            "0x01=Labyrinth Zone (fourth zone)\n" .
            "0x02=Marble Zone (second zone)";

        $result = $this->service->resolveValueAlias(2, '0x000002', '8-bit', $zoneNote);

        $this->assertEquals('Marble Zone (second zone)', $result);
    }

    public function testResolveValueAliasMatchesHexWithLeadingZeroes(): void
    {
        $noteSection = "0x0010=With Zeroes";

        $result = $this->service->resolveValueAlias(16, '0x000010', '8-bit', $noteSection);

        $this->assertEquals('With Zeroes', $result);
    }

    public function testResolveValueAliasReturnsNullWhenNoMatch(): void
    {
        $noteSection = "0=Loading\n1=Title";

        $result = $this->service->resolveValueAlias(99, '0x000063', '8-bit', $noteSection);

        $this->assertNull($result);
    }

    public function testResolveValueAliasMatchesWithColonSeparator(): void
    {
        $noteSection = "0: Loading\n1: Title\n2: Gameplay";

        $result = $this->service->resolveValueAlias(1, '0x000001', '8-bit', $noteSection);

        $this->assertEquals('Title', $result);
    }

    public function testResolveValueAliasMatchesCharacter(): void
    {
        $characterNote = "Player 1 Character\n0x00=max\n0x02=axel\n0x04=blaze\n0x06=skate";

        $result = $this->service->resolveValueAlias(4, '0x000004', '8-bit', $characterNote);

        $this->assertEquals('blaze', $result);
    }

    public function testFormatOperandDisplayHandlesRecallType(): void
    {
        $condition = [
            'SourceType' => 'Recall',
            'SourceAddress' => '',
            'SourceTooltip' => '',
            'SourceSize' => '',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Source', []);

        $this->assertEquals('{recall}', $result['display']);
        $this->assertStringContainsString('text-pink', $result['cssClass']);
        $this->assertFalse($result['isAlias']);
    }

    public function testFormatOperandDisplayHandlesValueType(): void
    {
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => '',
            'SourceSize' => '8-bit',
            'TargetType' => 'Value',
            'TargetAddress' => '0x00000a',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Target', []);

        $this->assertEquals('10', $result['display']);
        $this->assertEquals('10', $result['decimalDisplay']);
        $this->assertEquals('0x00000a', $result['hexDisplay']);
    }

    public function testFormatOperandDisplayShowsAliasForAddressWithNote(): void
    {
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => '[8-bit] Player Health',
            'SourceSize' => '8-bit',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Source', []);

        $this->assertEquals('Player Health', $result['display']);
        $this->assertTrue($result['isAlias']);
        $this->assertNotNull($result['tooltip']);
    }

    public function testFormatOperandDisplayReturnsRawAddressWithoutNote(): void
    {
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => '',
            'SourceSize' => '8-bit',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Source', []);

        $this->assertEquals('0x001234', $result['display']);
        $this->assertFalse($result['isAlias']);
        $this->assertNull($result['tooltip']);
    }

    public function testFormatOperandDisplayResolvesValueAliasFromSourceNote(): void
    {
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x00f601',
            'SourceTooltip' => "Game State/Screen Mode [8-bit]\n0x00 = SEGA!!!\n0x04 = title menu\n0x0c = in-game",
            'SourceSize' => '8-bit',
            'TargetType' => 'Value',
            'TargetAddress' => '0x00000c',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Target', []);

        $this->assertEquals('in-game', $result['display']);
        $this->assertTrue($result['isAlias']);
        $this->assertEquals('in-game', $result['valueAlias']);
    }

    public function testGenerateMarkdownFormatsGroupsCorrectly(): void
    {
        $groups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    [
                        'Flag' => '',
                        'SourceType' => 'Mem',
                        'SourceSize' => '8-bit',
                        'SourceAddress' => '0x001234',
                        'Operator' => '=',
                        'TargetType' => 'Value',
                        'TargetSize' => '',
                        'TargetAddress' => '0x000001',
                        'HitTarget' => '0',
                    ],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('### Core', $result);
        $this->assertStringContainsString('0x00001234', $result);
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString('(0)', $result);
        $this->assertStringContainsString('```', $result);
    }

    public function testGenerateMarkdownIncludesHitTarget(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    [
                        'Flag' => '',
                        'SourceType' => 'Mem',
                        'SourceSize' => '8-bit',
                        'SourceAddress' => '0x001234',
                        'Operator' => '=',
                        'TargetType' => 'Value',
                        'TargetSize' => '',
                        'TargetAddress' => '0x000001',
                        'HitTarget' => '10',
                    ],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('(10)', $result);
    }

    public function testGenerateMarkdownShowsZeroForEmptyHitTarget(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    [
                        'Flag' => '',
                        'SourceType' => 'Mem',
                        'SourceSize' => '8-bit',
                        'SourceAddress' => '0x001234',
                        'Operator' => '=',
                        'TargetType' => 'Value',
                        'TargetSize' => '',
                        'TargetAddress' => '0x000001',
                        'HitTarget' => '',
                    ],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringNotContainsString('()', $result);
        $this->assertStringContainsString('(0)', $result);
    }

    public function testHasAddAddressFlagReturnsTrueWhenPresent(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => 'Add Address'],
                    ['Flag' => ''],
                ],
            ],
        ];

        $result = $this->service->hasAddAddressFlag($groups);

        $this->assertTrue($result);
    }

    public function testHasAddAddressFlagReturnsFalseWhenAbsent(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => ''],
                    ['Flag' => 'Reset If'],
                ],
            ],
        ];

        $result = $this->service->hasAddAddressFlag($groups);

        $this->assertFalse($result);
    }

    public function testHasAddAddressFlagHandlesEmptyGroups(): void
    {
        $groups = [];

        $result = $this->service->hasAddAddressFlag($groups);

        $this->assertFalse($result);
    }

    public function testComputeAddAddressChainsReturnsChainsForEndOfChainRows(): void
    {
        $conditions = [
            ['Flag' => 'Add Address'],
            ['Flag' => 'Add Address'],
            ['Flag' => ''],              // End of chain at row 3.
            ['Flag' => ''],              // Not in a chain.
            ['Flag' => 'Add Address'],
            ['Flag' => 'Reset If'],      // End of chain at row 6.
        ];

        $result = $this->service->computeAddAddressChains($conditions);

        $this->assertCount(2, $result);
        $this->assertEquals([1, 2], $result[3]);
        $this->assertEquals([5], $result[6]);
    }

    public function testComputeAddAddressChainsReturnsEmptyForNoChains(): void
    {
        $conditions = [
            ['Flag' => ''],
            ['Flag' => 'Reset If'],
            ['Flag' => 'Pause If'],
        ];

        $result = $this->service->computeAddAddressChains($conditions);

        $this->assertEmpty($result);
    }

    public function testComputeAddAddressChainsHandlesEmptyConditions(): void
    {
        $result = $this->service->computeAddAddressChains([]);

        $this->assertEmpty($result);
    }

    public function testGetAddressFormatReturns24BitFormatByDefault(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['SourceAddress' => '0x001234', 'TargetAddress' => '0x000001'],
                ],
            ],
        ];

        $result = $this->service->getAddressFormat($groups);

        $this->assertEquals('0x%06x', $result);
    }

    public function testGetAddressFormatReturns32BitFormatWhenSourceIs32Bit(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['SourceAddress' => '0x00123456', 'TargetAddress' => '0x000001'],
                ],
            ],
        ];

        $result = $this->service->getAddressFormat($groups);

        $this->assertEquals('0x%08x', $result);
    }

    public function testGetAddressFormatReturns32BitFormatWhenTargetIs32Bit(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['SourceAddress' => '0x001234', 'TargetAddress' => '0x00123456'],
                ],
            ],
        ];

        $result = $this->service->getAddressFormat($groups);

        $this->assertEquals('0x%08x', $result);
    }

    public function testGetAddressFormatHandlesEmptyGroups(): void
    {
        $groups = [];

        $result = $this->service->getAddressFormat($groups);

        $this->assertEquals('0x%06x', $result);
    }

    public function testResolveValueAliasWithPipeDelimiterFormat(): void
    {
        /**
         * Game State [8bit]
         * --- 00 | Loading
         * --- 01 | Title Screen
         * --- 02 | In Level
         */
        $noteSection = "Game State [8bit]\n--- 00 | Loading\n--- 01 | Title Screen\n--- 02 | In Level";

        $result = $this->service->resolveValueAlias(1, '0x000001', '8-bit', $noteSection);

        $this->assertEquals('Title Screen', $result);
    }

    public function testResolveValueAliasDetectsHexModeFrom0xPrefix(): void
    {
        $noteSection = "States\n0x00 = Loading\n14 = In Level\n0x15 = Game Over";

        $result = $this->service->resolveValueAlias(20, '0x000014', '8-bit', $noteSection);

        $this->assertEquals('In Level', $result);
    }

    public function testResolveValueAliasDetectsHexModeFromBareHexLetters(): void
    {
        // This test case asserts we've fixed a bug from AutoCR's parseEnumerations().
        // Bare hex with letters (like "0A") triggers hex mode even without 0x prefix.
        $noteSection = "Animation [8bit]\n--- 00 | None\n--- 0A | Castle\n--- 01 | Walk";

        // ... 0A hex = 10 decimal ...
        $result = $this->service->resolveValueAlias(10, '0x00000a', '8-bit', $noteSection);

        $this->assertEquals('Castle', $result);
    }

    public function testResolveValueAliasFallsBackWhenBelowEnumerationThreshold(): void
    {
        $noteSection = "Simple Note\n0 = Off\n1 = On";

        $result = $this->service->resolveValueAlias(1, '0x000001', '8-bit', $noteSection);

        $this->assertEquals('On', $result);
    }

    public function testResolveValueAliasHandlesMixedDelimiters(): void
    {
        $noteSection = "Screen Mode\n0 | Main Menu\n1 | Gameplay\n2 | Pause\n3 = Options";

        $result = $this->service->resolveValueAlias(2, '0x000002', '8-bit', $noteSection);

        $this->assertEquals('Pause', $result);
    }

    public function testResolveValueAliasHandlesLeadingDashesAndSpaces(): void
    {
        $noteSection = "Power Level\n  --- 00 | Off\n  --- 01 | Low\n  --- 02 | Medium\n  --- 03 | High";

        $result = $this->service->resolveValueAlias(2, '0x000002', '8-bit', $noteSection);

        $this->assertEquals('Medium', $result);
    }

    public function testResolveValueAliasHandlesSuperMarioWorldFormat(): void
    {
        $noteSection = "Player Animation State [8bit]\n--- 00 | Walking\n--- 14 | In Level\n--- 15 | Fade to Game Over / Time Up";

        $result = $this->service->resolveValueAlias(14, '0x00000e', '8-bit', $noteSection);

        $this->assertEquals('In Level', $result);
    }

    public function testResolveValueAliasReturnsNullWhenValueNotFound(): void
    {
        $noteSection = "States\n--- 00 | Loading\n--- 01 | Title\n--- 02 | Playing";

        $result = $this->service->resolveValueAlias(99, '0x000063', '8-bit', $noteSection);

        $this->assertNull($result);
    }

    public function testResolveValueAliasHandlesEqualsDelimiter(): void
    {
        $noteSection = "Difficulty\n0 = Easy\n1 = Normal\n2 = Hard\n3 = Expert";

        $result = $this->service->resolveValueAlias(2, '0x000002', '8-bit', $noteSection);

        $this->assertEquals('Hard', $result);
    }

    public function testResolveValueAliasHandlesColonDelimiter(): void
    {
        $noteSection = "Player Count\n0 : None\n1 : Single\n2 : Versus\n3 : Co-op";

        $result = $this->service->resolveValueAlias(3, '0x000003', '8-bit', $noteSection);

        $this->assertEquals('Co-op', $result);
    }

    public function testResolveValueAliasHandlesParenthesizedCsv(): void
    {
        $noteSection = "screen (2=logos,4=title song,6=menus,7=game)\nrace\n0=Championship, 1=Arcade, 2=Multiplayer";

        $result = $this->service->resolveValueAlias(7, '0x000007', '8-bit', $noteSection);

        $this->assertEquals('game', $result);
    }

    public function testResolveValueAliasHandlesLineLevelCsv(): void
    {
        $noteSection = "Mode Selection\n0=Championship, 1=Arcade, 2=Multiplayer";

        $result = $this->service->resolveValueAlias(0, '0x000000', '8-bit', $noteSection);

        $this->assertEquals('Championship', $result);
    }

    public function testResolveValueAliasHandlesLineLevelCsvMiddleValue(): void
    {
        $noteSection = "Mode Selection\n0=Championship, 1=Arcade, 2=Multiplayer";

        $result = $this->service->resolveValueAlias(1, '0x000001', '8-bit', $noteSection);

        $this->assertEquals('Arcade', $result);
    }

    public function testResolveValueAliasHandlesCsvWithColonDelimiter(): void
    {
        $noteSection = "States (0:Off, 1:On, 2:Paused)";

        $result = $this->service->resolveValueAlias(2, '0x000002', '8-bit', $noteSection);

        $this->assertEquals('Paused', $result);
    }

    public function testResolveValueAliasHandlesCsvWithSemicolonSeparator(): void
    {
        $noteSection = "Options (0=None; 1=Low; 2=High)";

        $result = $this->service->resolveValueAlias(1, '0x000001', '8-bit', $noteSection);

        $this->assertEquals('Low', $result);
    }

    public function testResolveValueAliasHandlesCsvWithSpacesInValues(): void
    {
        $noteSection = "screen (2=logos,4=title song,6=menus,7=game)";

        $result = $this->service->resolveValueAlias(4, '0x000004', '8-bit', $noteSection);

        $this->assertEquals('title song', $result);
    }

    public function testGenerateMarkdownUsesAutoCRGroupNames(): void
    {
        $groups = [
            [
                'Label' => 'Core Group',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
            [
                'Label' => 'Alt Group 1',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000002', 'HitTarget' => '0'],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('### Core', $result);
        $this->assertStringContainsString('### Alt 1', $result);
        $this->assertStringNotContainsString('Core Group', $result);
        $this->assertStringNotContainsString('Alt Group 1', $result);
    }

    public function testGenerateMarkdownConvertsInvertedToInvert(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Inverted', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Inverted', 'TargetSize' => '8-bit', 'TargetAddress' => '0x005678', 'HitTarget' => '0'],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('Invert', $result);
        $this->assertStringNotContainsString('Inverted', $result);
    }

    public function testGenerateMarkdownRemovesSpacesFromFlags(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => 'Add Address', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '', 'TargetType' => '', 'TargetSize' => '', 'TargetAddress' => '', 'HitTarget' => ''],
                    ['Flag' => 'Reset If', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '0'],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('AddAddress', $result);
        $this->assertStringContainsString('ResetIf', $result);
        $this->assertStringNotContainsString('Add Address', $result);
        $this->assertStringNotContainsString('Reset If', $result);
    }

    public function testGenerateMarkdownPadsAddressesToEightDigits(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x1234', 'Operator' => '=', 'TargetType' => 'Mem', 'TargetSize' => '8-bit', 'TargetAddress' => '0x5678', 'HitTarget' => '0'],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('0x00001234', $result);
        $this->assertStringContainsString('0x00005678', $result);
    }

    public function testGenerateMarkdownShowsDecimalForValueType(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x00000a', 'HitTarget' => '0'],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('10', $result);
        $this->assertStringNotContainsString('0x00000a', $result);
    }

    public function testGenerateMarkdownShowsRecallCondition(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => 'Remember', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '', 'TargetType' => '', 'TargetSize' => '', 'TargetAddress' => '', 'HitTarget' => ''],
                    ['Flag' => '', 'SourceType' => 'Recall', 'SourceSize' => '', 'SourceAddress' => '', 'Operator' => '=', 'TargetType' => 'Recall', 'TargetSize' => '', 'TargetAddress' => '', 'HitTarget' => '0'],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $this->assertStringContainsString('{recall}', $result);
    }

    public function testGenerateMarkdownHidesHitCountForScalableFlags(): void
    {
        $groups = [
            [
                'Label' => 'Core',
                'Conditions' => [
                    ['Flag' => 'Add Source', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '', 'TargetType' => '', 'TargetSize' => '', 'TargetAddress' => '', 'HitTarget' => ''],
                    ['Flag' => 'Sub Source', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '', 'TargetType' => '', 'TargetSize' => '', 'TargetAddress' => '', 'HitTarget' => ''],
                    ['Flag' => 'Add Address', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '', 'TargetType' => '', 'TargetSize' => '', 'TargetAddress' => '', 'HitTarget' => ''],
                    ['Flag' => 'Remember', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '', 'TargetType' => '', 'TargetSize' => '', 'TargetAddress' => '', 'HitTarget' => ''],
                    ['Flag' => '', 'SourceType' => 'Mem', 'SourceSize' => '8-bit', 'SourceAddress' => '0x001234', 'Operator' => '=', 'TargetType' => 'Value', 'TargetSize' => '', 'TargetAddress' => '0x000001', 'HitTarget' => '5'],
                ],
            ],
        ];

        $result = $this->service->generateMarkdown($groups);

        $lines = explode("\n", $result);
        $hitCountLines = array_filter($lines, fn ($line) => preg_match('/\(\d+\)/', $line));

        $this->assertCount(1, $hitCountLines);
        $this->assertStringContainsString('(5)', $result);
    }

    public function testFormatOperandDisplayTruncatesLongAliases(): void
    {
        $longAlias = 'Bit 6 = Denotes if you can move Serph or not. Useful for cutscenes.';
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => $longAlias,
            'SourceSize' => '8-bit',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Source', []);

        $this->assertEquals($longAlias, $result['display']);
        $this->assertTrue($result['isTruncated']);
        $this->assertEquals(43, strlen($result['displayTruncated'])); // 40 chars + "..."
        $this->assertStringEndsWith('...', $result['displayTruncated']);
    }

    public function testFormatOperandDisplayDoesNotTruncateShortAliases(): void
    {
        $shortAlias = 'Player Health';
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => "[8-bit] $shortAlias",
            'SourceSize' => '8-bit',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Source', []);

        $this->assertEquals($shortAlias, $result['display']);
        $this->assertEquals($shortAlias, $result['displayTruncated']);
        $this->assertFalse($result['isTruncated']);
    }

    public function testFormatOperandDisplayTruncatesLongValueAliases(): void
    {
        $noteSection = "Game Mode\n0x30 = This is a very long description that exceeds forty characters";
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => $noteSection,
            'SourceSize' => '8-bit',
            'TargetType' => 'Value',
            'TargetAddress' => '0x000030',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Target', []);

        $this->assertEquals('This is a very long description that exceeds forty characters', $result['valueAlias']);
        $this->assertTrue($result['isValueAliasTruncated']);
        $this->assertStringEndsWith('...', $result['valueAliasTruncated']);
    }

    public function testFormatOperandDisplayDoesNotTruncateShortValueAliases(): void
    {
        $noteSection = "Game Mode\n0x01 = Title Screen";
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => $noteSection,
            'SourceSize' => '8-bit',
            'TargetType' => 'Value',
            'TargetAddress' => '0x000001',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Target', []);

        $this->assertEquals('Title Screen', $result['valueAlias']);
        $this->assertEquals('Title Screen', $result['valueAliasTruncated']);
        $this->assertFalse($result['isValueAliasTruncated']);
    }

    public function testFormatOperandDisplayRecallTypeHasTruncationFields(): void
    {
        $condition = [
            'SourceType' => 'Recall',
            'SourceAddress' => '',
            'SourceTooltip' => '',
            'SourceSize' => '',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Source', []);

        $this->assertEquals('{recall}', $result['displayTruncated']);
        $this->assertFalse($result['isTruncated']);
        $this->assertNull($result['valueAliasTruncated']);
        $this->assertFalse($result['isValueAliasTruncated']);
    }

    public function testFormatOperandDisplayRawAddressHasTruncationFields(): void
    {
        $condition = [
            'SourceType' => 'Mem',
            'SourceAddress' => '0x001234',
            'SourceTooltip' => '',
            'SourceSize' => '8-bit',
        ];

        $result = $this->service->formatOperandDisplay($condition, 'Source', []);

        $this->assertEquals('0x001234', $result['displayTruncated']);
        $this->assertFalse($result['isTruncated']);
    }
}
