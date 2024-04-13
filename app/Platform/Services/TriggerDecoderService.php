<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\MemoryNote;
use Illuminate\Support\Str;

class TriggerDecoderService
{
    private function parseOperand(string $mem): array
    {
        $end = strlen($mem);
        if ($end === 0) {
            return ['Invalid', '', '', ''];
        }

        $type = '';
        switch ($mem[0]) {
            case 'd': case 'D': $type = 'Delta'; $mem = substr($mem, 1); $end--; break;
            case 'p': case 'P': $type = 'Prior'; $mem = substr($mem, 1); $end--; break;
            case 'b': case 'B': $type = 'BCD'; $mem = substr($mem, 1); $end--; break;
            case '~':           $type = 'Inverted'; $mem = substr($mem, 1); $end--; break;
        }

        $size = '';
        if ($end > 3 && $mem[0] === '0' && $mem[1] === 'x') {
            switch ($mem[2]) {
                case 'h': case 'H': $size = '8-bit'; break;
                case ' ':           $size = '16-bit'; break;
                case 'x': case 'X': $size = '32-bit'; break;

                case 'm': case 'M': $size = 'Bit0'; break;
                case 'n': case 'N': $size = 'Bit1'; break;
                case 'o': case 'O': $size = 'Bit2'; break;
                case 'p': case 'P': $size = 'Bit3'; break;
                case 'q': case 'Q': $size = 'Bit4'; break;
                case 'r': case 'R': $size = 'Bit5'; break;
                case 's': case 'S': $size = 'Bit6'; break;
                case 't': case 'T': $size = 'Bit7'; break;
                case 'l': case 'L': $size = 'Lower4'; break;
                case 'u': case 'U': $size = 'Upper4'; break;
                case 'k': case 'K': $size = 'BitCount'; break;
                case 'w': case 'W': $size = '24-bit'; break;
                case 'g': case 'G': $size = '32-bit BE'; break;
                case 'i': case 'I': $size = '16-bit BE'; break;
                case 'j': case 'J': $size = '24-bit BE'; break;

                case '0': case '1': case '2': case '3': case '4':
                case '5': case '6': case '7': case '8': case '9':
                case 'a': case 'b': case 'c': case 'd': case 'e': case 'f':
                case 'A': case 'B': case 'C': case 'D': case 'E': case 'F':
                    // no size specified, implied 16-bit. convert to explicit
                    $size = '16-bit';
                    $mem = substr($mem, 0, 2) . ' ' . substr($mem, 2);
                    break;

                default:
                    $size = $mem[2];
                    break;
            }

            $mem = substr($mem, 3);
        } elseif ($end > 2 && $mem[0] === 'f' || $mem[0] === 'F') {
            switch ($mem[1]) {
                case 'f': case 'F': $size = 'Float'; break;
                case 'b': case 'B': $size = 'Float BE'; break;
                case 'h': case 'H': $size = 'Double32'; break;
                case 'i': case 'I': $size = 'Double32 BE'; break;
                case 'm': case 'M': $size = 'MBF32'; break;
                case 'l': case 'L': $size = 'MBF32 LE'; break;

                case '+': case '-': case '.':
                case '0': case '1': case '2': case '3': case '4':
                case '5': case '6': case '7': case '8': case '9':
                    $type = 'Float';
                    $count = 1;
                    if ($mem[1] === '+' || $mem[1] === '-') {
                        $count++;
                    }
                    while ($count < $end && ctype_digit($mem[$count])) {
                        $count++;
                    }
                    if ($count < $end && $mem[$count] === '.') {
                        $count++;
                        while ($count < $end && ctype_digit($mem[$count])) {
                            $count++;
                        }
                    }

                    $value = substr($mem, 1, $count - 1); // ignore 'f'
                    $mem = substr($mem, $count);

                    return [$type, $size, $value, $mem];

                default:
                    $size = $mem[1];
                    break;
            }

            $mem = substr($mem, 2);
        } elseif ($end > 1 && $mem[0] === 'h' || $mem[0] === 'H') {
            $type = 'Value';

            $mem = substr($mem, 1);
            $count = 0;
            $end = strlen($mem);
            while ($count < $end && ctype_alnum($mem[$count])) {
                $count++;
            }

            $value = '0x' . str_pad(substr($mem, 0, $count), 6, '0', STR_PAD_LEFT);
            $mem = substr($mem, $count);

            return [$type, $size, $value, $mem];
        } else {
            $type = 'Value';
            if ($mem[0] === 'v' || $mem[0] === 'V') {
                $mem = substr($mem, 1);
            }
            $count = 0;
            if ($mem[0] === '+' || $mem[0] === '-') {
                $count++;
            }
            $end = strlen($mem);
            while ($count < $end && ctype_digit($mem[$count])) {
                $count++;
            }
            if ($count < $end && $mem[$count] === '.') {
                $hitsStart = $count;
                $count++;
                while ($count < $end && ctype_digit($mem[$count])) {
                    $count++;
                }
                if ($count < $end && $mem[$count] === '.') {
                    $count = $hitsStart;
                } else {
                    $size = 'Float';
                }
            }

            $padded = str_pad(dechex((int) substr($mem, 0, $count)), 6, '0', STR_PAD_LEFT);
            if (strlen($padded) > 8) {
                $padded = substr($padded, -8);
            }
            $value = '0x' . $padded;
            $mem = substr($mem, $count);

            return [$type, $size, $value, $mem];
        }

        if (!$type) {
            $type = 'Mem';
        }

        $count = 0;
        $end = strlen($mem);
        while ($count < $end && ctype_alnum($mem[$count])) {
            $count++;
        }

        $address = '0x' . str_pad(substr($mem, 0, $count), 6, '0', STR_PAD_LEFT);
        $mem = substr($mem, $count);

        return [$type, $size, $address, $mem];
    }

