<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Http\Actions\FindDiscordMemberAction;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Remove specific Discord roles from a user, or remove all roles if none are specified.
 */
class RemoveUserDiscordRolesAction
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
     * Remove Discord roles from a user.
     *
     * @param User $user user to remove roles from
     * @param array<string> $roleIds Role IDs to remove. Empty array removes ALL roles.
     */
    public function execute(User $user, array $roleIds = []): void
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

            // Determine the updated roles based on what we're removing.
            if (empty($roleIds)) {
                // Remove all roles.
                $updatedRoles = [];
            } else {
                // Remove specific roles.
                $updatedRoles = array_values(array_filter($currentRoles, fn ($role) => !in_array($role, $roleIds, true)));
            }

            // Only make the API call if roles actually changed.
            if ($updatedRoles !== $currentRoles) {
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
            $roleList = empty($roleIds) ? 'roles' : 'roles: ' . implode(', ', $roleIds);
            Log::error("Failed to remove Discord {$roleList} for user: " . $e->getMessage());
        }
    }
}
