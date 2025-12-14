<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;

class ProcessExpiredMutesAction
{
    public function execute(): void
    {
        $mutedRoleId = config('services.discord.muted');

        // Find users whose muted_until date has passed.
        $expiredMutedUsers = User::whereNotNull('muted_until')
            ->where('muted_until', '<', Carbon::now())
            ->get();

        foreach ($expiredMutedUsers as $user) {
            // Remove the Discord Muted role.
            if ($mutedRoleId) {
                (new RemoveUserDiscordRolesAction())->execute($user, [$mutedRoleId]);
            }

            // Clear muted_until. We don't record this as a moderation action
            // because it's not a decision - just a timer expiring. The original
            // mute record's expires_at field shows when the mute ended naturally.
            $user->muted_until = null;
            $user->saveQuietly();
        }
    }
}
