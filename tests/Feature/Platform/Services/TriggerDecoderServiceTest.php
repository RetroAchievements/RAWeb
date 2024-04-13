<?php

declare(strict_types=1);

namespace Tests\Feature\Platform\Services;

use App\Platform\Services\TriggerDecoderService;
use Tests\TestCase;

class TriggerDecoderServiceTest extends TestCase
{
    private function assertConditionFlag(array $condition, string $type): void
    {
        $this->assertEquals($type, $condition['Flag']);
    }

    private function assertConditionSourceOperand(array $condition, string $type, string $size, string $address): void
    {
        $this->assertEquals($type, $condition['SourceType']);
        $this->assertEquals($size, $condition['SourceSize']);
        $this->assertEquals($address, $condition['SourceAddress']);
    }

    private function assertConditionSourceTooltip(array $condition, string $tooltip): void
    {
        $this->assertEquals($tooltip, $condition['SourceTooltip'] ?? '');
    }

    private function assertConditionOperator(array $condition, string $operator): void
    {
        $this->assertEquals($operator, $condition['Operator']);
    }

    private function assertConditionTargetOperand(array $condition, string $type, string $size, string $address): void
    {
        $this->assertEquals($type, $condition['TargetType']);
        $this->assertEquals($size, $condition['TargetSize']);
        $this->assertEquals($address, $condition['TargetAddress']);
    }

    private function assertConditionTargetTooltip(array $condition, string $tooltip): void
    {
        $this->assertEquals($tooltip, $condition['TargetTooltip'] ?? '');
    }

    private function assertConditionHitTarget(array $condition, string $target): void
    {
        $this->assertEquals($target, $condition['HitTarget']);
    }

    private function parseSingleCondition(string $condition): array
    {
        $service = new TriggerDecoderService();

        $groups = $service->decode($condition);

        return $groups[0]['Conditions'][0];
    }

    private function assertParseOperand(string $operand, string $type, string $size, string $address): void
    {
        $condition = $this->parseSingleCondition($operand);
        $this->assertConditionSourceOperand($condition, $type, $size, $address);
    }

    public function testParseOperand(): void
    {
        $this->assertParseOperand('0xH1234', 'Mem', '8-bit', '0x001234');
        $this->assertParseOperand('0xh1234', 'Mem', '8-bit', '0x001234');
        $this->assertParseOperand('0x 1234', 'Mem', '16-bit', '0x001234');
        $this->assertParseOperand('0x1234', 'Mem', '16-bit', '0x001234');
        $this->assertParseOperand('0xW1234', 'Mem', '24-bit', '0x001234');
        $this->assertParseOperand('0xX1234', 'Mem', '32-bit', '0x001234');

        $this->assertParseOperand('0xL1234', 'Mem', 'Lower4', '0x001234');
        $this->assertParseOperand('0xU1234', 'Mem', 'Upper4', '0x001234');
        $this->assertParseOperand('0xM1234', 'Mem', 'Bit0', '0x001234');
        $this->assertParseOperand('0xN1234', 'Mem', 'Bit1', '0x001234');
        $this->assertParseOperand('0xO1234', 'Mem', 'Bit2', '0x001234');
        $this->assertParseOperand('0xP1234', 'Mem', 'Bit3', '0x001234');
        $this->assertParseOperand('0xQ1234', 'Mem', 'Bit4', '0x001234');
        $this->assertParseOperand('0xR1234', 'Mem', 'Bit5', '0x001234');
        $this->assertParseOperand('0xS1234', 'Mem', 'Bit6', '0x001234');
        $this->assertParseOperand('0xT1234', 'Mem', 'Bit7', '0x001234');

        $this->assertParseOperand('0xK1234', 'Mem', 'BitCount', '0x001234');

        $this->assertParseOperand('0xI1234', 'Mem', '16-bit BE', '0x001234');
        $this->assertParseOperand('0xJ1234', 'Mem', '24-bit BE', '0x001234');
        $this->assertParseOperand('0xG1234', 'Mem', '32-bit BE', '0x001234');

        $this->assertParseOperand('d0xH1234', 'Delta', '8-bit', '0x001234');
        $this->assertParseOperand('p0xH1234', 'Prior', '8-bit', '0x001234');
        $this->assertParseOperand('b0xH1234', 'BCD', '8-bit', '0x001234');
        $this->assertParseOperand('~0xH1234', 'Inverted', '8-bit', '0x001234');

        $this->assertParseOperand('fF1234', 'Mem', 'Float', '0x001234');
        $this->assertParseOperand('fB1234', 'Mem', 'Float BE', '0x001234');
        $this->assertParseOperand('fH1234', 'Mem', 'Double32', '0x001234');
        $this->assertParseOperand('fI1234', 'Mem', 'Double32 BE', '0x001234');
        $this->assertParseOperand('fM1234', 'Mem', 'MBF32', '0x001234');
        $this->assertParseOperand('fL1234', 'Mem', 'MBF32 LE', '0x001234');

        $this->assertParseOperand('1234', 'Value', '', '0x0004d2'); // raw value
        $this->assertParseOperand('h1234', 'Value', '', '0x001234'); // hex value
        $this->assertParseOperand('v1234', 'Value', '', '0x0004d2'); // legacy raw value
        $this->assertParseOperand('v-1234', 'Value', '', '0xfffffb2e'); // signed legacy raw value

        $this->assertParseOperand('f123.4', 'Float', '', '123.4');
        $this->assertParseOperand('f-5.432', 'Float', '', '-5.432');
        $this->assertParseOperand('f8.0', 'Float', '', '8.0');
    }

