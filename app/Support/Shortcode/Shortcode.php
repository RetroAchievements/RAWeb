<?php

declare(strict_types=1);

namespace App\Support\Shortcode;

use Illuminate\Support\Facades\Cache;
use Thunder\Shortcode\Event\FilterShortcodesEvent;
use Thunder\Shortcode\EventContainer\EventContainer;
use Thunder\Shortcode\Events;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

final class Shortcode
{
    private HandlerContainer $handlers;

    public function __construct()
    {
        $this->handlers = (new HandlerContainer())
            ->add('b', fn (ShortcodeInterface $s) => '<b>' . $s->getContent() . '</b>')
            ->add('i', fn (ShortcodeInterface $s) => '<i>' . $s->getContent() . '</i>')
            ->add('u', fn (ShortcodeInterface $s) => '<u>' . $s->getContent() . '</u>')
            ->add('s', fn (ShortcodeInterface $s) => '<s>' . $s->getContent() . '</s>')
            ->add('img', fn (ShortcodeInterface $s) => '<img class="inline-image" src="' . ($s->getBbCode() ?: $s->getContent()) . '">')
            ->add('code', fn (ShortcodeInterface $s) => $this->renderCode($s))
            ->add('url', fn (ShortcodeInterface $s) => $this->renderUrlLink($s))
            ->add('link', fn (ShortcodeInterface $s) => $this->renderLink($s))
            ->add('spoiler', fn (ShortcodeInterface $s) => $this->renderSpoiler($s))
            ->add('ach', fn (ShortcodeInterface $s) => $this->embedAchievement((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('game', fn (ShortcodeInterface $s) => $this->embedGame((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('ticket', fn (ShortcodeInterface $s) => $this->embedTicket((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('user', fn (ShortcodeInterface $s) => $this->embedUser($s->getBbCode() ?: $s->getContent()));
    }

    public static function render(string $input, array $options = []): string
    {
        return (new Shortcode())->parse($input, $options);
    }

    public static function stripAndClamp(string $input, int $previewLength = 100): string
    {
        // Inject game and achievement data for shortcodes.
        // This is more desirable than showing "Game 123" or "Achievement 123".
        $injectionShortcodes = [
            // "[game=1]" --> "Sonic the Hedgehog (Mega Drive)"
            '~\[game=(\d+)]~i' => function ($matches) {
                $gameData = getGameData((int) $matches[1]);
                if ($gameData) {
                    return "{$gameData['Title']} ({$gameData['ConsoleName']})";
                }

                return "";
            },

            // "[ach=1]" --> "Ring Collector (5)"
            '~\[ach=(\d+)]~i' => function ($matches) {
                $achievementData = GetAchievementData((int) $matches[1]);
                if ($achievementData) {
                    return "{$achievementData['Title']} ({$achievementData['Points']})";
                }

                return "";
            },
        ];

        foreach ($injectionShortcodes as $pattern => $callback) {
            $input = preg_replace_callback($pattern, $callback, $input);
        }

        $stripPatterns = [
            // "[img=https://google.com/icon.png]" --> ""
            '~\[img(=)?([^]]+)]~i' => '',

            // "[img]https://google.com/icon.png[/img]" --> ""
            '~\[img\](.*?)\[/img\]~i' => '',

            // "[b]Hello[/b]" --> "Hello"
            '~\[(b|i|u|s|code)\](.*?)\[/\1\]~i' => '$2',
            '~\[(url|link).*?](.*?)\[/\1\]~i' => '$2',

            // "[spoiler]Top Secret[/spoiler]" --> "{SPOILER}"
            '~\[spoiler\](.*?)\[/spoiler\]~i' => "{SPOILER}",

            // "[ticket=123]" --> "Ticket 123"
            '~\[ticket(=)?(\d+)]~i' => 'Ticket $2',

            // "[user=Scott]" --> "@Scott"
            '~\[user(=)?([^]]+)]~i' => '@$2',

            // Fragments: opening tags without closing tags.
            '~\[(b|i|u|s|img|code|url|link|spoiler|ach|game|ticket|user)[^\]]*?\]~i' => '',
            '~\[(b|i|u|s|img|code|url|link|spoiler|ach|game|ticket|user)[^\]]*?$~i' => '...',

            // Fragments: closing tags without opening tags.
            '~\[/?(b|i|u|s|img|code|url|link|spoiler|ach|game|ticket|user)\]~i' => '',
        ];

        foreach ($stripPatterns as $stripPattern => $replacement) {
            $input = preg_replace($stripPattern, $replacement, $input);
        }

        // For cleaner previews, strip all unnecessary whitespace.
        $input = trim(preg_replace('/\s+/', ' ', $input));

        // As a failsafe, check the last 6 characters for any fragmented shortcodes and purge them.
        $lastSixChars = substr($input, -6);
        if (preg_match('/\[[^\]]{0,5}$/', $lastSixChars)) {
            $input = preg_replace('/\[[^\]]{0,5}$/', '...', $input);
        }

        // If the string is over the preview length, clamp it and add "..."
        // This can happen as a result of the replacement from above.
        if (strlen($input) > $previewLength) {
            $input = substr($input, 0, $previewLength) . '...';
        }

        // Handle edge case: if the input is just ellipses, show nothing.
        if ($input === "...") {
            $input = "";
        }

        return $input;
    }

    private function parse(string $input, array $options = []): string
    {
        // make sure to use attribute delimiter for string values
        // integers work with and without delimiter (ach, game, ticket, ...)
        $input = preg_replace('~\[img="?([^]"]*)"?]~i', '[img="$1"]', $input);
        $input = preg_replace('~\[url="?([^]"]*)"?]~i', '[url="$1"]', $input);
        $input = preg_replace('~\[user="?([^]"]*)"?]~i', '[user="$1"]', $input);

        // pass bbcode style url labeling to link handler
        $input = preg_replace('~\[url="?([^]"]*)"?](!\[)\[/url]~i', '[link url="$1"]$2[/link]', $input);

        // case insensitive
        foreach ($this->handlers->getNames() as $tag) {
            $input = preg_replace("~\[/$tag]~i", "[/$tag]", $input); // closing tag
            $input = preg_replace("~\[$tag]~i", "[$tag]", $input); // opening tag
            $input = preg_replace("~\[$tag=~i", "[$tag=", $input); // opening tag with value
        }

        $events = new EventContainer();
        $events->addListener(Events::FILTER_SHORTCODES, function (FilterShortcodesEvent $event) {
            // Note: can't disable parsing in code blocks yet as i has been used as separator within posts. Enable again after code has been migrated to something else
            // $parent = $event->getParent();
            // no parsing inside of code tags
            // if ($parent && ($parent->getName() === 'code' || $parent->hasAncestor('code'))) {
            //     $event->setShortcodes([]);
            // }
        });

        $processor = (new Processor(new RegularParser(), $this->handlers))
            ->withEventContainer($events);

        $html = $processor->process(nl2br($input, false));

        // linkify whatever's left
        if ($options['imgur'] ?? false) {
            $html = $this->autoEmbedImgur($html);
        }
        $html = $this->autoEmbedYouTube($html);
        $html = $this->autoEmbedTwitch($html);
        $html = $this->autolinkRetroachievementsUrls($html);

        return $this->autolinkUrls($html);
    }

    private function renderUrlLink(ShortcodeInterface $shortcode): string
    {
        return '<a href="' . $this->protocolPrefix($shortcode->getBbCode() ?: $shortcode->getContent()) . '">' . ($shortcode->getContent() ?: $this->protocolPrefix($shortcode->getBbCode())) . '</a>';
    }

    private function renderLink(ShortcodeInterface $shortcode): string
    {
        return '<a href="' . $this->protocolPrefix($shortcode->getParameter('url') ?: $shortcode->getContent()) . '">' . $shortcode->getContent() . '</a>';
    }

    private function protocolPrefix(?string $href): string
    {
        if (empty($href)) {
            return '';
        }

        $scheme = parse_url($href, PHP_URL_SCHEME);
        $host = parse_url($href, PHP_URL_HOST);

        if (empty($scheme)) {
            $href = 'https://' . ltrim($href, '/');
        } elseif ($scheme === 'http' && str_ends_with($host, 'retroachievements.org')) {
            $href = str_replace('http://', 'https://', $href);
        }

        return $href;
    }

    private function renderCode(ShortcodeInterface $shortcode): string
    {
        return '<pre class="codetags">' . str_replace('<br>', '', $shortcode->getContent() ?? '') . '</pre>';
    }

    private function renderSpoiler(ShortcodeInterface $shortcode): string
    {
        $content = $shortcode->getContent() ?? '';

        $id = uniqid((string) random_int(10000, 99999));

        // remove leading break
        $content = preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $content);

        return <<<EOF
            <div class="devbox">
                <span onclick="$('#spoiler_{$id}').toggle(); return false;">Spoiler (Click to show)</span>
                <div class="spoiler" id="spoiler_{$id}">{$content}</div>
            </div>
        EOF;
    }

    private function embedAchievement(int $id): string
    {
        $data = Cache::store('array')->rememberForever('achievement:' . $id . ':card-data', fn () => GetAchievementData($id));

        if (empty($data)) {
            return '';
        }

        return achievementAvatar($data, iconSize: 24);
    }

    private function embedGame(int $id): string
    {
        $data = Cache::store('array')->rememberForever('game:' . $id . ':card-data', fn () => getGameData($id));

        if (empty($data)) {
            return '';
        }

        return gameAvatar($data, iconSize: 24);
    }

    private function embedTicket(int $id): string
    {
        $ticketModel = GetTicketModel($id);

        if ($ticketModel == null) {
            return '';
        }

        return ticketAvatar($ticketModel, iconSize: 24);
    }

    private function embedUser(?string $username): string
    {
        if (empty($username)) {
            return '';
        }

        return userAvatar($username, icon: false);
    }

    private function autolinkRetroachievementsUrls(string $text): string
    {
        // see https://stackoverflow.com/a/2271552/580651:
        // [...] it's probably safe to assume a semicolon at the end of a URL is meant as sentence punctuation.
        // The same goes for other sentence-punctuation characters like periods, question marks, quotes, etc..
        // lookahead: (?<![!,.?;:"\'()-])
        return (string) preg_replace(
            '~
                (?:https?://)?      # Optional scheme. Either http or https.
                (?:www.)?           # Optional subdomain.
                (?:media.)?         # Optional subdomain.
                retroachievements\.        # Host.
                ([\w!#$%&\'()*+,./:;=?@\[\]-]+
                (?<![!,.?;:"\'()]))
                (?!                 # Assert URL is not pre-linked.
                  [^<>]*>           # Either inside a start tag,
                  | [^<>]*</a>      # End recognized pre-linked alts.
                )                   # End negative lookahead assertion.
            ~ix',
            '<a href="https://retroachievements.$1">https://retroachievements.$1</a>',
            $text
        );
    }

    private function autolinkUrls(string $text): string
    {
        // see https://stackoverflow.com/a/2271552/580651:
        // [...] it's probably safe to assume a semicolon at the end of a URL is meant as sentence punctuation.
        // The same goes for other sentence-punctuation characters like periods, question marks, quotes, etc..
        // lookahead: (?<![!,.?;:"\'()-])
        return (string) preg_replace(
            '~
            (https?://[\w!#$%&\'()*+,./:;=?@\[\]-]+(?<![!,.?;:"\'()]))
            (?!                 # Assert URL is not pre-linked.
              [^<>]*>           # Either inside a start tag,
              | [^<>]*</a>      # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ~ix',
            '<a href="$1">$1</a>',
            $text
        );
    }

    private function embedVideo(string $videoUrl): string
    {
        return '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' . $videoUrl . '" allowfullscreen></iframe></div>';
    }

    /**
     * @see http://stackoverflow.com/questions/5830387/how-to-find-all-youtube-video-ids-in-a-string-using-a-regex
     * This has been enhanced a little bit to support timestamp parameters.
     */
    private function autoEmbedYouTube(string $text): string
    {
        // Restore any ampersands escaped by sanitization.
        $text = str_replace('&amp;', '&', $text);

        return preg_replace_callback(
            '~
                (?:https?://)?      # Optional scheme. Either http or https.
                (?:[0-9A-Z-]+\.)?   # Optional subdomain.
                (?:                 # Group host alternatives.
                  youtu\.be/        # Either youtu.be (trailing slash required),
                | youtube\.com      # or youtube.com followed by
                  \S*               # Allow anything up to VIDEO_ID,
                  [^\w\\-\s]        # but char before ID is non-ID char.
                )                   # End host alternatives.
                ([\w\-]{11})        # $1: VIDEO_ID is exactly 11 chars.
                (?=[^\w\-]|$)       # Assert next char is non-ID or EOS.
                (?!                 # Assert URL is not pre-linked.
                  (?:               # Group pre-linked alternatives.
                    [^<>]*>         # Either inside a start tag,
                    | [^<>]*</a>    # or inside <a> element text contents.
                  )                 # End recognized pre-linked alts.
                )                   # End negative lookahead assertion.
                ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
            ~ix',
            function ($matches) {
                $videoId = $matches[1];
                $query = [];

                // Are there additional query parameters in the URL?
                if (isset($matches[2])) {
                    // Parse the query parameters and populate them into $query.
                    parse_str(ltrim($matches[2], '?'), $query);

                    // Check if the "t" parameter (timestamp) is present.
                    if (isset($query['t'])) {
                        // "t" has to be converted to a time compatible with youtube-nocookie.com embeds.
                        $query['start'] = $this->convertYouTubeTime($query['t']);

                        // Once converted, remove the "t" parameter so we don't accidentally duplicate it.
                        unset($query['t']);
                    }
                }

                $query = http_build_query($query);

                return $this->embedVideo("//www.youtube-nocookie.com/embed/$videoId" . ($query ? "?$query" : ""));
            },
            $text
        );
    }

    private function convertYouTubeTime(string $time): int
    {
        // If the time is numeric, it's already in seconds
        if (is_numeric($time)) {
            return (int) $time;
        }

        // If it's not numeric, it could be in the format of 1h30m15s, 30m15s, 15s, 90m etc.
        preg_match('/(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?/', $time, $matches);
        $hours = isset($matches[1]) ? intval($matches[1]) : 0;
        $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
        $seconds = isset($matches[3]) ? intval($matches[3]) : 0;

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    private function autoEmbedTwitch(string $text): string
    {
        if (mb_strpos($text, "twitch.tv") === false) {
            return $text;
        }

        $parent = parse_url(config('app.url'))['host'];

        // https://www.twitch.tv/videos/270709956
        // https://www.twitch.tv/gamingwithmist/v/40482810

        $text = (string) preg_replace(
            '~
                (?:https?://)?      # Optional scheme. Either http or https.
                (?:www.)?           # Optional subdomain.
                twitch.tv/.*        # Host.
                (?:videos|[^/]+/v)  # See path examples above.
                /(\d+)              # $1
                (?!                 # Assert URL is not pre-linked.
                  [?=&+%\w.-]*      # Allow URL (query) remainder.
                  (?:               # Group pre-linked alternatives.
                    [^<>]*>         # Either inside a start tag,
                    | [^<>]*</a>    # or inside <a> element text contents.
                  )                 # End recognized pre-linked alts.
                )                   # End negative lookahead assertion.
                ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
            ~ix',
            $this->embedVideo('//player.twitch.tv/?video=$1&parent=' . $parent . '&autoplay=false'),
            $text
        );

        // https://www.twitch.tv/collections/cWHCMbAY1xQVDA
        $text = preg_replace(
            '~(?:https?://)?(?:www.)?twitch.tv/collections/([a-z0-9]+)~ix',
            $this->embedVideo('//player.twitch.tv/?collection=$1&parent=' . $parent . '&autoplay=false'),
            $text
        );

        // https://clips.twitch.tv/AmorphousCautiousLegPanicVis
        $text = preg_replace(
            '~(?:https?://)?clips.twitch.tv/([a-z0-9]+)~i',
            $this->embedVideo('//clips.twitch.tv/embed?clip=$1&parent=' . $parent . '&autoplay=false'),
            $text
        );

        return (string) $text;
    }

    private function autoEmbedImgur(string $text): string
    {
        // https://imgur.com/gallery/bciLIYm.gifv
        // https://imgur.com/a/bciLIYm.gifv
        // https://i.imgur.com/bciLIYm.gifv
        // https://i.imgur.com/bciLIYm.webm
        // https://i.imgur.com/bciLIYm.mp4

        // https://imgur.com/gallery/bciLIYm -> no extension -> will be ignored (turns out as link)
        // https://imgur.com/a/bciLIYm.gif -> replaced by gif - potentially broken if it's a static image
        // https://imgur.com/a/bciLIYm.jpg -> downloads as gif if original is a gif, potentially large :/ can't do much about that

        $pattern = '~
            (?:https?://)?
            (?:[0-9a-z-]+\.)?
            imgur\.com
            (?:[\w/]*/)?
            (\w+)(\.\w+)?
            (?!                 # Assert URL is not pre-linked.
              [?=&+%\w.-]*      # Allow URL (query) remainder.
              (?:               # Group pre-linked alternatives.
                [^<>]*>         # Either inside a start tag,
                | [^<>]*<\/a>   # or inside <a> element text contents.
              )                 # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
        ~ix';

        preg_match_all($pattern, $text, $matches);
        if (empty($matches[0])) {
            return $text;
        }
        $replacements = [];
        $matchesCount = is_countable($matches[0]) ? count($matches[0]) : 0;
        for ($i = 0; $i < $matchesCount; $i++) {
            $id = $matches[1][$i];
            $extension = $matches[2][$i] ?? null;
            $extension = $extension === '.gif' ? '.gifv' : $extension;
            $replacements[$i] = $matches[0][$i];
            if (in_array($extension, ['.gifv', '.mp4', '.webm'])) {
                $replacements[$i] = '<a href="//imgur.com/' . $id . '"><div class="embed-responsive embed-responsive-16by9"><video controls class="embed-responsive-item"><source src="//i.imgur.com/' . $id . '.mp4" type="video/mp4"></video></div><div class="text-right mb-3"><small>view on imgur</small></div></a>';
            } elseif (in_array($extension, ['.jpg', '.png', '.jpeg'])) {
                $replacements[$i] = '<a href="//imgur.com/' . $id . '"><img class="inline-image" src="//i.imgur.com/' . $id . '.jpg" alt=""><div class="text-right mb-3"><small>view on imgur</small></div></a>';
            }
        }

        return preg_replace_array($pattern, $replacements, $text);
    }
}