    private function isScalerOperator(string $cmp): bool
    {
        return match ($cmp) {
            '*', '/', '&' => true,
            default => false,
        };
    }

    private function isMemoryReference(string $type): bool
    {
        return match ($type) {
            'Value', 'Float', '' => false,
            default => true,
        };
    }

    private function parseCondition(string $mem): array
    {
        $flag = '';
        $lType = '';
        $lSize = '';
        $lMemory = '';
        $cmp = '';
        $rType = '';
        $rSize = '';
        $rMemVal = '';
        $hits = '';
        $scalable = false;

        if (strlen($mem) > 2 && $mem[1] === ':') {
            switch ($mem[0]) {
                case 'p': case 'P': $flag = 'Pause If'; break;
                case 'r': case 'R': $flag = 'Reset If'; break;
                case 'a': case 'A': $flag = 'Add Source'; $scalable = true; break;
                case 'b': case 'B': $flag = 'Sub Source'; $scalable = true; break;
                case 'c': case 'C': $flag = 'Add Hits'; break;
                case 'd': case 'D': $flag = 'Sub Hits'; break;
                case 'n': case 'N': $flag = 'And Next'; break;
                case 'o': case 'O': $flag = 'Or Next'; break;
                case 'm': case 'M': $flag = 'Measured'; break;
                case 'q': case 'Q': $flag = 'Measured If'; break;
                case 'i': case 'I': $flag = 'Add Address'; $scalable = true; break;
                case 't': case 'T': $flag = 'Trigger'; break;
                case 'z': case 'Z': $flag = 'Reset Next If'; break;
                case 'g': case 'G': $flag = 'Measured %'; break;
                default: $flag = $mem[0]; break;
            }

            $mem = substr($mem, 2);
        }

        [$lType, $lSize, $lMemory, $mem] = $this->parseOperand($mem);

        if (strlen($mem) === 0) {
            // no operator
        } elseif ($scalable && !$this->isScalerOperator($mem[0])) {
            $mem = ''; // non-scaler operator ignored for scalable operations
        } else {
            $cmp = $mem[0];
            $cmplen = 1;
            switch ($mem[0]) {
                case '=':
                    if ($mem[1] === '=') {
                        $cmplen = 2;
                    }
                    break;

                case '!':
                    if ($mem[1] === '=') {
                        $cmp = '!=';
                        $cmplen = 2;
                    }
                    break;

                case '<':
                    if ($mem[1] === '=') {
                        $cmp = '<=';
                        $cmplen = 2;
                    }
                    break;

                case '>':
                    if ($mem[1] === '=') {
                        $cmp = '>=';
                        $cmplen = 2;
                    }
                    break;
            }
            $mem = substr($mem, $cmplen);

            [$rType, $rSize, $rMemVal, $mem] = $this->parseOperand($mem);

            $hits = $scalable ? '' : '0';
            if (strlen($mem) > 0 && ($mem[0] === '(' || $mem[0] === '.')) {
                $hits = substr($mem, 1, strlen($mem) - 2);
            }
        }

        return [
            'Flag' => $flag,
            'SourceType' => $lType,
            'SourceSize' => $lSize,
            'SourceAddress' => $lMemory,
            'Operator' => $cmp,
            'TargetType' => $rType,
            'TargetSize' => $rSize,
            'TargetAddress' => $rMemVal,
            'HitTarget' => $hits,
        ];
    }

