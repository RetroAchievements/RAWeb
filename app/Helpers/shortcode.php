<?php

declare(strict_types=1);

use App\Community\Support\Converter\HTMLConverter;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Site\Models\User;
use App\Support\Shortcode\ShortcodeModelCollector;
use Jenssegers\Optimus\Optimus;

/**
 * @throws Exception
 */
function parseShortcodes(?string $input = null, bool $extractModels = false): ?string
{
    if (!$input) {
        return null;
    }

    /**
     * TODO: cache output? it really does a lot there...
     */

    /**
     * Run Laravel's escape function to start with properly sanitized output
     */
    $input = e($input);

    /**
     * give it some breaks
     */
    $value = $input = nl2br($input);

    /*
     * TODO: note that there are no tags in the content anymore
     * find any long url variants of site-tags
     * does not allow for links that have a different link label than the model's icon/name
     * so let's not do that for now - too many variants
     * users are automatically parsed on save in HasShortcodeFields Trait nonetheless
     * we want to make sure users are primarily referenced by their id, not their username
     */
    // $find = [
    //     "~\<a [^/>]*retroachievements\.org/achievement/([0-9]{1,20})[^/>]*\][^</a>]*</a>~si",
    //     "~\[url[^\]]*retroachievements\.org/achievement/([0-9]{1,20})[^\]]*\][^\[]*\[/url\]~si",
    //     "~\<a [^/>]*retroachievements\.org/game/([0-9]{1,10})[^/>]*\][^</a>]*</a>~si",
    //     "~\[url[^\]]*retroachievements\.org/game/([0-9]{1,10})[^\]]*\][^\[]*\[/url\]~si",
    // ];
    // $replace = [
    //     "[ach=$1]",
    //     "[ach=$1]",
    //     "[game=$1]",
    //     "[game=$1]",
    // ];
    // $value = preg_replace($find, $replace, $value);

    /*
     * extract model tags for eager loading
     * will end here
     */
    if ($extractModels) {
        preg_replace_callback("~\[ach=(.*?)\]~si", function (array $matches) {
            if (count($matches) < 1) {
                return '';
            }
            ShortcodeModelCollector::add(Achievement::class, (int) $matches[1]);
        }, $value);
        preg_replace_callback("~\[user=([\d]*?)-(?:[\w]+?)\]~si", function (array $matches) {
            if (count($matches) < 1) {
                return '';
            }
            ShortcodeModelCollector::add(User::class, app(Optimus::class)->decode((int) $matches[1]));
        }, $value);
        preg_replace_callback("~\[game=(.*?)\]~si", function (array $matches) {
            if (count($matches) < 1) {
                return '';
            }
            ShortcodeModelCollector::add(Game::class, (int) $matches[1]);
        }, $value);

        return null;
    }

    /*
     * parse model tags
     */
    // $value = preg_replace_callback("~\[ach=(.*?)\]~si", function (array $matches) {
    //     if (count($matches) < 1) {
    //         return '';
    //     }
    //     /** @var ?Achievement $achievement */
    //     $achievement = ShortcodeModelCollector::get(Achievement::class, (int) $matches[1]);
    //
    //     if (!$achievement) {
    //         return '';
    //     }
    //
    //     try {
    //         return achievement_avatar($achievement, 'icon', 'xs') . achievement_avatar($achievement);
    //     } catch (Throwable) {
    //     }
    //
    //     return '';
    // }, $value);
    // $value = preg_replace_callback("~\[user=([\d]*?)-(?:[\w]+?)\]~si", function (array $matches) {
    //     if (count($matches) < 1) {
    //         return '';
    //     }
    //     /** @var ?User $user */
    //     $user = ShortcodeModelCollector::get(User::class, app(Optimus::class)->decode((int) $matches[1]));
    //
    //     if (!$user) {
    //         return '';
    //     }
    //
    //     try {
    //         return user_avatar($user, 'icon', 'xs') . user_avatar($user);
    //         // return user_avatar($user);
    //     } catch (Throwable) {
    //     }
    //
    //     return '';
    // }, $value ?? '');
    // $value = preg_replace_callback("~\[game=(.*?)\]~si", function (array $matches) {
    //     if (count($matches) < 1) {
    //         return '';
    //     }
    //     /** @var ?Game $game */
    //     $game = ShortcodeModelCollector::get(Game::class, (int) $matches[1]);
    //
    //     if (!$game) {
    //         return '';
    //     }
    //
    //     try {
    //         return game_avatar(
    //             $game,
    //             'icon',
    //             'xs'
    //         ) . game_avatar($game)
    //             . (($game->system ?? false) ? ' <span class="badge embedded">' . system_avatar($game->system) . '</span>' : '');
    //     } catch (Throwable) {
    //     }
    //
    //     return '';
    // }, $value ?? '');

    if (!$value) {
        return $value;
    }

    /**
     * TODO: parse embeddable urls
     */
    // $value = parseYouTubeURLs($value);
    // $value = parseTwitchURLs($value);
    // $value = parseImgurURLs($value);
    // $value = parseURLs($value);

    $value = shortcode2html($value);

    /**
     * sanitize breaks
     * two breaks are enough! it's like adding page breaks in word by hitting enter ...
     * remove leading and trailing breaks
     */
    $value = str_replace(["\r", "\n"], '', $value);
    $value = str_replace('<br />', '<br>', $value);
    $value = str_replace('<br><br><br>', '', $value);
    $value = preg_replace('/^(<br>){0,}|(<br>){0,}$/', '', $value);

    return is_string($value) ? $value : $input;
}

