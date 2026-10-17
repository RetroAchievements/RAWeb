<?php

declare(strict_types=1);

namespace App\Http\Actions;

use GuzzleHttp\Client;

class FindDiscordMemberAction
{
    private Client $client;
    private ?string $botToken;
    private ?string $guildId;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? new Client();
        $this->botToken = config('services.discord.rabot_token');
        $this->guildId = config('services.discord.guild_id');
    }

    /**
     * Find a Discord member by their nickname or username.
     */
    public function execute(string $displayName): ?array
    {
        if (!$this->botToken || !$this->guildId) {
            return null;
        }

        $response = $this->client->get(
            "https://discord.com/api/v10/guilds/{$this->guildId}/members/search",
            [
                'headers' => [
                    'Authorization' => "Bot {$this->botToken}",
                ],
                'query' => ['query' => $displayName, 'limit' => 1000],
            ]
        );

        $members = json_decode($response->getBody()->getContents(), true);

        // Find a case-insensitive match.
        foreach ($members as $member) {
            $memberNick = $member['nick'] ?? $member['user']['username'];
            if (strcasecmp($memberNick, $displayName) === 0) {
                return $member;
            }
        }

        // Members not being found is expected. Relatively little of our userbase is on Discord.
        return null;
    }
}
