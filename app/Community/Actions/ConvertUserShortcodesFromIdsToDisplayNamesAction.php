<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\User;

class ConvertUserShortcodesFromIdsToDisplayNamesAction
{
    /**
     * Converts [user=ID] shortcodes to [user=display_name] format.
     *
     * @param string[] $bodies array of text content containing shortcodes
     * @return string[] array of text content with converted shortcodes
     */
    public function execute(array $bodies): array
    {
        if (empty($bodies)) {
            return [];
        }

        // Extract all user IDs from the bodies.
        $pattern = '/\[user=(\d+)\]/';
        $userIds = collect();

        foreach ($bodies as $body) {
            preg_match_all($pattern, $body, $matches);
            if (!empty($matches[1])) {
                $userIds->push(...$matches[1]);
            }
        }

        // If there are no IDs, it's safe to bail.
        if ($userIds->isEmpty()) {
            return $bodies;
        }

        // Otherwise, fetch all referenced users in a single query.
        $users = User::withTrashed()
            ->whereIn('ID', $userIds->unique())
            ->get(['ID', 'display_name'])
            ->keyBy('ID');

        // Process each body and replace user IDs with display names.
        return array_map(function (string $body) use ($users, $pattern) {
            return preg_replace_callback(
                $pattern,
                function ($matches) use ($users) {
                    $userId = (int) $matches[1];
                    $user = $users->get($userId);

                    // If the user was not found or has no display name, leave the original shortcode.
                    if (!$user || !$user->display_name) {
                        return $matches[0];
                    }

                    return "[user={$user->display_name}]";
                },
                $body
            );
        }, $bodies);
    }
}
