<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Http\Actions\FindDiscordMemberAction;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Add specific Discord roles to a user if they're present in the main Discord server.
 */
class AddUserDiscordRolesAction
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

    /**
     * Add Discord roles to a user.
     *
     * @param User $user user to add roles to
     * @param array<string> $roleIds Role IDs to add. Empty array is a no-op.
     */
    public function execute(User $user, array $roleIds = []): void
    {
        if (!$this->botToken || !$this->guildId || empty($roleIds)) {
            return;
        }

        try {
            $member = (new FindDiscordMemberAction())->execute($user->display_name);
            if (!$member) {
                return;
            }

            $userId = $member['user']['id'];
            $currentRoles = $member['roles'] ?? [];

            // Add roles that they don't already have.
            $rolesToAdd = array_diff($roleIds, $currentRoles);

            // Only make the API call if there are new roles to add.
            if (!empty($rolesToAdd)) {
                $updatedRoles = array_values(array_unique(array_merge($currentRoles, $rolesToAdd)));

                $this->client->patch(
                    "https://discord.com/api/v10/guilds/{$this->guildId}/members/{$userId}",
                    [
                        'headers' => [
                            'Authorization' => "Bot {$this->botToken}",
                        ],
                        'json' => ['roles' => $updatedRoles],
                    ]
                );
            }
        } catch (Throwable $e) {
            $roleList = 'roles: ' . implode(', ', $roleIds);
            Log::error("Failed to add Discord {$roleList} for user: " . $e->getMessage());
        }
    }
}
