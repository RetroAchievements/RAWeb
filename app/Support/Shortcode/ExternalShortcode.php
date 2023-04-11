<?php

declare(strict_types=1);

namespace App\Support\Shortcode;

use Illuminate\Support\Facades\Cache;
use Thunder\Shortcode\EventContainer\EventContainer;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

final class ExternalShortcode
{
    private HandlerContainer $handlers;

    public function __construct()
    {
        $this->handlers = (new HandlerContainer())
            ->add('b', fn (ShortcodeInterface $s) => "<strong>" . $s->getContent() . "</strong>")
            ->add('i', fn (ShortcodeInterface $s) => "<em>" . $s->getContent() . "</em>")
            ->add('u', fn (ShortcodeInterface $s) => "<span style='text-decoration: underline'>" . $s->getContent() . "</span>")
            ->add('s', fn (ShortcodeInterface $s) => "<del>" . $s->getContent() . "</del>")
            ->add('img', fn (ShortcodeInterface $s) => "<a target='_blank' rel='noreferrer' href=" . ($s->getBbCode() ?: $s->getContent()) . ">Image</a>")
            ->add('code', fn (ShortcodeInterface $s) => "<code>" . $s->getContent() . "</code>")
            ->add('url', fn (ShortcodeInterface $s) => $this->renderUrlLink($s))
            ->add('link', fn (ShortcodeInterface $s) => $this->renderLink($s))
            ->add('spoiler', fn (ShortcodeInterface $s) => $this->renderSpoiler($s))
            ->add('ach', fn (ShortcodeInterface $s) => $this->embedAchievement((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('game', fn (ShortcodeInterface $s) => $this->embedGame((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('ticket', fn (ShortcodeInterface $s) => $this->embedTicket((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('user', fn (ShortcodeInterface $s) => $this->embedUser($s->getBbCode() ?: $s->getContent()));
    }

    public static function render(string $input): string
    {
        return (new ExternalShortcode())->parse($input);
    }

    private function parse(string $input): string
    {
        $input = preg_replace('~\[img="?([^]"]*)"?]~i', '[img="$1"]', $input);
        $input = preg_replace('~\[url="?([^]"]*)"?]~i', '[url="$1"]', $input);

        foreach ($this->handlers->getNames() as $tag) {
            $input = preg_replace("~\[/$tag]~i", "[/$tag]", $input); // closing tag
            $input = preg_replace("~\[$tag]~i", "[$tag]", $input); // opening tag
            $input = preg_replace("~\[$tag=~i", "[$tag=", $input); // opening tag with value
        }

        $events = new EventContainer();

        $processor = (new Processor(new RegularParser(), $this->handlers))
            ->withEventContainer($events);

        $html = $processor->process($input);

        // Remove any incomplete/truncated shortcodes.
        // "Here is some content [url=https://ab..." -> "Here is some content ..."
        if (!empty($html)) {
            $html = preg_replace('~\s*\[/?\w+[^]]*$~', '...', $html);
        }

        return $html;
    }

    private function renderUrlLink(ShortcodeInterface $shortcode): string
    {
        $url = $shortcode->getBbCode() ?: $shortcode->getContent();
        $prefixedUrl = $this->protocolPrefix($url);
        $content = $shortcode->getContent() ?: $prefixedUrl;

        return "<a target='_blank' rel='noreferrer' href='$prefixedUrl'>$content</a>";
    }

    private function renderLink(ShortcodeInterface $shortcode): string
    {
        $url = $shortcode->getParameter('url') ?: $shortcode->getContent();
        $prefixedUrl = $this->protocolPrefix($url);
        $content = $shortcode->getContent();

        return "<a href='$prefixedUrl'>$content</a>";
    }

    private function renderSpoiler(ShortcodeInterface $shortcode): string
    {
        $content = $shortcode->getContent();

        return "<span style='background: black; color: black;'>$content</span>";
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

    private function embedAchievement(int $id): string
    {
        $achievementData = Cache::store('array')->rememberForever('achievement:' . $id . ':card-data', fn () => GetAchievementData($id));

        if (empty($achievementData)) {
            return "<a href='/achievement/$id'>Achievement</a>";
        }

        $achievementName = $achievementData['Title'];
        $achievementPoints = $achievementData['Points'];

        return "<a href='/achievement/$id'>$achievementName ($achievementPoints)</a>";
    }

    private function embedGame(int $id): string
    {
        $data = Cache::store('array')->rememberForever('game:' . $id . ':card-data', fn () => getGameData($id));

        if (empty($data)) {
            return "<a href='/game/$id'>Game</a>";
        }

        $gameName = $data['Title'];
        $gameConsole = $data['ConsoleName'];

        return "<a href='/game/$id'>$gameName ($gameConsole)</a>";
    }

    private function embedTicket(int $id): string
    {
        return "<a href='/ticketmanager.php?i=$id'>Ticket #$id</a>";
    }

    private function embedUser(?string $username): string
    {
        if (empty($username)) {
            return '@User';
        }

        return "<a href='/user/$username'>@$username</a>";
    }
}