    public function testParseCondition(): void
    {
        $condition = $this->parseSingleCondition("0xH1234=6");
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001234');
        $this->assertConditionOperator($condition, '=');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000006');
        $this->assertConditionHitTarget($condition, '0');

        $condition = $this->parseSingleCondition("R:0xH1234>d0xH1234.5.");
        $this->assertConditionFlag($condition, 'Reset If');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001234');
        $this->assertConditionOperator($condition, '>');
        $this->assertConditionTargetOperand($condition, 'Delta', '8-bit', '0x001234');
        $this->assertConditionHitTarget($condition, '5');

        $condition = $this->parseSingleCondition("A:0xH1234*2");
        $this->assertConditionFlag($condition, 'Add Source');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001234');
        $this->assertConditionOperator($condition, '*');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000002');
        $this->assertConditionHitTarget($condition, '');
    }

    public function testParseConditionFlags(): void
    {
        $flags = [
            'P' => 'Pause If',
            'R' => 'Reset If',
            'C' => 'Add Hits',
            'D' => 'Sub Hits',
            'N' => 'And Next',
            'O' => 'Or Next',
            'M' => 'Measured',
            'Q' => 'Measured If',
            'T' => 'Trigger',
            'Z' => 'Reset Next If',
            'G' => 'Measured %',
        ];

        foreach ($flags as $flag => $text) {
            $condition = $this->parseSingleCondition("$flag:0xH1234=6");
            $this->assertConditionFlag($condition, $text);
            $this->assertConditionOperator($condition, '=');
        }

        $modifierFlags = [
            'A' => 'Add Source',
            'B' => 'Sub Source',
            'I' => 'Add Address',
        ];

        foreach ($modifierFlags as $flag => $text) {
            $condition = $this->parseSingleCondition("$flag:0xH1234");
            $this->assertConditionFlag($condition, $text);
            $this->assertConditionOperator($condition, '');
        }
    }

    public function testAddAddressChain(): void
    {
        $service = new TriggerDecoderService();
        $groups = $service->decode("I:0xX001234_A:0xH0000&63_I:0xX001234_0xH0001<7");

        $this->assertEquals(1, count($groups));
        $this->assertEquals(4, count($groups[0]['Conditions']));

        $condition = $groups[0]['Conditions'][0];
        $this->assertConditionFlag($condition, 'Add Address');
        $this->assertConditionSourceOperand($condition, 'Mem', '32-bit', '0x001234');
        $this->assertConditionOperator($condition, '');
        $this->assertConditionTargetOperand($condition, '', '', '');
        $this->assertConditionHitTarget($condition, '');

        $condition = $groups[0]['Conditions'][1];
        $this->assertConditionFlag($condition, 'Add Source');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x000000');
        $this->assertConditionOperator($condition, '&');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x00003f');
        $this->assertConditionHitTarget($condition, '');

        $condition = $groups[0]['Conditions'][2];
        $this->assertConditionFlag($condition, 'Add Address');
        $this->assertConditionSourceOperand($condition, 'Mem', '32-bit', '0x001234');
        $this->assertConditionOperator($condition, '');
        $this->assertConditionTargetOperand($condition, '', '', '');
        $this->assertConditionHitTarget($condition, '');

        $condition = $groups[0]['Conditions'][3];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x000001');
        $this->assertConditionOperator($condition, '<');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000007');
        $this->assertConditionHitTarget($condition, '0');
    }

    public function testAltGroups(): void
    {
        $service = new TriggerDecoderService();
        $groups = $service->decode("0xH1234=1S0xH2345<6S0xH3456>=9");

        $this->assertEquals(3, count($groups));
        $this->assertEquals(1, count($groups[0]['Conditions']));
        $this->assertEquals(1, count($groups[1]['Conditions']));
        $this->assertEquals(1, count($groups[2]['Conditions']));

        $this->assertEquals('Core Group', $groups[0]['Label']);
        $condition = $groups[0]['Conditions'][0];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001234');
        $this->assertConditionOperator($condition, '=');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000001');
        $this->assertConditionHitTarget($condition, '0');

        $this->assertEquals('Alt Group 1', $groups[1]['Label']);
        $condition = $groups[1]['Conditions'][0];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x002345');
        $this->assertConditionOperator($condition, '<');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000006');
        $this->assertConditionHitTarget($condition, '0');

        $this->assertEquals('Alt Group 2', $groups[2]['Label']);
        $condition = $groups[2]['Conditions'][0];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x003456');
        $this->assertConditionOperator($condition, '>=');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000009');
        $this->assertConditionHitTarget($condition, '0');
    }

