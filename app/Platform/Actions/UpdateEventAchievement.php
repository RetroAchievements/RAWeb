<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\PlayerAchievement;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Illuminate\Support\Carbon;

class UpdateEventAchievement
{
    public function execute(
        Achievement $achievement,
        ?Achievement $sourceAchievement,
        ?Carbon $activeFrom,
        ?Carbon $activeUntil,
        ?User $user = null,
    ): void {

        $eventAchievement = EventAchievement::updateOrCreate(
            ['achievement_id' => $achievement->id],
            [
                'source_achievement_id' => $sourceAchievement?->id,
                'active_from' => $activeFrom,
                'active_until' => $activeUntil,
            ],
        );

        if ($sourceAchievement) {
            $achievement->title = $sourceAchievement->title;
            $achievement->description = $sourceAchievement->description;
            $achievement->BadgeName = $sourceAchievement->BadgeName;
            $achievement->save();

            $winners = PlayerAchievement::where('achievement_id', '=', $sourceAchievement->id)
                ->whereNotNull('unlocked_hardcore_at');

            if ($eventAchievement->active_from) {
                $winners->where('unlocked_hardcore_at', '>=', $eventAchievement->active_from);
            }
            if ($eventAchievement->active_until) {
                $winners->where('unlocked_hardcore_at', '<', $eventAchievement->active_until);
            }

            foreach ($winners->get() as $winner) {
                dispatch(new UnlockPlayerAchievementJob($winner->user_id, $achievement->id, true, $winner->unlocked_hardcore_at))
                    ->onQueue('player-achievements');
            }
        }

        if ($user) {
            $auditLog = "{$user->display_name} updated this achievement's event data.";

            addArticleComment('Server', ArticleType::Achievement, $achievement->id, $auditLog, $user->User);
        }
    }
}
