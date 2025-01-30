<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;
use App\Models\UserUsername;
use GuzzleHttp\Client;
use Throwable;

class ApproveNewDisplayNameAction
{
    public function execute(User $user, UserUsername $changeRequest): void
    {
        $oldDisplayName = $user->display_name;

        // Automatically mark conflicting requests as denied.
        UserUsername::where('username', $changeRequest->username)
            ->where('id', '!=', $changeRequest->id)
            ->whereNull('approved_at')
            ->whereNull('denied_at')
            ->update(['denied_at' => now()]);

        $changeRequest->update(['approved_at' => now()]);

        $user->display_name = $changeRequest->username;
        $user->save();

        sendDisplayNameChangeConfirmationEmail($user, $changeRequest->username);

        $this->notifyDiscord($user, $oldDisplayName, $changeRequest->username);
    }

    private function notifyDiscord(User $user, string $oldName, string $newName): void
    {
        $webhookUrl = config('services.discord.webhook.name-changes');

        if (!$webhookUrl) {
            return;
        }

        $profileUrl = "https://retroachievements.org/user/{$user->username}";
        $payload = [
            'content' => "[{$user->username}]({$profileUrl}) - Display name changed from **{$oldName}** to **{$newName}**.",
        ];

        try {
            (new Client())->post($webhookUrl, ['json' => $payload]);
        } catch (Throwable $e) {
            // Similar to our other Discord notifications, do nothing.
            // But don't flash a server error to the user.
        }
    }
}
