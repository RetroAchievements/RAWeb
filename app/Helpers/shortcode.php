<?php

declare(strict_types=1);

use App\Site\Models\User;
use App\Support\Shortcode\Converter\HTMLConverter;

/**
 * @deprecated Only used for news HTML sync. TODO: Remove when done
 */
function html2shortcode(string $value, string $id = ''): string
{
    /*
     * scan for html links -> [url=href]label[/url]
     * used in news
     */
    try {
        $converter = new HTMLConverter($value, $id);
        $value = $converter->toBBCode();
    } catch (Exception) {
        // $this->warn($exception->getMessage());
    }

    /*
     * TODO: scan shortcode for [user=username] -> [user=hashid-username]
     */

    return $value;
}

function url2shortcode(string $url): string
{
    /**
     * lowercase
     */
    $url = mb_strtolower($url);

    /**
     * make site links relative
     */
    $url = str_replace([
        'http://retroachievements.org/',
        'https://retroachievements.org/',
        'http://www.retroachievements.org/',
        'https://www.retroachievements.org/',
        'www.retroachievements.org/',
    ], '/', $url);

    /**
     * TODO: transform model links to model tags
     */
    $url = str_replace(['/game/'], ['game='], $url);

    $link = '[url=' . $url . ']' . $url . '[/url]';
    if (mb_strpos($url, 'game') === 0) {
        $link = '[' . $url . ']';
    }

    return $link;
}

function normalize_shortcodes(string $value): string
{
    $value = normalize_user_shortcodes($value);
    $value = normalize_game_shortcodes($value);
    $value = normalize_achievement_shortcodes($value);
    $value = normalize_ticket_shortcodes($value);

    return $value;
}

function normalize_user_shortcodes(string $value): string
{
    /**
     * find any url variants of user links and transform them into tags
     * ignores urls that contain path or query params
     */
    $find = [
        "~\<a [^/>]*retroachievements\.org/user/([\w]{1,20})(?![\w/?])[^/>]*\][^</a>]*</a>~si",
        "~\[url[^\]]*retroachievements\.org/user/([\w]{1,20})(?![\w/?])[^\]]*\][^\[]*\[/url\]~si",
        "~\[url[^\]]*?user/([\w]{1,20})(?![\w/?]).*?\[/url\]~si",
        "~https?://(?:[\w\-]+\.)?retroachievements\.org/user/([\w]{1,20})(?![\w/?])~si",
        "~https?://localhost(?::\d{1,5})?/user/([\w]{1,20})(?![\w/?])~si",
    ];
    $replace = '[user=$1]';
    $value = preg_replace($find, $replace, $value);

    /**
     * TODO clean up any other malformed shortcode tags
     * - remove quotes
     * - lowercase any site url that is not a user link (users want their name to be written correctly)
     */

    /**
     * TODO rebuild hash id prefixed username shortcodes
     * any user tag that has no dash in it -> no id there yet
     * only take those that have a username set
     *
     * TODO: fetch users in batch first
     */
    // $value = preg_replace_callback("~\[user=([^-]*?[\w]+?)\]~si", function ($matches) {
    //     $username = $userTag = $matches[1];
    //     $user = User::where('User', $username)->first();
    //     if ($user) {
    //         $userTag = $user->hashId . '-' . $username;
    //     }
    //
    //     return '[user=' . $userTag . ']';
    // }, $value ?? '');

    return $value ?? '';
}

function normalize_game_shortcodes(string $value): string
{
    $find = [
        "~\<a [^/>]*retroachievements\.org/game/(\d+)[^/>]*\][^</a>]*</a>~si",
        "~\[url[^\]]*retroachievements\.org/game/(\d+)[^\]]*\][^\[]*\[/url\]~si",
        "~https?://(?:[\w\-]+\.)?retroachievements\.org/game/(\d+)~si",
        "~https?://localhost(?::\d{1,5})?/game/(\d+)~si",
    ];
    $replace = '[game=$1]';

    return preg_replace($find, $replace, $value);
}

function normalize_achievement_shortcodes(string $value): string
{
    $find = [
        "~\<a [^/>]*retroachievements\.org/achievement/(\d+)[^/>]*\][^</a>]*</a>~si",
        "~\[url[^\]]*retroachievements\.org/achievement/(\d+)[^\]]*\][^\[]*\[/url\]~si",
        "~https?://(?:[\w\-]+\.)?retroachievements\.org/achievement/(\d+)~si",
        "~https?://localhost(?::\d{1,5})?/achievement/(\d+)~si",
    ];
    $replace = '[ach=$1]';

    return preg_replace($find, $replace, $value);
}

function normalize_ticket_shortcodes(string $value): string
{
    $find = [
        "~\<a [^/>]*retroachievements\.org/ticketmanager\.php\?i=(\d+)[^/>]*\][^</a>]*</a>~si",
        "~\[url[^\]]*retroachievements\.org/ticketmanager\.php\?i=(\d+)[^\]]*\][^\[]*\[/url\]~si",
        "~https?://(?:[\w\-]+\.)?retroachievements\.org/ticketmanager\.php\?i=(\d+)~si",
        "~https?://localhost(?::\d{1,5})?/ticketmanager\.php\?i=(\d+)~si",
    ];
    $replace = '[ticket=$1]';

    return preg_replace($find, $replace, $value);
}
