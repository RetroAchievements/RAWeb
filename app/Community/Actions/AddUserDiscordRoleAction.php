<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Http\Actions\FindDiscordMemberAction;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Add a specific role to a user on Discord if they're present in the main Discord server.
 */
class AddUserDiscordRoleAction
{
    private Client $client;
    private ?string $botToken;
    private ?string $guildId;

    public function __construct()
    {
        $this->client = new Client();
        $this->botToken = config('services.discord.rabot_token');
        $this->guildId = config('services.discord.guild_id');
    }

    public function execute(User $user, string $roleId): void
    {
        if (!$this->botToken || !$this->guildId) {
            return;
        }

        try {
            $member = (new FindDiscordMemberAction())->execute($user->display_name);
            if (!$member) {
                return;
            }

            $userId = $member['user']['id'];
            $currentRoles = $member['roles'] ?? [];

            // Add the role if they don't already have it.
            if (!in_array($roleId, $currentRoles, true)) {
                $currentRoles[] = $roleId;
            }

            $this->client->patch(
                "https://discord.com/api/v10/guilds/{$this->guildId}/members/{$userId}",
                [
                    'headers' => [
                        'Authorization' => "Bot {$this->botToken}",
                    ],
                    'json' => ['roles' => $currentRoles],
                ]
            );
        } catch (Throwable $e) {
            Log::error("Failed to add Discord role {$roleId} for user: " . $e->getMessage());
        }
    }
}
