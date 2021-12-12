<?php

namespace RA\Shortcode;

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
            ->add('img', fn (ShortcodeInterface $s) => '<img class="injectinlineimage" src="' . ($s->getBbCode() ?: $s->getContent()) . '">')
            ->add('code', fn (ShortcodeInterface $s) => $this->renderCode($s))
            ->add('url', fn (ShortcodeInterface $s) => $this->renderUrlLink($s))
            ->add('link', fn (ShortcodeInterface $s) => $this->renderLink($s))
            ->add('spoiler', fn (ShortcodeInterface $s) => $this->renderSpoiler($s))
            ->add('ach', fn (ShortcodeInterface $s) => $this->embedAchievement($s->getBbCode() ?: $s->getContent()))
            ->add('game', fn (ShortcodeInterface $s) => $this->embedGame($s->getBbCode() ?: $s->getContent()))
            ->add('ticket', fn (ShortcodeInterface $s) => $this->embedTicket($s->getBbCode() ?: $s->getContent()))
            ->add('user', fn (ShortcodeInterface $s) => $this->embedUser($s->getBbCode() ?: $s->getContent()));
    }

    public static function render(string $input, array $options = []): string
    {
        return (new Shortcode())->parse($input, $options);
    }

    private function parse(string $input, array $options = []): string
    {
        // make sure to use attribute delimiter for string values
        // integers work with and without delimiter (ach, game, ticket, ...)
        $input = preg_replace('~\[img="?([^]"]*)"?]~i', '[img="$1"]', $input);
        $input = preg_replace('~\[url="?([^]"]*)"?]~i', '[url="$1"]', $input);
        $input = preg_replace('~\[user="?([^]"]*)"?]~i', '[user="$1"]', $input);

        // pass bbcode style url labeling to link handler
        $input = preg_replace('~\[url="?([^]"]*)"?](.+)\[/url]~i', '[link url="$1"]$2[/link]', $input);

        // case insensitive
        foreach ($this->handlers->getNames() as $tag) {
            $input = preg_replace("~$tag]~i", "$tag]", $input);
            $input = preg_replace("~\[$tag~i", "[$tag", $input);
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
        $html = $this->linkifyBasicURLs($html);

        return $html;
    }

    private function renderUrlLink(ShortcodeInterface $shortcode): string
    {
        return '<a href="' . ($shortcode->getBbCode() ?: $shortcode->getContent()) . '">' . ($shortcode->getContent() ?: $shortcode->getBbCode()) . '</a>';
    }

    private function renderLink(ShortcodeInterface $shortcode): string
    {
        return '<a href="' . ($shortcode->getParameter('url') ?: $shortcode->getContent()) . '">' . $shortcode->getContent() . '</a>';
    }

    private function renderCode(ShortcodeInterface $shortcode): string
    {
        return '<pre class="codetags">' . str_replace('<br>', '', $shortcode->getContent()) . '</pre>';
    }

    private function renderSpoiler(ShortcodeInterface $shortcode): string
    {
        $content = $shortcode->getContent();

        $id = uniqid((string) mt_rand(10000, 99999));

        // remove leading break
        $content = preg_replace('/^(?:<br\s*\/?>\s*)+/', '', $content);

        return <<<EOF
            <div class="devbox">
                <span onclick="$('#spoiler_{$id}').toggle(); return false;">Spoiler (Click to show):</span><br>
                <div class="spoiler" id="spoiler_{$id}">{$content}</div>
            </div>
        EOF;
    }

    private function embedAchievement($id): string
    {
        $achData = [];
        getAchievementMetadata($id, $achData);
        if (empty($achData)) {
            return '';
        }

        return GetAchievementAndTooltipDiv(
            $achData['AchievementID'],
            $achData['AchievementTitle'],
            $achData['Description'],
            $achData['Points'],
            $achData['GameTitle'],
            $achData['BadgeName'],
            $achData['ConsoleName'],
            false
        );
    }

    private function embedGame($id): string
    {
        $gameData = [];
        getGameTitleFromID(
            $id,
            $gameName,
            $consoleIDOut,
            $consoleName,
            $forumTopicID,
            $gameData
        );
        if (empty($gameData)) {
            return '';
        }

        return GetGameAndTooltipDiv($id, $gameName, $gameData['GameIcon'], $consoleName);
    }

    private function embedTicket($id): string
    {
        $ticketModel = GetTicketModel($id);

        if ($ticketModel == null) {
            return '';
        }

        return GetTicketAndTooltipDiv($ticketModel);
    }

    private function embedUser($username): string
    {
        return GetUserAndTooltipDiv($username, false);
    }

    private function linkifyBasicURLs($text)
    {
        $text = preg_replace(
            '~
            (https?://[a-z0-9_./?=&#%:+(),-]+)
            (?!                 # Assert URL is not pre-linked.
              [?=&+%\w.-]*      # Allow URL (query) remainder.
              (?:               # Group pre-linked alternatives.
                [^<>]*>         # Either inside a start tag,
                | [^<>]*</a>   # or inside <a> element text contents.
              )                 # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
            ~ix',
            ' <a href="$1" target="_blank" rel="noopener">$1</a> ',
            $text
        );

        return $text;
    }

    private function embedVideo($videoUrl): string
    {
        return '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' . $videoUrl . '" allowfullscreen></iframe></div>';
    }

    /**
     * from http://stackoverflow.com/questions/5830387/how-to-find-all-youtube-video-ids-in-a-string-using-a-regex
     * @param mixed $text
     */
    private function autoEmbedYouTube($text)
    {
        // http://www.youtube.com/v/YbKzgRwF91w
        // http://www.youtube.com/watch?v=1zMHaHPXqqg
        // http://youtu.be/-D06lkNS3-k
        // https://youtu.be/66ohBw9O6NU
        // https://www.youtube.com/embed/Fmwr6T2JHc4
        // https://www.youtube.com/watch?v=1YiNYWpwn7o
        // www.youtube.com/watch?v=Yjba9rvs4iU

        $text = preg_replace(
            '~
                (?:https?://)?      # Optional scheme. Either http or https.
                (?:[0-9A-Z-]+\.)?   # Optional subdomain.
                (?:                 # Group host alternatives.
                  youtu\.be/       # Either youtu.be (trailing slash required),
                | youtube\.com      # or youtube.com followed by
                  \S*               # Allow anything up to VIDEO_ID,
                  [^\w\-\s]         # but char before ID is non-ID char.
                )                   # End host alternatives.
                ([\w\-]{11})        # $1: VIDEO_ID is exactly 11 chars.
                (?=[^\w\-]|$)       # Assert next char is non-ID or EOS.
                (?!                 # Assert URL is not pre-linked.
                  [?=&+%\w.-]*      # Allow URL (query) remainder.
                  (?:               # Group pre-linked alternatives.
                    [^<>]*>         # Either inside a start tag,
                    | [^<>]*</a>   # or inside <a> element text contents.
                  )                 # End recognized pre-linked alts.
                )                   # End negative lookahead assertion.
                ([?=&+%\w.-]*)      # Consume any URL (query) remainder.
            ~ix',
            $this->embedVideo('//www.youtube-nocookie.com/embed/$1$2'),
            $text
        );

        return $text;
    }

    private function autoEmbedTwitch($text): string
    {
        if (mb_strpos($text, "twitch.tv") === false) {
            return $text;
        }

        $parent = parse_url(getenv('APP_URL'))['host'];

        // https://www.twitch.tv/videos/270709956
        // https://www.twitch.tv/gamingwithmist/v/40482810

        $text = preg_replace(
            '~
                (?:https?://)?      # Optional scheme. Either http or https.
                (?:www.)?           # Optional subdomain.
                twitch.tv/.*        # Host.
                (?:videos|[^/]+/v)  # See path examples above.
                /([0-9]+)           # $1
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

        return $text;
    }

    private function autoEmbedImgur(string $text): string
    {
        // https://imgur.com/gallery/bciLIYm.gifv
        // https://imgur.com/a/bciLIYm.gifv
        // https://i.imgur.com/bciLIYm.gifv
        // https://i.imgur.com/bciLIYm.webm
        // https://i.imgur.com/bciLIYm.mp4

        // https://imgur.com/gallery/bciLIYm -> no extension -> will be ignored (turns out as link)
        // https://imgur.com/a/bciLIYm.gif -> replaced by gifv - potentially broken if it's a static image
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
        if (!count($matches[0])) {
            return $text;
        }
        $replacements = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            $id = $matches[1][$i];
            $extension = $matches[2][$i] ?? null;
            $extension = $extension === '.gif' ? '.gifv' : $extension;
            $replacements[$i] = $matches[0][$i];
            if (in_array($extension, ['.gifv', '.mp4', '.webm'])) {
                $replacements[$i] = '<a href="//imgur.com/' . $id . '" target="_blank" rel="noopener"><div class="embed-responsive embed-responsive-16by9"><video controls class="embed-responsive-item"><source src="//i.imgur.com/' . $id . '.mp4" type="video/mp4"></video></div><div class="text-right mb-3"><small>view on imgur</small></div></a>';
            } elseif (in_array($extension, ['.jpg', '.png', '.jpeg'])) {
                $replacements[$i] = '<a href="//imgur.com/' . $id . '" target="_blank" rel="noopener"><img class="injectinlineimage" src="//i.imgur.com/' . $id . '.jpg" alt=""><div class="text-right mb-3"><small>view on imgur</small></div></a>';
            }
        }
        $text = preg_replace_array($pattern, $replacements, $text);

        return $text;
    }
}
