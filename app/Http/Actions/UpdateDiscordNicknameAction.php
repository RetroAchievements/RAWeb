<?php

declare(strict_types=1);

namespace App\Http\Actions;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateDiscordNicknameAction
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

    public function execute(string $oldUsername, string $newUsername): void
    {
        if (!$this->botToken || !$this->guildId) {
            return;
        }

        try {
            $member = (new FindDiscordMemberAction())->execute($oldUsername);
            if (!$member) {
                return;
            }

            $this->updateUserNickname($member['user']['id'], $newUsername);
        } catch (Throwable $e) {
            Log::error("Failed to update Discord nickname: " . $e->getMessage());
        }
    }

    private function updateUserNickname(string $userId, string $newNickname): void
    {
        $this->client->patch(
            "https://discord.com/api/v10/guilds/{$this->guildId}/members/{$userId}",
            [
                'headers' => [
                    'Authorization' => "Bot {$this->botToken}",
                ],
                'json' => ['nick' => $newNickname],
            ]
        );
    }
}
