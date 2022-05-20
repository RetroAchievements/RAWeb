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
