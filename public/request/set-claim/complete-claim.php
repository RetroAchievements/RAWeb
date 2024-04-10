<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimSetType;
use App\Enums\Permissions;
use App\Models\PlayerBadge;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::JuniorDeveloper)) {
    return back()->withErrors(__('legacy.error.permissions'));
}

$input = Validator::validate(Arr::wrap(request()->post()), [
    'game' => 'required|integer|exists:GameData,ID',
]);

$gameID = (int) $input['game'];
$claimData = getClaimData($gameID);

if (!empty($claimData) && completeClaim($user, $gameID)) { // Check that the claim was successfully completed
    addArticleComment("Server", ArticleType::SetClaim, $gameID, "Claim completed by " . $user);

    // TODO: these emails should be queued and sent asynchronously
    if ($claimData[0]['SetType'] == ClaimSetType::Revision) {
        // Send email to users who had previously mastered the set
        $gameTitle = getGameData($gameID)['Title'];

        $userAwards = PlayerBadge::with('user')
            ->where('AwardData', $gameID)
            ->where('AwardType', AwardType::Mastery)
            ->get();

        foreach ($userAwards as $userAward) {
            sendSetRevisionEmail(
                $userAward->user->User,
                $userAward->user->EmailAddress,
                $userAward->AwardDataExtra === 1,
                $gameID,
                $gameTitle,
            );
        }
    } else {
        // Send email to set requestors
        $requestors = getSetRequestorsList($gameID, true); // need this to get email and probably game name to pass in.
        foreach ($requestors as $requestor) {
            sendSetRequestEmail($requestor['Requestor'], $requestor['Email'], $gameID, $requestor['Title']);
        }
    }

    return back()->with('success', __('legacy.success.ok'));
}

return back()->withErrors(__('legacy.error.error'));
