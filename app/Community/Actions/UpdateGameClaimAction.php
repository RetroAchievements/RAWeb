<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Requests\UpdateGameClaimRequest;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\PlayerBadge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class UpdateGameClaimAction
{
    public function execute(AchievementSetClaim $claim, UpdateGameClaimRequest $request): void
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $newValues = $request->validated();

        $auditMessage = "{$currentUser->display_name} updated {$claim->user->display_name}'s claim.";

        if (array_key_exists('type', $newValues)) {
            $newType = (int) $newValues['type'];
            if ($claim->ClaimType != $newType) {
                $claim->ClaimType = $newType;

                $auditMessage .= " Claim Type: " . ClaimType::toString($newType) . '.';
            }
        }

        if (array_key_exists('set_type', $newValues)) {
            $newType = (int) $newValues['set_type'];
            if ($claim->SetType != $newType) {
                $claim->SetType = $newType;

                $auditMessage .= " Set Type: " . ClaimSetType::toString($newType) . '.';
            }
        }

        if (array_key_exists('status', $newValues)) {
            $newStatus = (int) $newValues['status'];
            if ($claim->Status != $newStatus) {
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
            if ($claim->Special != $newSpecial) {
                $claim->Special = $newSpecial;

                $auditMessage .= " Special: " . ClaimSetType::toString($newSpecial) . '.';
            }
        }

        if (array_key_exists('claimed', $newValues)) {
            $newDate = $newValues['claimed'];
            if ($claim->Created != $newDate) {
                $claim->Created = $newDate;

                $auditMessage .= " Claim Date: $newDate.";
            }
        }

        if (array_key_exists('finished', $newValues)) {
            $newDate = $newValues['finished'];
            if ($claim->Finished != $newDate) {
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
        addArticleComment("Server", ArticleType::SetClaim, $claim->game_id, "Claim completed by {$currentUser->User}");

        // also complete any collaboration claims
        $game = Game::find($claim->game_id);
        $game->achievementSetClaims()->active()->update(['Status' => $claim->Status, 'Finished' => $claim->Finished]);

        // TODO: these emails should be queued and sent asynchronously
        if ($claim->SetType == ClaimSetType::Revision) {
            // Send email to users who had previously mastered the set
            $userAwards = PlayerBadge::with('user')
                ->where('AwardData', $game->ID)
                ->where('AwardType', AwardType::Mastery)
                ->get();

            foreach ($userAwards as $userAward) {
                sendSetRevisionEmail(
                    $userAward->user->User,
                    $userAward->user->EmailAddress,
                    $userAward->AwardDataExtra === 1,
                    $game->ID,
                    $game->Title,
                );
            }
        } else {
            // Send email to set requestors
            $requestors = getSetRequestorsList($game->ID, true); // true to also fetch email
            foreach ($requestors as $requestor) {
                sendSetRequestEmail($requestor['Requestor'], $requestor['Email'], $game->ID, $game->Title);
            }
        }
    }
}
