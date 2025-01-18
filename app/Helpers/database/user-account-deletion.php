<?php

use App\Community\Enums\ArticleType;
use App\Models\User;
use Carbon\Carbon;

function getDeleteDate(string|Carbon $deleteRequested): string
{
    if (empty($deleteRequested)) {
        return '';
    }

    if (!$deleteRequested instanceof Carbon) {
        $deleteRequested = Carbon::parse($deleteRequested);
    }

    return $deleteRequested->addDays(14)->format('Y-m-d');
}

function cancelDeleteRequest(string $username): bool
{
    $user = User::whereName($username)->first();

    $query = "UPDATE UserAccounts u 
        SET u.DeleteRequested = NULL 
        WHERE u.User = '$username' OR u.display_name = '$username'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        addArticleComment('Server', ArticleType::UserModeration, $user->id,
            $user->display_name . ' canceled account deletion'
        );
    }

    return $dbResult !== false;
}
