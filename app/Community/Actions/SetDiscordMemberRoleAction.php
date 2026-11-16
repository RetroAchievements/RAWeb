<?php

declare(strict_types=1);

namespace App\Community\Actions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class SetDiscordMemberRoleAction
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

    public function execute(string $discordUserId, string $roleId, bool $shouldHaveRole, string $reason): bool
    {
        if (!$this->botToken || !$this->guildId || !$roleId) {
            return false;
        }

        try {
            $response = $this->client->request(
                $shouldHaveRole ? 'PUT' : 'DELETE',
                "https://discord.com/api/v10/guilds/{$this->guildId}/members/{$discordUserId}/roles/{$roleId}",
                [
                    'headers' => [
                        'Authorization' => "Bot {$this->botToken}",
                        'X-Audit-Log-Reason' => rawurlencode($reason),
                    ],
                    'connect_timeout' => 3,
                    'timeout' => 10,
                ]
            );
        } catch (ClientException $e) {
            if (!$shouldHaveRole && $e->getResponse()->getStatusCode() === 404) {
                return true; // tells sync that role removal is complete
            }

            throw $e;
        }

        return $response->getStatusCode() === 204;
    }
}
