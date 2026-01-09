<?php

declare(strict_types=1);

namespace App\Platform\Services;

use Illuminate\Support\Str;

class TriggerViewerService
{
    private const SCALABLE_FLAGS = ['AddSource', 'SubSource', 'AddAddress', 'Remember'];

    private const ALIAS_TRUNCATE_LENGTH = 40;

    private const FLAG_COLORS = [
        'Reset If' => 'text-red-500 dark:text-red-400',
        'Reset Next If' => 'text-red-500 dark:text-red-400',
        'Pause If' => 'text-amber-500 dark:text-amber-400',
        'Add Source' => 'text-purple-500 dark:text-purple-400',
        'Sub Source' => 'text-purple-500 dark:text-purple-400',
        'Add Hits' => 'text-purple-500 dark:text-purple-400',
        'Sub Hits' => 'text-purple-500 dark:text-purple-400',
        'Add Address' => 'text-purple-500 dark:text-purple-400',
        'And Next' => 'text-cyan-500 dark:text-cyan-400',
        'Or Next' => 'text-cyan-500 dark:text-cyan-400',
        'Measured' => 'text-emerald-500 dark:text-emerald-400',
        'Measured If' => 'text-emerald-500 dark:text-emerald-400',
        'Measured %' => 'text-emerald-500 dark:text-emerald-400',
        'Trigger' => 'text-sky-500 dark:text-sky-400',
        'Remember' => 'text-pink-500 dark:text-pink-400',
    ];

    /**
     * Returns the Tailwind CSS classnames for a given flag type.
     */
    public function getFlagColorClass(string $flag): string
    {
        return self::FLAG_COLORS[$flag] ?? '';
    }

    /**
     * Formats an operand (Source or Target side) for display.
     *
     * @param array<string, mixed> $condition the decoded condition from TriggerDecoderService
     * @param string $side 'Source' or 'Target'
     * @param array<int, string> $groupNotes the notes array for indirect address resolution
     * @return array{
     *     display: string,
     *     displayTruncated: string,
     *     isTruncated: bool,
     *     tooltip: string|null,
     *     cssClass: string|null,
     *     isAlias: bool,
     *     decimalDisplay: string|null,
     *     hexDisplay: string|null,
     *     alias: string|null,
     *     valueAlias: string|null,
     *     valueAliasTruncated: string|null,
     *     isValueAliasTruncated: bool,
     *     deltaSuffix: string
     * }
     */
    public function formatOperandDisplay(
        array $condition,
        string $side,
        array $groupNotes,
    ): array {
        $type = $condition[$side . 'Type'] ?? '';
        $address = $condition[$side . 'Address'] ?? '';
        $tooltip = $condition[$side . 'Tooltip'] ?? '';
        $sourceSize = $condition['SourceSize'] ?? '';

        if ($type === 'Recall') {
            return [
                'display' => '{recall}',
                'displayTruncated' => '{recall}',
                'isTruncated' => false,
                'tooltip' => null,
                'cssClass' => 'text-pink-500 dark:text-pink-400',
                'isAlias' => false,
                'decimalDisplay' => null,
                'hexDisplay' => null,
                'alias' => null,
                'valueAlias' => null,
                'valueAliasTruncated' => null,
                'isValueAliasTruncated' => false,
                'deltaSuffix' => '',
            ];
        }

        if ($type === 'Value') {
            $hexVal = $address;
            $decVal = (int) hexdec(ltrim($hexVal, '0x'));

            $sourceTooltip = $condition['SourceTooltip'] ?? '';
            $valueAlias = null;

            if (!empty($sourceTooltip)) {
                $resolvedSource = $this->resolveAddressAlias($sourceTooltip, $groupNotes);
                $noteSection = $resolvedSource['noteSection'];

                $valueAlias = $this->resolveValueAlias($decVal, $hexVal, $sourceSize, $noteSection);
            }

            $display = $valueAlias ?? (string) $decVal;
            $displayTruncated = Str::limit($display, self::ALIAS_TRUNCATE_LENGTH);
            $valueAliasTruncated = $valueAlias !== null ? Str::limit($valueAlias, self::ALIAS_TRUNCATE_LENGTH) : null;

            return [
                'display' => $display,
                'displayTruncated' => $displayTruncated,
                'isTruncated' => $displayTruncated !== $display,
                'tooltip' => null,
                'cssClass' => $valueAlias !== null ? 'text-emerald-600 dark:text-emerald-400' : null,
                'isAlias' => $valueAlias !== null,
                'decimalDisplay' => (string) $decVal,
                'hexDisplay' => $hexVal,
                'alias' => null,
                'valueAlias' => $valueAlias,
                'valueAliasTruncated' => $valueAliasTruncated,
                'isValueAliasTruncated' => $valueAlias !== null && $valueAliasTruncated !== $valueAlias,
                'deltaSuffix' => '',
            ];
        }

        if (!empty($tooltip)) {
            $resolved = $this->resolveAddressAlias($tooltip, $groupNotes);
            $display = $resolved['alias'] . $resolved['deltaSuffix'];
            $displayTruncated = Str::limit($display, self::ALIAS_TRUNCATE_LENGTH);

            return [
                'display' => $display,
                'displayTruncated' => $displayTruncated,
                'isTruncated' => $displayTruncated !== $display,
                'tooltip' => $tooltip,
                'cssClass' => 'text-emerald-600 dark:text-emerald-400 cursor-help',
                'isAlias' => true,
                'decimalDisplay' => null,
                'hexDisplay' => null,
                'alias' => $display,
                'valueAlias' => null,
                'valueAliasTruncated' => null,
                'isValueAliasTruncated' => false,
                'deltaSuffix' => $resolved['deltaSuffix'],
                'noteSection' => $resolved['noteSection'],
            ];
        }

        return [
            'display' => $address,
            'displayTruncated' => $address,
            'isTruncated' => false,
            'tooltip' => null,
            'cssClass' => null,
            'isAlias' => false,
            'decimalDisplay' => null,
            'hexDisplay' => null,
            'alias' => null,
            'valueAlias' => null,
            'valueAliasTruncated' => null,
            'isValueAliasTruncated' => false,
            'deltaSuffix' => '',
        ];
    }

