<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Http\Actions\FindDiscordMemberAction;
use App\Models\EventAchievement;
use App\Models\EventWinnerDiscordRoleGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncAotwWinnerDiscordRolesAction
{
    public function __construct(
        private readonly FindDiscordMemberAction $findDiscordMemberAction,
        private readonly SetDiscordMemberRoleAction $setDiscordMemberRoleAction,
    ) {
    }

    public function execute(): void
    {
        $roleId = config('services.discord.aotw_winner_role');
        if (!$roleId || !config('services.discord.rabot_token') || !config('services.discord.guild_id')) {
            return;
        }

        $current = EventAchievement::currentAchievementOfTheWeek()->first();
        $winners = User::query()
            ->whereNull('banned_at')
            ->whereNull('unranked_at')
            ->where(fn (Builder $query) => $query->whereNull('muted_until')->orWhere('muted_until', '<=', now()))
            ->whereHas('playerAchievements', fn (Builder $query) => $query
                ->where('achievement_id', $current?->achievement_id)
                ->whereNotNull('unlocked_hardcore_at'))
            ->get()
            ->keyBy('id');

        $grants = EventWinnerDiscordRoleGrant::with('user')->where('discord_role_id', $roleId)->get();

        // Revoke expired grants before assigning roles for the new week.
        foreach ($grants as $key => $grant) {
            if ($current && $grant->expires_at->equalTo($current->active_until) && $winners->has($grant->user_id)) {
                continue;
            }

            try {
                if ($grant->discord_user_id !== null) {
                    $wasRevoked = $this->setDiscordMemberRoleAction->execute(
                        $grant->discord_user_id,
                        $roleId,
                        shouldHaveRole: false,
                        reason: "AOTW winner removed: {$grant->user->display_name}",
                    );

                    if (!$wasRevoked) {
                        continue;
                    }
                }

                $grant->delete();
                $grants->forget($key);
            } catch (Throwable $e) {
                Log::warning('Failed to revoke AOTW Discord role', ['user_id' => $grant->user_id, 'exception' => $e]);
            }
        }

        if (!$current) {
            return;
        }

        foreach ($winners as $user) {
            // A failed revoke must finish before this user can receive another grant.
            if ($grants->contains('user_id', $user->id)) {
                continue;
            }

            try {
                $member = $this->findDiscordMemberAction->execute($user->display_name);
                $discordUserId = $member['user']['id'] ?? null;
                if ($discordUserId !== null && $grants->contains('discord_user_id', $discordUserId)) {
                    continue;
                }

                if ($discordUserId !== null && !in_array($roleId, $member['roles'] ?? [], true)) {
                    $wasGranted = $this->setDiscordMemberRoleAction->execute(
                        $discordUserId,
                        $roleId,
                        shouldHaveRole: true,
                        reason: "AOTW winner: {$user->display_name}",
                    );

                    if (!$wasGranted) {
                        continue;
                    }
                }

                $grants->push(EventWinnerDiscordRoleGrant::create([
                    'user_id' => $user->id,
                    'discord_role_id' => $roleId,
                    'discord_user_id' => $discordUserId,
                    'expires_at' => $current->active_until,
                ]));
            } catch (Throwable $e) {
                Log::warning('Failed to grant AOTW Discord role', ['user_id' => $user->id, 'exception' => $e]);
            }
        }
    }
}
