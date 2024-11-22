<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Enums\ClientSupportLevel;
use App\Models\EmulatorUserAgent;

class UserAgentService
{
    public array $cache = [];

    public function decode(string $userAgent): array
    {
        return $this->cache[$userAgent] ??= $this->parseUserAgent($userAgent);
    }

    private function parseUserAgent(string $userAgent): array
    {
        if (empty($userAgent) || $userAgent === '[not provided]') {
            return [
                'client' => 'Unknown',
                'clientVersion' => 'Unknown',
            ];
        }

        // expected format: <product>/<product-version> (<system-information>) <extensions>

        $userAgentLength = strlen($userAgent);

        $indexParens = strpos($userAgent, '(');
        if ($indexParens !== false) {
            // OS information provided, assume everything before the OS is the client version
            $data = $this->extractClient(substr($userAgent, 0, $indexParens));

            $indexCloseParens = strpos($userAgent, ')', $indexParens);
            if ($indexCloseParens === false) {
                return $data;
            }

            $os = substr($userAgent, $indexParens + 1, $indexCloseParens - $indexParens - 1);
            $data['os'] = $this->trimOperatingSystem($os);

            $indexNext = $indexCloseParens + 1;
        } else {
            $indexSpace = strpos($userAgent, ' ');
            if ($indexSpace === false) {
                // only one part - assume it's Client/Version
                return $this->extractClient($userAgent);
            }

            $data = $this->extractClient(substr($userAgent, 0, $indexSpace));
            $indexNext = $indexSpace;
        }

        while ($indexNext < $userAgentLength) {
            if ($userAgent[$indexNext] == ' ') {
                $indexNext++;
                continue;
            }

            $indexSpace = strpos($userAgent, ' ', $indexNext);
            if ($indexSpace === false) {
                $indexSpace = $userAgentLength;
            }

            $this->addSecondaryInformation($data, substr($userAgent, $indexNext, $indexSpace - $indexNext));
            $indexNext = $indexSpace + 1;
        }

        return $data;
    }

    private function extractClient(string $clause): array
    {
        $clause = trim($clause);

        $index = strpos($clause, '/');
        if ($index !== false) {
            // found "Client/Version", just split it
            return [
                'client' => substr($clause, 0, $index),
                'clientVersion' => $this->trimVersion(substr($clause, $index + 1)),
            ];
        }

        // assuming first word is client and last word is version
        $parts = explode(' ', $clause);
        if (count($parts) > 1) {
            return [
                'client' => $parts[0],
                'clientVersion' => $this->trimVersion($parts[count($parts) - 1]),
            ];
        }

        // special case: 'libretro' => 'RetroArch'
        if ($clause === 'libretro') {
            return [
                'client' => 'RetroArch',
                'clientVersion' => 'Unknown',
            ];
        }

        // did not find a version number at the end of the string, just return
        // the whole clause as the client
        return [
            'client' => $clause ? $clause : 'Unknown',
            'clientVersion' => 'Unknown',
        ];
    }

    private function addSecondaryInformation(array &$data, string $clause): void
    {
        $index = strpos($clause, '/');
        if ($index !== false) {
            // found "Client/Version", just split it
            $thing = trim(substr($clause, 0, $index));
            $version = trim(substr($clause, $index + 1));

            if ($thing === 'Integration') {
                $data['integrationVersion'] = $version;
            } else {
                if (!array_key_exists('extra', $data)) {
                    $data['extra'] = [];
                }

                $data['extra'][$thing] = $this->trimVersion($version);

                $index = strpos($thing, '_libretro');
                if ($index !== false) {
                    $data['clientVariation'] = substr($thing, 0, $index);
                }
            }
        }

        $potentialVersion = $this->trimVersion($clause);
        if ($this->looksLikeVersion($potentialVersion) && !$this->looksLikeVersion($data['clientVersion'])) {
            $data['clientVersion'] = $potentialVersion;
        }
    }

    private function trimVersion(string $version): string
    {
        $version = trim($version);
        $version = ltrim($version, 'vV');
        $version = ltrim($version, '-');

        return $version;
    }

    private function looksLikeVersion(string $version): bool
    {
        $len = strlen($version);
        if ($len >= 3) {
            // look for string starting with "N.N"
            $c = substr($version, 0, 1);
            if (is_numeric($c)) {
                return preg_match('/^\d+(?:\.\d+)+/', $version) === 1;
            }
        }

        return false;
    }