    /**
     * Resolves an address alias from a tooltip/code note.
     * Handles bracket stripping, pointer normalization, and indirect address resolution.
     *
     * @param string $tooltip the full tooltip/code note text
     * @param array<int, string> $groupNotes notes for indirect resolution
     * @return array{alias: string, deltaSuffix: string, noteSection: string}
     */
    public function resolveAddressAlias(string $tooltip, array $groupNotes): array
    {
        $alias = Str::before($tooltip, "\n");
        $deltaSuffix = '';
        $noteSection = $tooltip;

        // Handle a range address format: "[0x001234 + 12]".
        $extractedDelta = $this->extractDeltaSuffix($alias, $tooltip);
        if ($extractedDelta['hasDelta']) {
            $deltaSuffix = $extractedDelta['suffix'];
            $alias = $extractedDelta['alias'];
            $noteSection = $extractedDelta['noteContent'];
        }

        if (str_starts_with($alias, '[Indirect')) {
            $resolved = $this->resolveIndirectAlias($alias, $groupNotes);
            if ($resolved['found']) {
                $alias = $resolved['alias'];
                $noteSection = $resolved['noteSection'];
            }
        }

        // Strip all brackets except [Pointer].
        $alias = $this->stripBrackets($alias);
        $alias = $this->normalizeWhitespace($alias);

        if (empty($alias)) {
            $alias = $this->extractFallbackAlias($tooltip);
        }

        return [
            'alias' => $alias,
            'deltaSuffix' => $deltaSuffix,
            'noteSection' => $noteSection,
        ];
    }

