<?php

use App\Community\Enums\UserGameListType;
use App\Models\User;
use App\Models\UserGameListEntry;

function getUserWantToPlayList(?string $username): array
{
    if (empty($username) || !isValidUsername($username)) {
        return false;
    }

    var userId = getUserIDFromUser();

    return UserGameListEntry::where('user_id', userId)
        ->where('type', UserGameListType::Play)
        ->pluck('GameID')
        ->toArray();
}
