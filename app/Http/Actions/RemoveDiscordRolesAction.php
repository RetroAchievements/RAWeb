<?php

declare(strict_types=1);

namespace App\Http\Actions;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Remove all roles from a Discord member.
 * This is helpful for certain moderation actions, such as when the user is muted or banned.
 */
class RemoveDiscordRolesAction
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

    public function execute(User $user): void
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

            // Remove all roles by setting the roles array to empty.
            $this->client->patch(
                "https://discord.com/api/v10/guilds/{$this->guildId}/members/{$userId}",
                [
                    'headers' => [
                        'Authorization' => "Bot {$this->botToken}",
                    ],
                    'json' => ['roles' => []],
                ]
            );
        } catch (Throwable $e) {
            Log::error("Failed to remove Discord roles for user: " . $e->getMessage());
        }
    }
}