    /**
     * Resolves a value alias from a code note section.
     * Handles bit field values (Bit0-Bit7), float values, and integer values.
     *
     * @param int $decimalValue the decimal value to resolve
     * @param string $hexValue the hex representation (for float conversion)
     * @param string $sourceSize the source operand size (eg: 'Bit3', 'Float', '8-bit')
     * @param string $noteSection the relevant code note section to search
     * @return string|null the resolved alias or null if not found
     */
    public function resolveValueAlias(
        int $decimalValue,
        string $hexValue,
        string $sourceSize,
        string $noteSection,
    ): ?string {
        if (preg_match('/^Bit(\d)$/', $sourceSize, $bitMatch)) {
            $bitIndex = (int) $bitMatch[1];
            $alias = $this->resolveBitFieldValue($bitIndex, $decimalValue, $noteSection);
            if ($alias !== null) {
                return $alias;
            }
        }

        $floatTypes = ['Float', 'Float BE', 'MBF32', 'MBF32 LE'];
        if (in_array($sourceSize, $floatTypes, true)) {
            if ($sourceSize !== 'MBF32' && $sourceSize !== 'MBF32 LE') {
                $alias = $this->resolveFloatValue($hexValue, $sourceSize, $noteSection);
                if ($alias !== null) {
                    return $alias;
                }
            }
        }

        return $this->resolveIntegerValue($decimalValue, $noteSection);
    }

    /**
     * Generates a markdown representation of decoded trigger groups.
     * Reverse-engineered from AutoCR's toMarkdown() output format for functional parity.
     *
     * @param array<int, array<string, mixed>> $groups the decoded groups from `TriggerDecoderService`
     */
    public function generateMarkdown(array $groups): string
    {
        // Calculate dynamic column widths from actual data.
        $maxFlagLen = 0;
        $maxTypeLen = 3;
        $maxSizeLen = 0;
        $maxValueLen = 0;

        foreach ($groups as $group) {
            foreach ($group['Conditions'] as $c) {
                $flag = str_replace(' ', '', $c['Flag'] ?? '');
                $sourceType = $c['SourceType'] === 'Inverted' ? 'Invert' : ($c['SourceType'] ?? '');
                $targetType = $c['TargetType'] === 'Inverted' ? 'Invert' : ($c['TargetType'] ?? '');
                $sourceVal = $this->formatMarkdownValue($c['SourceType'] ?? '', $c['SourceAddress'] ?? '');
                $targetVal = $this->formatMarkdownValue($c['TargetType'] ?? '', $c['TargetAddress'] ?? '');

                $maxFlagLen = max($maxFlagLen, strlen($flag));
                $maxTypeLen = max($maxTypeLen, strlen($sourceType), strlen($targetType));
                $maxSizeLen = max($maxSizeLen, strlen($c['SourceSize'] ?? ''), strlen($c['TargetSize'] ?? ''));
                $maxValueLen = max($maxValueLen, strlen($sourceVal), strlen($targetVal));
            }
        }

        $output = '';
        $groupIndex = 0;

        foreach ($groups as $group) {
            $label = $groupIndex === 0 ? 'Core' : "Alt {$groupIndex}";
            $output .= "### {$label}\n```\n";

            foreach ($group['Conditions'] as $idx => $c) {
                $flag = str_replace(' ', '', $c['Flag'] ?? '');
                $sourceType = $c['SourceType'] === 'Inverted' ? 'Invert' : ($c['SourceType'] ?? '');
                $targetType = $c['TargetType'] === 'Inverted' ? 'Invert' : ($c['TargetType'] ?? '');
                $sourceVal = $this->formatMarkdownValue($c['SourceType'] ?? '', $c['SourceAddress'] ?? '');
                $targetVal = $this->formatMarkdownValue($c['TargetType'] ?? '', $c['TargetAddress'] ?? '');
                $operator = $c['Operator'] ?? '';

                // Show hits only for non-scalable flags with operators.
                $showHits = !in_array($flag, self::SCALABLE_FLAGS, true) && $operator !== '';
                $hitTarget = $c['HitTarget'] ?: '0';

                $output .= sprintf("%3d: ", $idx + 1);
                $output .= str_pad($flag, $maxFlagLen + 1);
                $output .= str_pad($sourceType, $maxTypeLen + 1);
                $output .= str_pad($c['SourceSize'] ?? '', $maxSizeLen + 1);
                $output .= str_pad($sourceVal, $maxValueLen + 1);
                $output .= str_pad($operator, 4);
                $output .= str_pad($targetType, $maxTypeLen + 1);
                $output .= str_pad($c['TargetSize'] ?? '', $maxSizeLen + 1);
                $output .= str_pad($targetVal, $maxValueLen + 1);
                $output .= $showHits ? "({$hitTarget})" : '';
                $output .= "\n";
            }

            $output .= "```\n\n";
            $groupIndex++;
        }

        return $output;
    }