    public function decode(string $serializedTrigger): array
    {
        $groups = [];

        // separating CoreGroup and AltGroups
        $serializedGroups = preg_split("/(?<!0x)[S$]/", $serializedTrigger);
        $groupsCount = is_countable($serializedGroups) ? count($serializedGroups) : 0;
        for ($i = 0; $i < $groupsCount; $i++) {
            $group = [
                'Label' => ($i === 0) ? 'Core Group' : "Alt Group $i",
                'Conditions' => [],
            ];

            $reqs = explode('_', $serializedGroups[$i]);
            $reqsCount = count($reqs);
            $isIndirect = false;
            for ($j = 0; $j < $reqsCount; $j++) {
                if (empty($reqs[$j])) {
                    continue;
                }

                $condition = $this->parseCondition($reqs[$j]);
                $condition['IsIndirect'] = $isIndirect;

                if ($condition['SourceType'] === 'Value') {
                    $condition['SourceTooltip'] = hexdec($condition['SourceAddress']);
                }
                if ($condition['TargetType'] === 'Value') {
                    $condition['TargetTooltip'] = hexdec($condition['TargetAddress']);
                }

                $group['Conditions'][] = $condition;

                $isIndirect = $condition['Flag'] === 'Add Address';
            }

            $groups[] = $group;
        }

        return $groups;
    }

    public function addCodeNotes(array &$groups, int $gameId): void
    {
        $memoryReferences = [];
        foreach ($groups as &$group) {
            foreach ($group['Conditions'] as &$condition) {
                if (!$condition['IsIndirect']) {
                    if ($this->isMemoryReference($condition['SourceType'])) {
                        $address = hexdec($condition['SourceAddress']);
                        if (!in_array($address, $memoryReferences)) {
                            $memoryReferences[] = $address;
                        }
                    }

                    if ($this->isMemoryReference($condition['TargetType'])) {
                        $address = hexdec($condition['TargetAddress']);
                        if (!in_array($address, $memoryReferences)) {
                            $memoryReferences[] = $address;
                        }
                    }
                }
            }
        }

        $codeNotes = MemoryNote::where('GameID', $gameId)
            ->whereIn('Address', $memoryReferences)
            ->get()
            ->mapWithKeys(function ($row, $key) {
                return [sprintf("0x%06x", $row['Address']) => $row['Note']];
            })
            ->toArray();

        $this->mergeCodeNotes($groups, $codeNotes);
    }

    public function mergeCodeNotes(array &$groups, array $codeNotes): void
    {
        foreach ($groups as &$group) {
            $groupNotes = [];
            $indirectNote = '';
            $indirectChain = '';
            foreach ($group['Conditions'] as &$condition) {
                if (!$condition['IsIndirect']) {
                    if ($this->isMemoryReference($condition['SourceType'])) {
                        $address = $condition['SourceAddress'];
                        if (array_key_exists($address, $codeNotes)) {
                            $note = $codeNotes[$address];
                            $condition['SourceTooltip'] = $note;
                            $groupNotes[$address] = $note;
                        }
                    }

                    if ($this->isMemoryReference($condition['TargetType'])) {
                        $address = $condition['TargetAddress'];
                        if (array_key_exists($address, $codeNotes)) {
                            $note = $codeNotes[$address];
                            $condition['TargetTooltip'] = $note;
                            $groupNotes[$address] = $note;
                        }
                    }
                } elseif (!empty($indirectNote)) {
                    if ($this->isMemoryReference($condition['SourceType'])) {
                        $address = $condition['SourceAddress'];
                        $note = $this->getIndirectNote($indirectNote, hexdec($address));
                        if (!empty($note)) {
                            $condition['SourceTooltip'] = "[Indirect $indirectChain + $address]\n$note";
                        }
                    }

                    if ($this->isMemoryReference($condition['TargetType'])) {
                        $address = $condition['TargetAddress'];
                        $note = $this->getIndirectNote($indirectNote, hexdec($address));
                        if (!empty($note)) {
                            $condition['TargetTooltip'] = "[Indirect $indirectChain + $address]\n$note";
                        }
                    }
                }

                if ($condition['Flag'] === 'Add Address' && ($condition['Operator'] === '' || $condition['Operator'] === '&')) {
                    $indirectNote = $condition['SourceTooltip'] ?? '';
                    $index = strpos($indirectNote, "\n+");
                    if ($index !== false) {
                        $firstLine = substr($indirectNote, 0, $index);
                        if (!empty($firstLine) && stripos($firstLine, 'pointer') !== false) {
                            $condition['SourceTooltip'] = trim($firstLine);
                            if (empty($indirectChain)) {
                                $indirectChain = $condition['SourceAddress'];
                            } else {
                                $indirectChain .= ' + ' . $condition['SourceAddress'];
                            }
                        } else {
                            $indirectNote = '';
                            $indirectChain = '';
                        }
                    }
                } else {
                    $indirectNote = '';
                    $indirectChain = '';
                }
            }
            $group['Notes'] = $groupNotes;
        }
    }

