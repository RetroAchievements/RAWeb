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

        $data = [];

        $client = $userAgent;
        $version = null;

        // split on spaces, then on slashes. if something that looks like a version is found,
        // take the first part of the clause (up to the first space if present) as the client.

        $parts = explode(' ', $userAgent);
        if (count($parts) === 1) {
            $index = strpos($userAgent, '/');
            if ($index !== false) {
                // found "Client/Version", just split it
                $client = substr($userAgent, 0, $index);
                $version = $this->trimVersion(substr($userAgent, $index + 1));
            }
        } else {
            $client = $lastClient = $version = null;
            for ($i = 0; $i < count($parts); $i++) {
                $part = $parts[$i];
                if (empty($part)) {
                    continue;
                }

                // part is only punctuation, ignore it if not in the middle of client name
                if (empty($client) && ctype_punct($part)) {
                    continue;
                }

                if ($part[0] === '(') {
                    // system information - match until closing parenthesis
                    if ($part[-1] === ')') {
                        $os = substr($part, 1, -1);
                    } else {
                        $os = substr($part, 1);

                        $i++;
                        while ($i < count($parts)) {
                            $os .= ' ';

                            $part = $parts[$i];
                            if ($part[-1] === ')') {
                                $os .= substr($part, 0, -1);
                                break;
                            }

                            $os .= $part;
                            $i++;
                        }
                    }

                    if (!array_key_exists('os', $data)) {
                        $data['os'] = $this->trimOperatingSystem($os);
                    }

                    continue;
                }

                $index = strpos($part, '/');
                if ($index !== false) {
                    // found "Client/Version", just split it
                    $front = substr($part, 0, $index);
                    $back = substr($part, $index + 1);

                    $client ??= $front;
                    $version = $this->trimVersion($back);
                } else {
                    // if we didn't find something that looks like a version,
                    // assume its part of the client name and continue.
                    $version = $this->trimVersion($part);
                    if (!$this->looksLikeVersion($version)) {
                        // only keep the first word of the client name
                        $client ??= $part;
                        $version = null;
                        continue;
                    }

                    // if we didn't find a new client moniker, update the version of the last client
                    if (empty($client)) {
                        $primaryClient = $data['client'] ?? '';
                        if ($lastClient === $primaryClient) {
                            if (!$this->looksLikeVersion($data['clientVersion'] ?? '')) {
                                $data['clientVersion'] = $version;
                            }
                        } elseif (array_key_exists('extra', $data)) {
                            $lastClientVersion = $data['extra'][$lastClient] ?? '';
                            if (!$this->looksLikeVersion($lastClientVersion)) {
                                $data['extra'][$lastClient] = $version;
                            }
                        }

                        continue;
                    }
                }

                // found client and version, store it
                if (!array_key_exists('client', $data)) {
                    // assume first clause is primary client
                    $data['client'] = $client;
                    $data['clientVersion'] = $version;
                } else {
                    // primary client already captured, delegate to extra subarray
                    if ($client === 'Integration') {
                        // promote RAIntegration version information
                        $data['integrationVersion'] = $version;
                    } else {
                        if (!array_key_exists('extra', $data)) {
                            $data['extra'] = [];
                        }

                        $data['extra'][$client] = $version;

                        // promote libretro core information
                        $index = strpos($client, '_libretro');
                        if ($index !== false) {
                            $data['clientVariation'] = substr($client, 0, $index);
                        }
                    }
                }

                $lastClient = $client;
                $client = $version = null;
            }
        }

        // if no primary client or primary client version was found,
        // populate whatever we can and fill the rest with Unknowns.
        if (!array_key_exists('client', $data)) {
            // special case: 'libretro' => 'RetroArch'
            if ($client === 'libretro') {
                $client = 'RetroArch';
            } else {
                // if there's a space in the client name, only use the first word
                $index = strpos($client, ' ');
                if ($index !== false) {
                    $client = substr($client, 0, $index);
                }
            }

            $data['client'] = empty($client) ? 'Unknown' : $client;
            $data['clientVersion'] = empty($version) ? 'Unknown' : $version;
        } elseif (empty($data['clientVersion'] ?? '')) {
            $data['clientVersion'] = 'Unknown';
        }

        return $data;
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