    /**
     * Formats an operand value for markdown output.
     * - Recall type shows {recall}
     * - Value/Float types show decimal
     * - Address types show hex with 8-digit padding
     */
    private function formatMarkdownValue(string $type, string $address): string
    {
        if ($type === 'Recall') {
            return '{recall}';
        }

        $isAddressType = !in_array($type, ['Value', 'Float', 'Recall', ''], true);

        if (!$isAddressType && str_starts_with($address, '0x')) {
            return (string) hexdec($address);
        }

        if ($isAddressType && str_starts_with($address, '0x')) {
            return '0x' . str_pad(substr($address, 2), 8, '0', STR_PAD_LEFT);
        }

        return $address;
    }

    /**
     * Checks if any condition in the groups uses the AddAddress flag.
     *
     * @param array<int, array<string, mixed>> $groups The decoded groups
     */
    public function hasAddAddressFlag(array $groups): bool
    {
        foreach ($groups as $group) {
            foreach ($group['Conditions'] ?? [] as $condition) {
                if (($condition['Flag'] ?? '') === 'Add Address') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Computes which AddAddress rows precede each end-of-chain row.
     * Returns a map of row number -> array of AddAddress row numbers that feed into it.
     *
     * @param array<int, array<string, mixed>> $conditions the conditions array from a group
     * @return array<int, int[]>
     */
    public function computeAddAddressChains(array $conditions): array
    {
        $chains = [];
        $currentChain = [];

        foreach ($conditions as $index => $condition) {
            $rowNum = $index + 1;

            if (($condition['Flag'] ?? '') === 'Add Address') {
                $currentChain[] = $rowNum;
            } elseif (!empty($currentChain)) {
                $chains[$rowNum] = $currentChain;
                $currentChain = [];
            }
        }

        return $chains;
    }

    /**
     * Determines the appropriate address format string based on address sizes.
     * Returns '0x%08x' for 32-bit addresses, '0x%06x' otherwise.
     *
     * @param array<int, array<string, mixed>> $groups The decoded groups
     */
    public function getAddressFormat(array $groups): string
    {
        foreach ($groups as $group) {
            foreach ($group['Conditions'] ?? [] as $condition) {
                $sourceAddr = $condition['SourceAddress'] ?? '';
                $targetAddr = $condition['TargetAddress'] ?? '';

                // 32-bit addresses are "0x" + 8 hex digits = 10 characters.
                if ((str_starts_with($sourceAddr, '0x') && strlen($sourceAddr) === 10)
                    || (str_starts_with($targetAddr, '0x') && strlen($targetAddr) === 10)) {
                    return '0x%08x';
                }
            }
        }

        return '0x%06x';
    }

    /**
     * Strips brackets from an alias string, but preserves [Pointer] markers.
     */
    private function stripBrackets(string $alias): string
    {
        $result = preg_replace_callback('/\[.*?\]/', function ($match) {
            if (str_contains(strtolower($match[0]), 'pointer')) {
                return '[Pointer]';
            }

            return '';
        }, $alias);

        return $result ?? $alias;
    }

    private function normalizeWhitespace(string $alias): string
    {
        $result = preg_replace('/\s{2,}/', ' ', $alias);

        return trim($result ?? $alias);
    }

    /**
     * Extracts delta suffix from a range address format like "[0x001234 + 12]".
     *
     * @return array{hasDelta: bool, suffix: string, alias: string, noteContent: string}
     */
    private function extractDeltaSuffix(string $alias, string $tooltip): array
    {
        if (!preg_match('/^\[0x[0-9a-fA-F]+\s*\+\s*(\d+)\]$/', $alias, $rangeMatch)) {
            return ['hasDelta' => false, 'suffix' => '', 'alias' => $alias, 'noteContent' => $tooltip];
        }

        $deltaOffset = (int) $rangeMatch[1];
        $suffix = '';
        if ($deltaOffset > 0) {
            $suffix = ' +0x' . strtoupper(dechex($deltaOffset));
        }

        $noteContent = Str::after($tooltip, "\n");
        $newAlias = Str::before($noteContent, "\n");

        return [
            'hasDelta' => true,
            'suffix' => $suffix,
            'alias' => $newAlias,
            'noteContent' => $noteContent,
        ];
    }

    /**
     * Resolves an indirect address alias from hierarchical code notes.
     *
     * @param string $indirectPattern the indirect address pattern (eg: "[Indirect 0x001234 + 0x000064]")
     * @param array<int, string> $groupNotes the notes array keyed by address
     * @return array{found: bool, alias: string, noteSection: string}
     */
    private function resolveIndirectAlias(string $indirectPattern, array $groupNotes): array
    {
        if (!preg_match('/\[Indirect\s+0x([0-9a-fA-F]+)(.*)?\]/', $indirectPattern, $matches)) {
            return ['found' => false, 'alias' => $indirectPattern, 'noteSection' => ''];
        }

        $baseAddr = (int) hexdec($matches[1]);
        $offsetPart = $matches[2] ?? '';

        $baseNote = $groupNotes[$baseAddr] ?? null;
        if (!$baseNote || !$offsetPart) {
            return ['found' => false, 'alias' => $indirectPattern, 'noteSection' => ''];
        }

        preg_match_all('/\+\s*0x([0-9a-fA-F]+)/', $offsetPart, $offsetMatches);
        $offsets = array_map('hexdec', $offsetMatches[1]);

        if (empty($offsets)) {
            return ['found' => false, 'alias' => $indirectPattern, 'noteSection' => ''];
        }

        $lastOffset = end($offsets);
        $plusCount = count($offsets);
        $plusPrefix = str_repeat('+', $plusCount);

        // Search for pattern like "+0x64 | Name", "++100 | Name", "+++0xec | Name", etc.
        // Supports various offset formats: padded hex (0x00000064), short hex (0x64),
        // bare hex (64, b47), and decimal (100).
        $pattern = '/^' . preg_quote($plusPrefix, '/') . '(0x)?([0-9a-fA-F]+)\s*\|\s*(.+)$/mi';

        if (!preg_match_all($pattern, $baseNote, $allMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return ['found' => false, 'alias' => $indirectPattern, 'noteSection' => ''];
        }

        // Find the match where the offset value equals our target.
        foreach ($allMatches as $match) {
            $has0xPrefix = !empty($match[1][0]);
            $offsetStr = $match[2][0];

            // Parse the offset. If it has a 0x prefix, it's definitely hex. Otherwise, it could
            // be hex or decimal. Real code notes commonly use bare hex (like "b47" or "ec"),
            // so we interpret values with hex letters (a-f) as hex.
            if ($has0xPrefix || preg_match('/[a-fA-F]/', $offsetStr)) {
                $parsedOffset = hexdec($offsetStr);
            } else {
                $parsedOffset = (int) $offsetStr;
            }

            if ($parsedOffset === $lastOffset) {
                $alias = trim($match[3][0]);
                $sectionStart = (int) $match[0][1] + strlen($match[0][0]);
                $remainingNote = substr($baseNote, $sectionStart);

                // Find where next offset section starts (line starting with +).
                if (preg_match('/^[+]/m', $remainingNote, $nextMatch, PREG_OFFSET_CAPTURE)) {
                    $noteSection = substr($remainingNote, 0, (int) $nextMatch[0][1]);
                } else {
                    $noteSection = $remainingNote;
                }

                return ['found' => true, 'alias' => $alias, 'noteSection' => $noteSection];
            }
        }

        return ['found' => false, 'alias' => $indirectPattern, 'noteSection' => ''];
    }

    /**
     * Finds the first meaningful line from a tooltip for a fallback alias.
     */
    private function extractFallbackAlias(string $tooltip): string
    {
        $lines = explode("\n", $tooltip);
        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            $alias = $this->stripBrackets($line);
            $alias = $this->normalizeWhitespace($alias);

            if (!empty($alias)) {
                return $alias;
            }
        }

        return '';
    }

    /**
     * Resolves bit field values (0 = false, 1 = true or label).
     */
    private function resolveBitFieldValue(int $bitIndex, int $value, string $noteSection): ?string
    {
        if ($value === 0) {
            return 'false';
        }

        if ($value === 1) {
            $bitPattern = '/^\s*Bit\s*' . $bitIndex . '\s*[=:]\s*(.+)$/mi';
            if (preg_match($bitPattern, $noteSection, $match)) {
                return trim($match[1]);
            }

            return 'true';
        }

        return null;
    }

    /**
     * Converts hex to an IEEE 754 float and matches against note patterns.
     */
    private function resolveFloatValue(string $hexValue, string $floatType, string $noteSection): ?string
    {
        $hexStr = str_pad(ltrim($hexValue, '0x'), 8, '0', STR_PAD_LEFT);
        $packed = pack('H*', $hexStr);

        // The hex string is in big-endian order, so reverse it for little-endian Float.
        if ($floatType === 'Float') {
            $packed = strrev($packed);
        }

        $floatVal = unpack('f', $packed)[1];
        $epsilon = 0.000001;

        $lines = explode("\n", $noteSection);
        foreach ($lines as $line) {
            // Match: "0.5 = description" or "-1.0 : description".
            if (preg_match('/^\s*([-+]?[0-9]*\.?[0-9]+)\s*[=:]\s*(.+)$/i', $line, $match)) {
                $lineVal = (float) $match[1];
                if (abs($lineVal - $floatVal) < $epsilon) {
                    return trim($match[2]);
                }
            }
        }

        return null;
    }

    /**
     * Parses value enumerations from a code note section.
     * Ported directly from AutoCR's parseEnumerations() function, with a minor bug fix in the matcher pattern.
     *
     * @param string $noteSection the code note text
     * @return array<int|float, string>|null map of numeric values to labels, or null if not enough enumerations found
     */
    private function parseEnumerations(string $noteSection): ?array
    {
        $lines = explode("\n", $noteSection);
        if (count($lines) < 2) {
            return null;
        }

        // Matches: value (group 1) + delimiter (group 2) + description.
        $delimiterPattern = '/((?:(?:0x)?[0-9a-f]+|[-+]?[0-9]*\.?[0-9]+)+)([^\w\d]*[^\w\d\s][^\w\d]*).+$/i';

        $delimiterCounts = [];
        for ($i = 1; $i < count($lines); $i++) {
            if (preg_match($delimiterPattern, trim($lines[$i]), $matches)) {
                $delim = $matches[2];
                $delimiterCounts[$delim] = ($delimiterCounts[$delim] ?? 0) + 1;
            }
        }

        if (empty($delimiterCounts)) {
            return null;
        }

        arsort($delimiterCounts);
        $delimiter = array_key_first($delimiterCounts);
        $delimCount = $delimiterCounts[$delimiter];

        $enumerations = [];
        $hasHexIndicator = false;
        $lineCount = 0;

        // Group 1: 0x-prefixed hex | Group 2: pure digits | Group 3: bare hex with letters.
        $valuePattern = '/\b(?:(0x[0-9a-f]+)|([0-9]+)|([0-9a-f]+))\b/i';

        for ($i = 1; $i < count($lines); $i++) {
            if (strpos($lines[$i], $delimiter) === false) {
                continue;
            }

            $lineCount++;
            $parts = explode($delimiter, $lines[$i], 2);
            if (count($parts) < 2) {
                continue;
            }

            $lhs = $parts[0];
            $rhs = trim($parts[1]);

            if (preg_match_all($valuePattern, $lhs, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $enumerations[] = ['literal' => $match[0], 'meaning' => $rhs];

                    // Groups 1 and 3 indicate definite hex values.
                    if (!empty($match[1]) || !empty($match[3])) {
                        $hasHexIndicator = true;
                    }
                }
            }
        }

        // Avoid false positives from notes with sparse value patterns.
        // This does miss some valid stuff, but it's probably not a big deal.
        if ($delimCount < 2 || $lineCount < 3) {
            return null;
        }

        $result = [];
        foreach ($enumerations as $enum) {
            $literal = $enum['literal'];

            if (str_contains($literal, '.') && !str_starts_with(strtolower($literal), '0x')) {
                $value = (float) $literal;
            } elseif (str_starts_with(strtolower($literal), '0x')) {
                $value = hexdec($literal);
            } elseif ($hasHexIndicator) {
                $value = hexdec($literal);
            } else {
                $value = (int) $literal;
            }

            if (!is_nan($value)) {
                $result[$value] = $enum['meaning'];
            }
        }

        return empty($result) ? null : $result;
    }

    /**
     * Extracts value pairs from comma/semicolon-separated code note content.
     *
     * @param string $content the CSV-like content to parse
     * @param array<int, string> &$result the result array to populate
     */
    private function extractCsvPairs(string $content, array &$result): void
    {
        $clauses = preg_split('/[,;]/', $content);

        foreach ($clauses as $clause) {
            $clause = trim($clause);

            // Match patterns like "7=game" or "7: game" or "7 = game".
            if (preg_match('/(\d+)\s*[=:]\s*(.+)$/i', $clause, $match)) {
                $value = (int) $match[1];
                $description = rtrim(trim($match[2]), ')'); // trim any trailing parens

                if (!empty($description)) {
                    $result[$value] = $description;
                }
            }
        }
    }

    /**
     * Parses inline CSV formats like "(2=logos,4=title song,7=game)" or "0=A, 1=B, 2=C".
     *
     * @param string $noteSection the code note text
     * @return array<int, string>|null map of numeric values to labels, or null if no CSV found
     */
    private function parseInlineCsv(string $noteSection): ?array
    {
        $result = [];

        // Check for parenthesized CSV: "label (0=A, 1=B, 2=C)".
        if (preg_match_all('/\(([^)]*\d+\s*[=:][^)]+)\)/i', $noteSection, $parenMatches)) {
            foreach ($parenMatches[1] as $content) {
                $this->extractCsvPairs($content, $result);
            }
        }

        // Check for line-level CSV: "0=A, 1=B, 2=C" (line has multiple value assignments).
        foreach (explode("\n", $noteSection) as $line) {
            if (preg_match_all('/\d+\s*[=:]/', $line, $assignments) && count($assignments[0]) > 1) {
                $this->extractCsvPairs($line, $result);
            }
        }

        return empty($result) ? null : $result;
    }

    /**
     * Matches integer values against note patterns like "16 = Label".
     */
    private function resolveIntegerValue(int $decimalValue, string $noteSection): ?string
    {
        $enumerations = $this->parseEnumerations($noteSection);
        if ($enumerations !== null && isset($enumerations[$decimalValue])) {
            return $enumerations[$decimalValue];
        }

        // Try inline CSV format (parenthesized or comma-separated on single line).
        $csvValues = $this->parseInlineCsv($noteSection);
        if ($csvValues !== null && isset($csvValues[$decimalValue])) {
            return $csvValues[$decimalValue];
        }

        // Fallback for notes that don't meet the parseEnumerations threshold.
        $decStr = (string) $decimalValue;
        if (preg_match('/^' . preg_quote($decStr, '/') . '\s*[=:]\s*(.+)$/m', $noteSection, $match)) {
            return trim($match[1]);
        }

        $hexLower = strtolower(dechex($decimalValue));
        if (preg_match('/^0x0*' . $hexLower . '\s*[=:]\s*(.+)$/mi', $noteSection, $match)) {
            return trim($match[1]);
        }

        return null;
    }
}