    private function getIndirectNote(string $parentNote, int $offset): string
    {
        $index = strpos($parentNote, "\n+");
        if ($index === false) {
            return ''; // unexpected - should have already been matched to reach this code
        }

        $index += 2;
        while (true) {
            $nextIndex = strpos($parentNote, "\n+", $index);
            if ($nextIndex === false) {
                $line = trim(substr($parentNote, $index));
            } else {
                $line = trim(substr($parentNote, $index, $nextIndex - $index));
            }

            $len = strlen($line);
            if ($len > 3) {
                $index = 0;
                while ($index < $len && (
                    (($c = strtolower($line[$index])) >= '0' && $c <= '9')
                    || ($c >= 'a' && $c <= 'f') || ($c == 'x'))) {
                    $index++;
                }
                $lineOffset = intval(substr($line, 0, $index), 0);
                if ($lineOffset === $offset) {
                    // found the applicable offset
                    // skip whitespace, any single non-alphanumeric character, and whitespace
                    while ($index < $len && ctype_space($line[$index])) {
                        $index++;
                    }
                    if ($index < $len && !ctype_alnum($line[$index])) {
                        $index++;
                        while ($index < $len && ctype_space($line[$index])) {
                            $index++;
                        }
                    }

                    $childNote = substr($line, $index);

                    while ($nextIndex !== false && $parentNote[$nextIndex + 2] === '+') {
                        $index = $nextIndex + 2;
                        $nextIndex = strpos($parentNote, "\n+", $index);
                        if ($nextIndex === false) {
                            $line = trim(substr($parentNote, $index));
                        } else {
                            $line = trim(substr($parentNote, $index, $nextIndex - $index));
                        }
                        $childNote .= "\n" . $line;
                    }

                    return $childNote;
                }
            }

            if ($nextIndex === false) {
                break;
            }

            $index = $nextIndex + 2;
        }

        return '';
    }

    public function decodeValue(string $serializedValue): array
    {
        // if it contains a colon, it's already in a trigger format (i.e. M:0xH001234)
        if (!Str::contains($serializedValue, ':')) {
            $serializedValue = $this->convertToTrigger($serializedValue);
        }

        $values = $this->decode($serializedValue);

        $numValues = count($values);
        if ($numValues === 1) {
            $values[0]['Label'] = 'Value';
        } else {
            for ($i = 0; $i < $numValues; $i++) {
                $values[$i]['Label'] = 'Value ' . ($i + 1);
            }
        }

        return $values;
    }

    private function convertToTrigger(string $serializedValue): string
    {
        $result = '';

        // regex to change "0xH001234*0.75" to "0xH001234*f0.75"
        $float_replace_pattern = '/(.*)[\*](\d+)\.(.*)/';
        $float_replace_replacement = '${1}*f${2}.${3}';

        // convert max_of elements to alt groups
        $parts = explode('$', $serializedValue);
        foreach ($parts as $part) {
            if (count($parts) > 1) {
                $result .= 'S';
            }

            // convert addition chain to AddSource chain with Measured
            $clauses = explode('_', $part);
            $clausesCount = count($clauses);
            for ($i = 0; $i < $clausesCount - 1; $i++) {
                $clause = preg_replace($float_replace_pattern, $float_replace_replacement, $clauses[$i]);
                if (Str::contains($clause, '*-')) {
                    $result .= 'B:' . str_replace('*-', '*', $clause) . '_';
                } elseif (Str::contains($clause, '*v-')) {
                    $result .= 'B:' . str_replace('*v-', '*', $clause) . '_';
                } else {
                    $result .= 'A:' . $clause . '_';
                }
            }

            $clause = preg_replace($float_replace_pattern, $float_replace_replacement, $clauses[count($clauses) - 1]);
            if (Str::contains($clause, '*-')) {
                $result .= 'B:' . str_replace('*-', '*', $clause) . '_M:0';
            } elseif (Str::contains($clause, '*v-')) {
                $result .= 'B:' . str_replace('*v-', '*', $clause) . '_M:0';
            } else {
                $result .= 'M:' . $clause;
            }
        }

        return $result;
    }
}
