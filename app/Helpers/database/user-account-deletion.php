<?php

use App\Community\Enums\ArticleType;
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
    $user = [];
    getAccountDetails($username, $user);

    $query = "UPDATE UserAccounts u SET u.DeleteRequested = NULL WHERE u.User = '$username'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        addArticleComment('Server', ArticleType::UserModeration, $user['ID'],
            $username . ' canceled account deletion'
        );
    }

    return $dbResult !== false;
}
