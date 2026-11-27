<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use Illuminate\Support\Str;

class TicketNegligenceAlert extends Alert
{
    /**
     * @param array<int, array{displayName: string, staleTicketCount: int}> $entries
     */
    public function __construct(
        public readonly array $entries,
    ) {
    }

    /**
     * Example:
     * "Ticket negligence alerts:
     * - [Scott](<https://retroachievements.org/user/Scott>): [9 unaddressed tickets](<https://retroachievements.org/user/Scott/tickets?filter[status]=open>) over 30 days old"
     */
    public function toDiscordMessage(): string
    {
        $lines = ['Ticket negligence alerts:'];

        foreach ($this->entries as $entry) {
            $lines[] = sprintf(
                '- [%s](<%s>): [%d unaddressed %s](<%s>) over 30 days old',
                $entry['displayName'],
                route('user.show', ['user' => $entry['displayName']]),
                $entry['staleTicketCount'],
                Str::plural('ticket', $entry['staleTicketCount']),
                route('developer.tickets', ['user' => $entry['displayName'], 'filter[status]' => 'open']),
            );
        }

        return implode("\n", $lines);
    }
}
