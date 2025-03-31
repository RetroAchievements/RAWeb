<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\UserGameListType;
use App\Mail\SetAchievementsPublishedNotificationMail;
use App\Mail\SetRevisionNotificationMail;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Models\UserGameListEntry;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UpdateGameClaimAction
{
    public function execute(AchievementSetClaim $claim, array $newValues): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $auditMessage = "{$currentUser->display_name} updated {$claim->user->display_name}'s claim.";

        if (array_key_exists('type', $newValues)) {
            $newType = (int) $newValues['type'];
            if ($claim->ClaimType !== $newType) {
                $claim->ClaimType = $newType;

                $auditMessage .= " Claim Type: " . ClaimType::toString($newType) . '.';
            }
        }

        if (array_key_exists('set_type', $newValues)) {
            $newType = (int) $newValues['set_type'];
            if ($claim->SetType !== $newType) {
                $claim->SetType = $newType;

                $auditMessage .= " Set Type: " . ClaimSetType::toString($newType) . '.';
            }
        }

        if (array_key_exists('status', $newValues)) {
            $newStatus = (int) $newValues['status'];
            if ($claim->Status !== $newStatus) {
                $claim->Status = $newStatus;

                $auditMessage .= " Claim Status: " . ClaimStatus::toString($newStatus) . '.';

                if (!ClaimStatus::isActive($newStatus)) {
                    $claim->Finished = Carbon::now();

                    if ($newStatus === ClaimStatus::Complete) {
                        $this->processCompletedClaim($claim, $currentUser);
                    }
                }
            }
        }

        if (array_key_exists('special', $newValues)) {
            $newSpecial = (int) $newValues['special'];
            if ($claim->Special !== $newSpecial) {
                $claim->Special = $newSpecial;

                $auditMessage .= " Special: " . ClaimSpecial::toString($newSpecial) . '.';
            }
        }

        if (array_key_exists('claimed', $newValues)) {
            $newDate = $newValues['claimed'];
            if ($claim->Created->notEqualTo($newDate)) {
                $claim->Created = $newDate;

                $auditMessage .= " Claim Date: $newDate.";
            }
        }

        if (array_key_exists('finished', $newValues)) {
            $newDate = $newValues['finished'];
            if ($claim->Finished->notEqualTo($newDate)) {
                $claim->Finished = $newDate;

                $auditMessage .= " End Date: $newDate.";
            }
        }

        if ($claim->isDirty()) {
            $claim->save();

            addArticleComment("Server", ArticleType::SetClaim, $claim->game_id, $auditMessage);
        }
    }

    private function processCompletedClaim(AchievementSetClaim $claim, User $currentUser): void
    {
        addArticleComment("Server", ArticleType::SetClaim, $claim->game_id, "Claim completed by {$currentUser->display_name}");

        // also complete any collaboration claims
        $game = Game::find($claim->game_id);
        $game->achievementSetClaims()->active()->update(['Status' => $claim->Status, 'Finished' => $claim->Finished]);

        if ($claim->SetType === ClaimSetType::Revision) {
            // Send email to users who had previously mastered the set
            $userAwards = PlayerBadge::with('user')
                ->where('AwardData', $game->ID)
                ->where('AwardType', AwardType::Mastery)
                ->get();

            foreach ($userAwards as $userAward) {
                if ($userAward->user && !$userAward->user->banned_at) {
                    Mail::to($userAward->user)->queue(new SetRevisionNotificationMail(
                        $userAward->user,
                        $game,
                        $userAward->AwardDataExtra === 1
                    ));
                }
            }
        } else {
            $setRequests = UserGameListEntry::where('GameID', $game->id)
                ->where('type', UserGameListType::AchievementSetRequest)
                ->with('user')
                ->get();

            foreach ($setRequests as $setRequest) {
                if ($setRequest->user && !$setRequest->user->banned_at) {
                    Mail::to($setRequest->user)->queue(new SetAchievementsPublishedNotificationMail(
                        $setRequest->user,
                        $game
                    ));
                }
            }
        }

        $webhookUrl = config('services.discord.webhook.claims');
        if (!empty($webhookUrl)) {
            $payload = [
                'username' => 'Claim Bot',
                'avatar_url' => media_asset('UserPic/QATeam.png'),
                'content' => route('game.show', $game) . "\n:white_check_mark: " .
                             "Claim completed by " . $currentUser->display_name,
            ];
            (new Client())->post($webhookUrl, ['json' => $payload]);
        }
    }
}
