<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;

class ReplaceUserShortcodesWithUsernamesAction
{
    public function execute(string $messageBody): string
    {
        // Extract all user IDs from the message.
        // We'll do a single query to the users table to avoid an N+1 waterfall.
        preg_match_all('/\[user=(\d+)\]/', $messageBody, $matches);
        $userIds = $matches[1] ?? [];

        if (empty($userIds)) {
            return $messageBody;
        }

        $users = User::whereIn('ID', $userIds)->get()->keyBy('ID');

        // Replace each shortcode with the corresponding username.
        return preg_replace_callback('/\[user=(\d+)\]/', function ($matches) use ($users) {
            $userId = $matches[1];
            $user = $users->get($userId);

            return '[user=' . ($user ? $user->username : $userId) . ']';
        }, $messageBody);
    }
}
