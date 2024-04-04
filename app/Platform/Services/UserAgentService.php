<?php

declare(strict_types=1);

namespace App\Platform\Services;

class UserAgentService
{
    public array $cache = [];

    public function decode(string $userAgent): array
    {
        if (array_key_exists($userAgent, $this->cache)) {
            return $this->cache[$userAgent];
        }

        $data = $this->parseUserAgent($userAgent);
        $this->cache[$userAgent] = $data;

        return $data;
    }

    private function parseUserAgent(string $userAgent): array
    {
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

        return $version;
    }

    private function looksLikeVersion(string $version): bool
    {
        // look for string starting with "N.N"
        $len = strlen($version);
        if ($len >= 3) {
            // match leading digits
            $index = 0;
            do {
                $c = substr($version, $index, 1);
                if (!is_numeric($c)) {
                    break;
                }
                $index++;
            } while ($index < $len);

            if ($index > 0 && $index < $len - 1) {
                // match decimal
                $c = substr($version, $index, 1);
                if ($c === '.') {
                    // match trailing digits
                    $c = substr($version, $index + 1, 1);
                    if (is_numeric($c)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function trimOperatingSystem(string $os): string
    {
        $index = strpos($os, ';');
        if ($index !== false) {
            $os = substr($os, 0, $index);
        }

        return trim($os);
    }
}
