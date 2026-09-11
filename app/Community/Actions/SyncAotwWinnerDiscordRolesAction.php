<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Http\Actions\FindDiscordMemberAction;
use App\Models\DiscordRoleGrant;
use App\Models\EventAchievement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncAotwWinnerDiscordRolesAction
{
    public function __construct(
        private readonly FindDiscordMemberAction $findDiscordMemberAction,
        private readonly SetDiscordMemberRoleAction $setDiscordMemberRoleAction,
    ) {
    }

    public function execute(?User $user = null): void
    {
        // This number will never get hit unless something has gone wrong,
        // like a Horizon worker freezing. This is just to make sure the lock
        // isn't held indefinitely, as the default value is 0.
        $lockTtl = 600;

        // Never allow the scheduled command and unlock listener to have overlapping executions.
        Cache::lock(self::class, $lockTtl)->get(fn () => $this->sync($user));
    }

    private function sync(?User $targetUser): void
    {
        $roleId = config('services.discord.aotw_winner_role');
        $channelId = config('services.discord.aotw_channel_id');
        if (!$roleId || !config('services.discord.rabot_token') || !config('services.discord.guild_id')) {
            return;
        }

        $current = EventAchievement::currentAchievementOfTheWeek()->first();
        $winners = User::query()
            ->when($targetUser, fn (Builder $query, User $user) => $query->whereKey($user->id))
            ->whereNull('banned_at')
            ->whereNull('unranked_at')
            ->where(fn (Builder $query) => $query->whereNull('muted_until')->orWhere('muted_until', '<=', now()))
            ->whereHas('playerAchievements', fn (Builder $query) => $query
                ->where('achievement_id', $current?->achievement_id)
                ->when($current, fn (Builder $query, EventAchievement $eventAchievement) => $query
                    ->where('unlocked_hardcore_at', '>=', $eventAchievement->active_from)
                    ->where('unlocked_hardcore_at', '<', $eventAchievement->active_until)))
            ->get()
            ->keyBy('id');

        $grants = DiscordRoleGrant::with('user')
            ->where('discord_role_id', $roleId)
            ->when($targetUser, fn (Builder $query, User $user) => $query->where('user_id', $user->id))
            ->get();

        // Revoke expired grants before assigning roles for the new week.
        foreach ($grants as $key => $grant) {
            if ($current && $grant->expires_at->isFuture() && $winners->has($grant->user_id)) {
                $grant->update(['expires_at' => $current->active_until]);

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

                $grants->push(DiscordRoleGrant::create([
                    'user_id' => $user->id,
                    'discord_role_id' => $roleId,
                    'discord_user_id' => $discordUserId,
                    'expires_at' => $current->active_until,
                ]));

                if ($discordUserId !== null && $channelId) {
                    Http::withToken(config('services.discord.rabot_token'), 'Bot')
                        ->connectTimeout(3)
                        ->timeout(10)
                        ->post("https://discord.com/api/v10/channels/{$channelId}/messages", [
                            'content' => "<@{$discordUserId}> You earned the AOTW winner role!",
                        ])
                        ->throw();
                }
            } catch (Throwable $e) {
                Log::warning('Failed to sync AOTW winner', ['user_id' => $user->id, 'exception' => $e]);
            }
        }
    }
}
