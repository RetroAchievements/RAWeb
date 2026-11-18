<?php

declare(strict_types=1);

namespace App\Support\Alerts;

use App\Models\User;

class WatchedTermAlert extends Alert
{
    /**
     * @param list<string> $matchedTerms terms the content matched
     * @param string $location where the content was posted
     * @param ?string $destinationUrl permalink to the content
     */
    public function __construct(
        public readonly User $user,
        public readonly array $matchedTerms,
        public readonly string $location,
        public readonly ?string $destinationUrl = null,
    ) {
    }

    public function emoji(): ?string
    {
        return '🚩';
    }

    /**
     * "[SomePerson](<https://retroachievements.org/user/SomePerson>) posted watched term `sometool` in [a game comment](<https://retroachievements.org/comment/123>)"
     */
    public function toDiscordMessage(): string
    {
        $userUrl = route('user.show', ['user' => $this->user]);

        $termLabel = count($this->matchedTerms) === 1 ? 'watched term' : 'watched terms';
        $terms = implode(', ', array_map(
            fn (string $term): string => sprintf('`%s`', $term),
            $this->matchedTerms,
        ));

        // Angle brackets keep Discord from expanding a link preview for the content.
        $location = $this->destinationUrl
            ? sprintf('[%s](<%s>)', $this->location, $this->destinationUrl)
            : $this->location;

        return sprintf(
            '[%s](<%s>) posted %s %s in %s',
            $this->user->display_name,
            $userUrl,
            $termLabel,
            $terms,
            $location,
        );
    }
}
