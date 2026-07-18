<?php

declare(strict_types=1);

use App\Models\User;

function convertUserUrlsToShortcodes(string $input): string
{
    // Find all instances of user URLs in the input.
    preg_match_all("~https?://(?:[\w\-]+\.)?retroachievements\.org/user/([\w\-]+)(/?(?![\w/?]))~i", $input, $matches);

    // Map usernames to their corresponding IDs.
    $usernames = array_unique($matches[1]);
    $users = User::whereIn('username', $usernames)->get()->keyBy('username');

    // Replace URLs with shortcodes.
    foreach ($matches[1] as $username) {
        $userId = $users[$username]->id ?? $username;  // Default to username if user not found
        $input = str_replace("https://retroachievements.org/user/{$username}", "[user={$userId}]", $input);
    }

    return $input;
}

function normalize_shortcodes(string $input): string
{
    // TODO somewhere, all these entities should be fetched in a batch

    $modifiedInput = convertUserUrlsToShortcodes($input);

    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'user');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'game');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'hub');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'event');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'achievement', 'ach');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'ticket');

    return $modifiedInput;
}

function normalize_targeted_shortcodes(string $input, string $kind, ?string $tagName = null): string
{
    // Find any URL variants of user links and transform them into shortcode tags.
    // First, handle URLs with a ?set= query param (these are games).
    if ($kind === 'game') {
        $findWithSet = [
            "~https?://(?:[\w\-]+\.)?retroachievements\.org/game/([\w]{1,20})(?:-[^\s\"'<>]*)?(?:/)?\\?set=(\d+)~si",
            "~https?://localhost(?::\d{1,5})?/game/([\w]{1,20})(?:-[^\s\"'<>]*)?(?:/)?\\?set=(\d+)~si",
        ];
        $replaceWithSet = "[game=$1?set=$2]";
        $input = preg_replace($findWithSet, $replaceWithSet, $input);
    }

    // Then, handle URLs without query params.
    // Ignore URLs that contain path or query params.
    $find = [
        "~\<a [^/>]*retroachievements\.org/" . $kind . "/([\w]{1,20})(?:-[^\s\"'<>]*)?(/?(?![\w/?]))[^/>]*\][^</a>]*</a>~si",
        "~\[url[^\]]*retroachievements\.org/" . $kind . "/([\w]{1,20})(?:-[^\s\"'<>]*)?(/?(?![\w/?]))[^\]]*\][^\[]*\[/url\]~si",
        "~https?://(?:[\w\-]+\.)?retroachievements\.org/" . $kind . "/([\w]{1,20})(?:-[^\s\"'<>]*)?(/?(?![\w/?]))~si",
        "~https?://localhost(?::\d{1,5})?/" . $kind . "/([\w]{1,20})(?:-[^\s\"'<>]*)?(/?(?![\w/?]))~si",
    ];
    $replace = "[" . ($tagName ?? $kind) . "=$1]";

    return preg_replace($find, $replace, $input);
}
