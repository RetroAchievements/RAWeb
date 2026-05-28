<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use Illuminate\Support\Carbon;

class DeveloperInactivityAlert extends Alert
{
    public const REASON_OVERALL_INACTIVITY = 'overall_inactivity';
    public const REASON_DEVELOPER_INACTIVITY = 'developer_inactivity';

    /**
     * @param array<int, array{
     *     displayName: string,
     *     finding: array{
     *         reason: string,
     *         threshold: string,
     *         lastActivityAt: string|null
     *     }
     * }> $entries
     */
    public function __construct(
        public readonly array $entries,
    ) {
    }

    /**
     * Examples:
     * "Developer inactivity alerts:
     * - [Scott](<https://retroachievements.org/user/Scott>): no site activity since Jan 1, 2026 (3-month threshold)"
     *
     * "Developer inactivity alerts:
     * - [Scott](<https://retroachievements.org/user/Scott>): last tracked developer activity was Nov 1, 2025 (6-month threshold)"
     */
    public function toDiscordMessage(): string
    {
        $lines = ['Developer inactivity alerts:'];

        foreach ($this->entries as $entry) {
            $userUrl = route('user.show', ['user' => $entry['displayName']]);

            $lines[] = sprintf(
                '- [%s](<%s>): %s',
                $entry['displayName'],
                $userUrl,
                $this->formatFinding($entry['finding']),
            );
        }

        return implode("\n", $lines);
    }

    /**
     * @param array{reason: string, threshold: string, lastActivityAt: string|null} $finding
     */
    private function formatFinding(array $finding): string
    {
        if ($finding['reason'] === self::REASON_OVERALL_INACTIVITY) {
            return sprintf(
                'no site activity since %s (%s threshold)',
                $this->formatLastActivityAt($finding['lastActivityAt']),
                $finding['threshold'],
            );
        }

        if ($finding['lastActivityAt'] === null) {
            return sprintf('no tracked developer activity (%s threshold)', $finding['threshold']);
        }

        return sprintf(
            'last tracked developer activity was %s (%s threshold)',
            $this->formatLastActivityAt($finding['lastActivityAt']),
            $finding['threshold'],
        );
    }

    private function formatLastActivityAt(?string $lastActivityAt): string
    {
        if ($lastActivityAt === null) {
            return 'never';
        }

        return Carbon::parse($lastActivityAt)->format('M j, Y');
    }
}