function shortcode2html(string $input): string
{
    /**
     * translate shortcodes to html
     */
    $find = [
        '~\[b\](.*?)\[/b\]~s',
        '~\[i\](.*?)\[/i\]~s',
        '~\[s\](.*?)\[/s\]~s',
        '~\[img=(.*?)\]~s',
        "~\[url=[\"']?(?:https?://retroachievements.org)(.*?)[\"']?](.*?)\[/url\]~s",
        "~\[url=[\"']?((?:https?)://.*?)[\"']?](.*?)\[/url\]~s",
        "~\[url=[\"']?(.*?)[\"']?](.*?)\[/url\]~s",
        '~\[u\](.*?)\[/u\]~s',
    ];
    $replace = [
        '<b>$1</b>',
        '<i>$1</i>',
        '<s>$1</s>',
        '<img src="$1" alt="">',
        '<a href="$1">$2</a>',
        '<a href="/r?url=$1">$2</a>',
        '<a href="$1">$2</a>',
        '<span style="text-decoration:underline;">$1</span>',
    ];

    $text = preg_replace($find, $replace, $input);

    return is_string($text) ? $text : $input;
}

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
     */
    $find = [
        "~\<a [^/>]*retroachievements\.org/user/([\w]{1,20})[^/>]*\][^</a>]*</a>~si",
        "~\[url[^\]]*retroachievements\.org/user/([\w]{1,20})[^\]]*\][^\[]*\[/url\]~si",
        "~\[url[^\]]*?user/([\w]*).*?\[/url\]~si",
        "~https?://(?:[\w\-]+\.)?retroachievements\.org/user/([\w]{1,20})~si",
        "~https?://localhost(?::\d{1,5})?/user/([\w]{1,20})~si",
    ];
    $replace = '[user=$1]';
    $value = preg_replace($find, $replace, $value);

    /**
     * TODO clean up any other malformed shortcode tags
     * - remove quotes
     * - lowercase any site url that is not a user link (users want their name to be written correctly)
     */

    /**
     * any user tag that has no dash in it -> no id there yet
     * only take those that have a username set
     *
     * TODO: fetch users in batch first
     */
    $value = preg_replace_callback("~\[user=([^-]*?[\w]+?)\]~si", function ($matches) {
        $username = $userTag = $matches[1];
        $user = User::where('username', mb_strtolower($username))->first();
        if ($user) {
            $userTag = $user->hashId . '-' . $username;
        }

        return '[user=' . $userTag . ']';
    }, $value ?? '');

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