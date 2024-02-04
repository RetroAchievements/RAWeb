<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
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

function deleteRequest(string $username, ?string $date = null): bool
{
    $user = [];
    getAccountDetails($username, $user);

    if ($user['DeleteRequested']) {
        return false;
    }

    // Cap permissions
    $permission = min($user['Permissions'], Permissions::Registered);

    $date ??= date('Y-m-d H:i:s');
    $query = "UPDATE UserAccounts u SET u.DeleteRequested = '$date', u.Permissions = $permission WHERE u.User = '$username'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false) {
        addArticleComment('Server', ArticleType::UserModeration, $user['ID'],
            $username . ' requested account deletion'
        );

        SendDeleteRequestEmail($username, $user['EmailAddress'], $date);
    }

    return $dbResult !== false;
}
