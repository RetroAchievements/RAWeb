<?php

declare(strict_types=1);

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

function normalize_shortcodes(string $input): string
{
    // TODO somewhere, all these entities should be fetched in a batch

    $modifiedInput = $input;

    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'user');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'game');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'achievement', 'ach');
    $modifiedInput = normalize_targeted_shortcodes($modifiedInput, 'ticket');

    return $modifiedInput;
}

function normalize_targeted_shortcodes(string $input, string $kind, ?string $tagName = null): string
{
    // Find any URL variants of user links and transform them into shortcode tags.
    // Ignore URLs that contain path or query params.
    $find = [
        "~\<a [^/>]*retroachievements\.org/" . $kind . "/([\w]{1,20})(/?(?![\w/?]))[^/>]*\][^</a>]*</a>~si",
        "~\[url[^\]]*retroachievements\.org/" . $kind . "/([\w]{1,20})(/?(?![\w/?]))[^\]]*\][^\[]*\[/url\]~si",
        "~\[url[^\]]*?" . $kind . "/([\w]{1,20})(/?(?![\w/?])).*?\[/url\]~si",
        "~https?://(?:[\w\-]+\.)?retroachievements\.org/" . $kind . "/([\w]{1,20})(/?(?![\w/?]))~si",
        "~https?://localhost(?::\d{1,5})?/" . $kind . "/([\w]{1,20})(/?(?![\w/?]))~si",
    ];
    $replace = "[" . ($tagName ?? $kind) . "=$1]";

    return preg_replace($find, $replace, $input);
}
