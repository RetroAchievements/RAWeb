<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\User;
use App\Models\UserGameBadgePreference;

class UpdateMasteryBadgePreferenceAction
{
    /**
     * Set (or clear) which historical badge a user displays for a game they have mastered.
     *
     * @return string the resolved badge URL to display now
     */
    public function execute(User $user, Game $game, ?string $sha1): string
    {
        abort_unless($user->hasMasteredGame($game->id), 403, 'You have not mastered this game.');

        $canonicalSha1 = $game->badges()->whereNull('replaced_at')->value('sha1');

        // no sha1, or the canonical badge itself, means "use canonical"
        if ($sha1 === null || $sha1 === $canonicalSha1) {
            $user->badgePreferences()->where('game_id', $game->id)->delete();

            return media_asset($game->image_icon_asset_path);
        }

        $badge = $game->badges()->where('sha1', $sha1)->first();
        abort_if($badge === null, 422, 'That badge is not available for this game.');

        UserGameBadgePreference::updateOrCreate(
            ['user_id' => $user->id, 'game_id' => $game->id],
            ['sha1' => $sha1],
        );

        return $badge->badge_url;
    }
}
