<?php

use App\Community\Enums\UserRelationStatus;
use App\Enums\Permissions;
use App\Enums\UserPreference;
use App\Models\User;
use App\Models\UserRelation;
use App\Notifications\Community\CommunityFriendNotification;

function changeFriendStatus(User $senderUser, User $targetUser, UserRelationStatus $newStatus): string
{
    $existingUserRelation = UserRelation::where('user_id', $senderUser->id)
        ->where('related_user_id', $targetUser->id)
        ->first();

    $newRelationship = false;
    if ($existingUserRelation) {
        $oldStatus = $existingUserRelation->status;
    } else {
        $newRelationship = true;
        $oldStatus = UserRelationStatus::NotFollowing;
    }

    if (
        $newStatus === UserRelationStatus::Following
        && ($targetUser->isBlocking($senderUser) || $senderUser->isFreshAccount())
    ) {
        // Don't allow follows if one user is blocking the other, or if the
        // person initiating the follow has little to no activity on their account.
        return "error";
    }

    // Upsert the relationship.
    if ($existingUserRelation) {
        $existingUserRelation->status = $newStatus;
        $existingUserRelation->save();
    } else {
        UserRelation::create([
            'user_id' => $senderUser->id,
            'related_user_id' => $targetUser->id,
            'status' => $newStatus,
        ]);
    }

    switch ($newStatus) {
        case UserRelationStatus::Following:
            // Attempt to notify the target of the new follower.
            if ($newRelationship && BitSet($targetUser->preferences_bitfield, UserPreference::EmailOn_Followed)) {
                $targetUser->notify(new CommunityFriendNotification($senderUser));
            }

            return "user_follow";

        case UserRelationStatus::NotFollowing:
            return match ($oldStatus) {
                UserRelationStatus::Following => "user_unfollow",
                UserRelationStatus::Blocked => "user_unblock",
                default => "error",
            };

        case UserRelationStatus::Blocked:
            if (!$targetUser->isBlocking($senderUser)) {
                // If the other user hasn't blocked the user, clear out their friendship status too.
                UserRelation::where('user_id', $targetUser->id)
                    ->where('related_user_id', $senderUser->id)
                    ->update(['status' => UserRelationStatus::NotFollowing]);
            }

            return "user_block";

        default:
            return "error";
    }
}

function GetExtendedFriendsList(User $user): array
{
    $friendList = $user->followedUsers()
        ->where('Permissions', '>=', Permissions::Unregistered)
        ->whereNull('deleted_at')
        ->orderBy('rich_presence_updated_at', 'desc')
        ->get()
        ->map(function ($friend) {
            return [
                'User' => $friend->display_name,
                'Friendship' => UserRelationStatus::from($friend->pivot->status)->toLegacyInteger(),
                'LastGameID' => (int) $friend->rich_presence_game_id,
                'LastSeen' => empty($friend->rich_presence) ? 'Unknown' : strip_tags($friend->rich_presence),
                'LastActivityTimestamp' => $friend->rich_presence_updated_at?->format('Y-m-d H:i:s'),
            ];
        });

    return $friendList->toArray();
}

function GetFriendsSubquery(string $user, bool $includeUser = true, bool $returnUserIds = false): string
{
    $userModel = User::whereName($user)->first();
    $userId = $userModel->id;

    // TODO: why is it so much faster to run this query and build the IN list
    //       than to use it as a subquery? i.e. "AND aw.User IN ($friendsSubquery)"
    //       local testing took over 2 seconds with the subquery and < 0.01 seconds
    //       total for two separate queries
    $friendValues = $userModel->followedUsers()
        ->where('Permissions', '>=', Permissions::Unregistered)
        ->pluck($returnUserIds ? 'users.id' : 'users.username');

    $friends = [];
    foreach ($friendValues as $value) {
        $friends[] = $returnUserIds ? $value : "'" . $value . "'";
    }

    if ($includeUser) {
        $friends[] = $returnUserIds ? $userId : "'$user'";
    } elseif (count($friends) == 0) {
        return "NULL";
    }

    return implode(',', $friends);
}

/**
 * Gets the number of friends for the input user.
 */
function getFriendCount(?User $user): int
{
    if (!$user) {
        return 0;
    }

    return $user->followedUsers()->count();
}
