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
        } elseif ($end > 2 && ($mem[0] === 'f' || $mem[0] === 'F')) {
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
        } elseif ($end > 1 && ($mem[0] === 'h' || $mem[0] === 'H')) {
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
        } elseif ($end > 1 && ($mem[0] === '{')) {
            $type = 'Variable';

            $index = strpos($mem, '}');
            if ($index === false) {
                $value = substr($mem, 1);
            } else {
                $value = substr($mem, 1, $index - 1);
                $mem = substr($mem, $index + 1);
            }

            if ($value === 'recall') {
                $type = 'Recall';
                $value = '';
            }

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
            '*', '/', '&', '+', '-', '^', '%' => true,
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

    private function parseCondition(string $mem, bool $isValue): array
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
                case 'm': case 'M': $flag = 'Measured'; $scalable = $isValue; break;
                case 'q': case 'Q': $flag = 'Measured If'; break;
                case 'i': case 'I': $flag = 'Add Address'; $scalable = true; break;
                case 't': case 'T': $flag = 'Trigger'; break;
                case 'z': case 'Z': $flag = 'Reset Next If'; break;
                case 'g': case 'G': $flag = 'Measured %'; break;
                case 'k': case 'K': $flag = 'Remember'; $scalable = true; break;
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

    private function decodeTrigger(string $serializedTrigger, bool $isValue): array
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
            foreach ($reqs as $req) {
                if (empty($req)) {
                    continue;
                }

                $condition = $this->parseCondition($req, $isValue);
                $condition['IsIndirect'] = $isIndirect;

                if ($condition['SourceType'] === 'Value') {
                    $condition['SourceTooltip'] = (string) hexdec($condition['SourceAddress']);
                }
                if ($condition['TargetType'] === 'Value') {
                    $condition['TargetTooltip'] = (string) hexdec($condition['TargetAddress']);
                }

                $group['Conditions'][] = $condition;

                $isIndirect = $condition['Flag'] === 'Add Address';
            }

            $groups[] = $group;
        }

        return $groups;
    }

    public function decode(string $serializedTrigger): array
    {
        return $this->decodeTrigger($serializedTrigger, isValue: false);
    }

    public function addCodeNotes(array &$groups, int $gameId): void
    {
        $memoryReferences = [];
        foreach ($groups as &$group) {
            foreach ($group['Conditions'] as &$condition) {
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

        $codeNotes = MemoryNote::where('game_id', $gameId)
            ->where(function ($q) use ($memoryReferences) {
                $q->whereIn('address', $memoryReferences);
                $q->orWhere('body', 'like', '%bytes%');
            })
            ->get()
            ->mapWithKeys(function ($row, $key) {
                return [$row['address'] => $row['body']];
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
                $this->setTooltipFromNotes($condition, 'Source', $codeNotes, $indirectChain, $indirectNote, $groupNotes);
                $this->setTooltipFromNotes($condition, 'Target', $codeNotes, $indirectChain, $indirectNote, $groupNotes);

                if ($condition['Flag'] === 'Add Address' && ($condition['Operator'] === '' || $condition['Operator'] === '&')) {
                    $indirectNote = $condition['SourceTooltip'] ?? '';
                    [$delimiterIndex] = $this->findStructOffsetDelimiter($indirectNote);
                    if ($delimiterIndex !== false) {
                        // The presence of a struct offset delimiter indicates this is a pointer chain structure.
                        $firstLine = substr($indirectNote, 0, $delimiterIndex);
                        $condition['SourceTooltip'] = trim($firstLine);
                        if (empty($indirectChain)) {
                            $indirectChain = $condition['SourceAddress'];
                        } else {
                            $indirectChain .= ' + ' . $condition['SourceAddress'];
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

    private function setTooltipFromNotes(array &$condition, string $type, array $codeNotes,
        string $indirectChain, string $indirectNote, array &$groupNotes): void
    {
        if ($this->isMemoryReference($condition[$type . 'Type'])) {
            $formattedAddress = $condition[$type . 'Address'];
            $address = hexdec($formattedAddress);

            if (!empty($indirectNote)) {
                $note = $this->getIndirectNote($indirectNote, $address);
                if (!empty($note)) {
                    $condition[$type . 'Tooltip'] = "[Indirect $indirectChain + $formattedAddress]\n$note";
                }
            } elseif (array_key_exists($address, $codeNotes)) {
                $note = $codeNotes[$address];
                $note = $this->resolveNoteRedirects($note, $codeNotes);
                if (!empty($note)) {
                    if ($condition['IsIndirect']) {
                        $condition[$type . 'Tooltip'] = "[With indirection]\n" . $note;
                    } else {
                        $condition[$type . 'Tooltip'] = $note;
                    }
                }
                $groupNotes[$address] = $note;
            } else {
                $noteAddress = $this->findArrayNote($address, $codeNotes);
                if ($noteAddress !== null) {
                    $note = $codeNotes[$noteAddress] ?? '';
                    $note = $this->resolveNoteRedirects($note, $codeNotes);
                    if (!empty($note)) {
                        $formattedNoteAddress = '0x' . str_pad(dechex($noteAddress), 6, '0', STR_PAD_LEFT);
                        $offset = $address - $noteAddress;
                        $condition[$type . 'Tooltip'] = "[$formattedNoteAddress + $offset]\n" . $note;
                    }
                    $groupNotes[$noteAddress] = $note;
                }
            }
        }
    }

    /**
     * Finds the position of the first struct offset delimiter in a note.
     * Supports formats: "+0x0 |", "|0x0=", and "Description (+0x0)".
     *
     * @return array{0: int|false, 1: string} Position of newline and format type ('plus', 'pipe', 'paren', or '').
     */
    private function findStructOffsetDelimiter(string $note): array
    {
        $plusIndex = strpos($note, "\n+");
        $pipeIndex = strpos($note, "\n|");

        // For parenthesized format, look for "(+0x" anywhere after a newline.
        $parenIndex = preg_match('/\n.*\(\+0x/i', $note, $matches, PREG_OFFSET_CAPTURE)
            ? $matches[0][1]
            : false;

        // Find the earliest delimiter position.
        $candidates = [];
        if ($plusIndex !== false) {
            $candidates[$plusIndex] = 'plus';
        }
        if ($pipeIndex !== false) {
            $candidates[$pipeIndex] = 'pipe';
        }
        if ($parenIndex !== false) {
            $candidates[$parenIndex] = 'paren';
        }

        if (empty($candidates)) {
            return [false, ''];
        }

        $minIndex = min(array_keys($candidates));

        return [$minIndex, $candidates[$minIndex]];
    }

    private function getIndirectNote(string $parentNote, int $offset): string
    {
        [$delimiterIndex, $formatType] = $this->findStructOffsetDelimiter($parentNote);
        if ($delimiterIndex === false) {
            return '';
        }

        // for parenthesized format "Description (+0x184)", use regex to find matching lines
        if ($formatType === 'paren') {
            return $this->getIndirectNoteParenthesized($parentNote, $offset);
        }

        // for plus and pipe formats, use the line-based parsing
        $delimiterChar = $formatType === 'plus' ? '+' : '|';
        $delimiter = "\n" . $delimiterChar;

        $index = $delimiterIndex + 2;
        while (true) {
            $nextIndex = strpos($parentNote, $delimiter, $index);
            if ($nextIndex === false) {
                $line = trim(substr($parentNote, $index));
            } else {
                $line = trim(substr($parentNote, $index, $nextIndex - $index));
            }

            $len = strlen($line);
            if ($len > 3) {
                $charIndex = 0;
                while ($charIndex < $len && (
                    (($c = strtolower($line[$charIndex])) >= '0' && $c <= '9')
                    || ($c >= 'a' && $c <= 'f') || ($c == 'x'))) {
                    $charIndex++;
                }
                $lineOffset = intval(substr($line, 0, $charIndex), 0);
                if ($lineOffset === $offset) {
                    // found the applicable offset
                    // skip whitespace, any single non-alphanumeric character (like '|', '='), and whitespace
                    while ($charIndex < $len && ctype_space($line[$charIndex])) {
                        $charIndex++;
                    }
                    if ($charIndex < $len && !ctype_alnum($line[$charIndex])) {
                        $charIndex++;
                        while ($charIndex < $len && ctype_space($line[$charIndex])) {
                            $charIndex++;
                        }
                    }

                    $childNote = substr($line, $charIndex);

                    // for standard "+0x0 |" format, check for nested pointers (++, +++, etc)
                    if ($delimiterChar === '+') {
                        while ($nextIndex !== false && $parentNote[$nextIndex + 2] === '+') {
                            $charIndex = $nextIndex + 2;
                            $nextIndex = strpos($parentNote, $delimiter, $charIndex);
                            if ($nextIndex === false) {
                                $line = trim(substr($parentNote, $charIndex));
                            } else {
                                $line = trim(substr($parentNote, $charIndex, $nextIndex - $charIndex));
                            }
                            $childNote .= "\n" . $line;
                        }
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

    /**
     * Parses indirect notes in the "Description (+0x184)" format where the offset is at the end of the line.
     */
    private function getIndirectNoteParenthesized(string $parentNote, int $offset): string
    {
        $lines = explode("\n", $parentNote);

        foreach ($lines as $line) {
            // Match pattern: "Description (+0x184)" or "Description (+0x184 - extra info)"
            if (preg_match('/^(.+?)\s*\(\+?(0x[0-9a-fA-F]+)(?:\s*-[^)]+)?\)\s*$/', $line, $matches)) {
                $lineOffset = intval($matches[2], 0);
                if ($lineOffset === $offset) {
                    return trim($matches[1]);
                }
            }
        }

        return '';
    }

    private function resolveNoteRedirects(string $note, array $codeNotes): string
    {
        $redirects = 0;
        while ($redirects < 5) {
            if (preg_match('/refer to \$0x([0-9a-fA-F]+)/i', $note, $match)) {
                $targetAddr = hexdec($match[1]);
                $targetNote = $codeNotes[$targetAddr] ?? null;
                if ($targetNote) {
                    $note = $targetNote;
                    $redirects++;

                    continue;
                }
            }
            break;
        }

        return $note;
    }

    private function findArrayNote(int $address, array $codeNotes): ?int
    {
        foreach ($codeNotes as $noteAddress => $note) {
            if ($noteAddress > $address) {
                break;
            }

            $index = stripos($note, 'bytes');
            if ($index === false || stripos($note, 'pointer') !== false) {
                continue;
            }

            while ($index > 0 && (ctype_space($note[$index - 1]) || $note[$index - 1] === '-')) {
                $index--;
            }

            $size = 0;
            $multiplier = 1;
            while ($index > 0 && ctype_digit($note[$index - 1])) {
                $index--;
                $size += ((int) $note[$index]) * $multiplier;
                $multiplier *= 10;
            }

            if ($address < $noteAddress + $size) {
                return $noteAddress;
            }
        }

        return null;
    }

    public function decodeValue(string $serializedValue): array
    {
        // if it contains a colon, it's already in a trigger format (i.e. M:0xH001234)
        if (!Str::contains($serializedValue, ':')) {
            $serializedValue = $this->convertToTrigger($serializedValue);
        }

        $values = $this->decodeTrigger($serializedValue, isValue: true);

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
        $float_replace_pattern = '/(.*)[\*]([-]?\d+)\.(.*)/';
        $float_replace_replacement = '${1}*f${2}.${3}';

        // convert max_of elements to alt groups
        $parts = explode('$', $serializedValue);
        foreach ($parts as $part) {
            if ($result !== '') {
                $result .= 'S';
            }

            // convert addition chain to AddSource chain with Measured
            $clauses = explode('_', $part);
            $clausesCount = count($clauses);
            for ($i = 0; $i < $clausesCount; $i++) {
                // add 'f' prefix to float constants
                $clause = preg_replace($float_replace_pattern, $float_replace_replacement, $clauses[$i]);

                // remove comparison suffix - only modifying operators are allowed
                if (preg_match('/[<>=!]/', $clause, $matches, PREG_OFFSET_CAPTURE)) {
                    $clause = substr($clause, 0, $matches[0][1]);
                }

                // convert multiplication by negative values to SubSource, otherwise use AddSource
                if (Str::contains($clause, '*-')) {
                    $clause = 'B:' . str_replace('*-', '*', $clause);
                } elseif (Str::contains($clause, '*v-')) {
                    $clause = 'B:' . str_replace('*v-', '*', $clause);
                } elseif (Str::contains($clause, '*f-')) {
                    $clause = 'B:' . str_replace('*f-', '*f', $clause);
                } else {
                    $clause = 'A:' . $clause;
                }

                // if last clause is AddSource, convert to Measured, else append Measured
                if ($i === $clausesCount - 1) {
                    if ($clause[0] === 'A') {
                        $clause[0] = 'M';
                    } else {
                        $clause .= '_M:0';
                    }
                } else {
                    $clause .= '_';
                }

                // append clause
                $result .= $clause;
            }
        }

        return $result;
    }
}