    public function testMergeCodeNotes(): void
    {
        $service = new TriggerDecoderService();
        $groups = $service->decode("0xH1234>d0xH1234_0xH1235=36");

        $this->assertEquals(1, count($groups));
        $this->assertEquals(2, count($groups[0]['Conditions']));

        $condition = $groups[0]['Conditions'][0];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001234');
        $this->assertConditionSourceTooltip($condition, '');
        $this->assertConditionOperator($condition, '>');
        $this->assertConditionTargetOperand($condition, 'Delta', '8-bit', '0x001234');
        $this->assertConditionTargetTooltip($condition, '');
        $this->assertConditionHitTarget($condition, '0');

        $condition = $groups[0]['Conditions'][1];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001235');
        $this->assertConditionSourceTooltip($condition, '');
        $this->assertConditionOperator($condition, '=');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000024');
        $this->assertConditionTargetTooltip($condition, '36');
        $this->assertConditionHitTarget($condition, '0');

        $service->mergeCodeNotes($groups, [
            '0x001234' => 'Lives',
            '0x001236' => 'Unused',
        ]);

        $this->assertEquals($groups[0]['Notes'], [
            '0x001234' => 'Lives',
        ]);

        $condition = $groups[0]['Conditions'][0];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001234');
        $this->assertConditionSourceTooltip($condition, 'Lives');
        $this->assertConditionOperator($condition, '>');
        $this->assertConditionTargetOperand($condition, 'Delta', '8-bit', '0x001234');
        $this->assertConditionTargetTooltip($condition, 'Lives');
        $this->assertConditionHitTarget($condition, '0');

        $condition = $groups[0]['Conditions'][1];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '8-bit', '0x001235');
        $this->assertConditionSourceTooltip($condition, '');
        $this->assertConditionOperator($condition, '=');
        $this->assertConditionTargetOperand($condition, 'Value', '', '0x000024');
        $this->assertConditionTargetTooltip($condition, '36');
        $this->assertConditionHitTarget($condition, '0');
    }

    public function testMergeCodeNotesIndirect(): void
    {
        $service = new TriggerDecoderService();
        $groups = $service->decode("I:0xX1234_I:0xX0004_0x 0002!=0x 0000");
        $service->mergeCodeNotes($groups, [
            '0x001234' => "[32-bit] pointer\n" .
                          "+0 | [32-bit] index\n" .
                          "+4 | [32-bit] nested pointer\n" .
                          "++0 | [16-bit] value1\n" .
                          "++2 | [16-bit] value2\n" .
                          "++4 | [32-bit] link\n" .
                          "+8 | [32-bit] unused\n",
        ]);

        $this->assertEquals(1, count($groups));
        $this->assertEquals(3, count($groups[0]['Conditions']));

        $condition = $groups[0]['Conditions'][0];
        $this->assertConditionFlag($condition, 'Add Address');
        $this->assertConditionSourceOperand($condition, 'Mem', '32-bit', '0x001234');
        $this->assertConditionSourceTooltip($condition, '[32-bit] pointer');
        $this->assertConditionOperator($condition, '');
        $this->assertConditionTargetOperand($condition, '', '', '');
        $this->assertConditionTargetTooltip($condition, '');
        $this->assertConditionHitTarget($condition, '');

        $condition = $groups[0]['Conditions'][1];
        $this->assertConditionFlag($condition, 'Add Address');
        $this->assertConditionSourceOperand($condition, 'Mem', '32-bit', '0x000004');
        $this->assertConditionSourceTooltip($condition, "[Indirect 0x001234 + 0x000004]\n[32-bit] nested pointer");
        $this->assertConditionOperator($condition, '');
        $this->assertConditionTargetOperand($condition, '', '', '');
        $this->assertConditionTargetTooltip($condition, '');
        $this->assertConditionHitTarget($condition, '');

        $condition = $groups[0]['Conditions'][2];
        $this->assertConditionFlag($condition, '');
        $this->assertConditionSourceOperand($condition, 'Mem', '16-bit', '0x000002');
        $this->assertConditionSourceTooltip($condition, "[Indirect 0x001234 + 0x000004 + 0x000002]\n[16-bit] value2");
        $this->assertConditionOperator($condition, '!=');
        $this->assertConditionTargetOperand($condition, 'Mem', '16-bit', '0x000000');
        $this->assertConditionTargetTooltip($condition, "[Indirect 0x001234 + 0x000004 + 0x000000]\n[16-bit] value1");
        $this->assertConditionHitTarget($condition, '0');
    }
}