    private function trimOperatingSystem(string $os): string
    {
        return trim(strtok($os, ';'));
    }

    public static function versionCompare(string $versionOne, string $versionTwo): int
    {
        $versionOneParts = UserAgentService::splitVersion($versionOne);
        $versionOnePartCount = count($versionOneParts);
        $versionTwoParts = UserAgentService::splitVersion($versionTwo);
        $versionTwoPartCount = count($versionTwoParts);

        $index = 0;
        while (true) {
            if ($index === $versionOnePartCount) {
                return ($index === $versionTwoPartCount) ? 0 : -1;
            }

            if ($index === $versionTwoPartCount) {
                return 1;
            }

            $versionOnePart = $versionOneParts[$index];
            $versionTwoPart = $versionTwoParts[$index];
            $diff = strcmp($versionOnePart, $versionTwoPart);
            if ($diff !== 0) {
                $versionOneNumeric = ctype_digit($versionOnePart);
                $versionTwoNumeric = ctype_digit($versionTwoPart);
                if ($versionOneNumeric) {
                    if ($versionTwoNumeric) {
                        // both parts are fully numeric. compare them numerically
                        $versionOneNumber = (int) $versionOnePart;
                        $versionTwoNumber = (int) $versionTwoPart;
                        if ($versionOneNumber < $versionTwoNumber) {
                            return -1;
                        } elseif ($versionOneNumber > $versionTwoNumber) {
                            return 1;
                        } else {
                            return 0;
                        }
                    }

                    // 1.0.1 > 1.0-dirty
                    return 1;
                } elseif ($versionTwoNumeric) {
                    // 1.0-dirty < 1.0.1
                    return -1;
                }

                // neither part is fully numeric. compare them as strings
                return ($diff < 0) ? -1 : 1;
            }

            $index++;
        }
    }

    private static function splitVersion(string $version): array
    {
        $parts = explode('.', str_replace('-', '.', $version));
        $count = count($parts);

        // ignore any chunks preceding the numeric part
        while ($count > 1 && ctype_alpha(substr($parts[0], 0, 1))) {
            array_shift($parts);
            $count--;
        }

        $index = 0;
        while ($index < $count && ctype_digit($parts[$index])) {
            $index++;
        }

        $lastPart = ($index < $count) ? $parts[$index] : '';
        $bonusPart = ($index + 1 < $count) ? $parts[$index + 1] : '';
        while ($index < $count) {
            array_pop($parts);
            $count--;
        }

        if (strlen($lastPart) > 1) {
            $left = substr($lastPart, 0, -1);
            if (ctype_digit($left)) {
                // split "6a" into "6.1"
                array_push($parts, $left);
                array_push($parts, strval(ord(strtolower(substr($lastPart, -1, 1))) - ord('a') + 1));

                // if there's a further trailing part, keep it as a differentiator
                if (strlen($bonusPart) > 0) {
                    array_push($parts, $bonusPart);
                }
            } elseif ($count === 0) {
                // entire string was non-numeric. return empty parts array
            } else {
                // keep the trailing part as a differentiator
                array_push($parts, $lastPart);
            }
        }

        return $parts;
    }

    public function getSupportLevel(string|array|null $userAgent): ClientSupportLevel
    {
        if (empty($userAgent) || $userAgent === '[not provided]') {
            return ClientSupportLevel::Unknown;
        }

        $data = is_string($userAgent) ? $this->decode($userAgent) : $userAgent;

        $emulatorUserAgent = EmulatorUserAgent::firstWhere('client', $data['client']);
        if (!$emulatorUserAgent) {
            return ClientSupportLevel::Unknown;
        }

        if ($emulatorUserAgent->minimum_allowed_version
            && UserAgentService::versionCompare($data['clientVersion'], $emulatorUserAgent->minimum_allowed_version) < 0) {

            // special case: Dolphin/e5d32f273f must still be allowed as it's the most stable development build
            if (str_starts_with($userAgent, 'Dolphin/e5d32f273f ')) {
                return ClientSupportLevel::Outdated;
            }

            return ClientSupportLevel::Blocked;
        }

        if ($emulatorUserAgent->minimum_hardcore_version
            && UserAgentService::versionCompare($data['clientVersion'], $emulatorUserAgent->minimum_hardcore_version) < 0) {

            return ClientSupportLevel::Outdated;
        }

        return ClientSupportLevel::Full;
    }
}
