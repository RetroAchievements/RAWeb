<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Http\Actions\UpdateDiscordNicknameAction;
use App\Mail\DisplayNameChangeConfirmedMail;
use App\Models\User;
use App\Models\UserUsername;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ApproveNewDisplayNameAction
{
    public function execute(User $user, UserUsername $changeRequest): void
    {
        $oldDisplayName = $user->display_name;
        $newDisplayName = $changeRequest->username;

        // Automatically mark conflicting requests as denied.
        UserUsername::where('username', $newDisplayName)
            ->where('id', '!=', $changeRequest->id)
            ->whereNull('approved_at')
            ->whereNull('denied_at')
            ->update(['denied_at' => now()]);

        $changeRequest->update(['approved_at' => now()]);

        $user->display_name = $newDisplayName;
        $user->save();

        Mail::to($user)->queue(new DisplayNameChangeConfirmedMail($user, $newDisplayName));

        (new UpdateDiscordNicknameAction())->execute($oldDisplayName, $newDisplayName);

        $this->notifyDiscord($user, $oldDisplayName, $newDisplayName);
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
