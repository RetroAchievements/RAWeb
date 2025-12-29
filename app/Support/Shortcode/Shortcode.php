<?php

declare(strict_types=1);

namespace App\Support\Shortcode;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\ResolveBackingGameForAchievementSetAction;
use App\Platform\Enums\GameSetType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
    private array $usersCache = [];

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
            ->add('quote', fn (ShortcodeInterface $s) => $this->renderQuote($s))
            ->add('spoiler', fn (ShortcodeInterface $s) => $this->renderSpoiler($s))
            ->add('ach', fn (ShortcodeInterface $s) => $this->embedAchievement((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('game', fn (ShortcodeInterface $s) => $this->embedGame((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('hub', fn (ShortcodeInterface $s) => $this->embedHub((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('event', fn (ShortcodeInterface $s) => $this->embedEvent((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('ticket', fn (ShortcodeInterface $s) => $this->embedTicket((int) ($s->getBbCode() ?: $s->getContent())))
            ->add('user', fn (ShortcodeInterface $s) => $this->embedUser($s->getBbCode() ?: $s->getContent()));
    }

    public static function render(string $input, array $options = []): string
    {
        return (new Shortcode())->parse($input, $options);
    }

    public static function convertLegacyGameHubShortcodesToHubShortcodes(string $input): string
    {
        // Extract all game IDs from the shortcodes. We want to make a single query
        // to avoid unnecessary database load.
        preg_match_all('/\[game=(\d+)\]/', $input, $matches);
        $gameIds = $matches[1];

        if (empty($gameIds)) {
            return $input;
        }

        // Find all legacy hubs (games with ConsoleID 100) and their corresponding
        // game_sets entries in a single query.
        $hubMap = Game::query()
            ->join('game_sets', 'GameData.ID', '=', 'game_sets.game_id')
            ->where(DB::raw('GameData.ConsoleID'), System::Hubs)
            ->where('game_sets.type', GameSetType::Hub)
            ->whereIn('GameData.ID', $gameIds)
            ->select('GameData.ID as game_id', 'game_sets.id as hub_id')
            ->get()
            ->pluck('hub_id', 'game_id');

        // Replace each legacy game hub shortcode with the corresponding modern hub
        // shortcode if it maps to a modern hub, otherwise leave it unchanged.
        return preg_replace_callback('/\[game=(\d+)\]/', function ($matches) use ($hubMap) {
            $gameId = $matches[1];
            $hubId = $hubMap->get($gameId);

            return $hubId ? "[hub={$hubId}]" : $matches[0];
        }, $input);
    }

    public static function convertUserShortcodesToUseIds(string $input): string
    {
        // Extract all usernames from the payload. We want to make a single
        // query so someone doesn't inadvertently slam the database.
        preg_match_all('/\[user=(.*?)\]/', $input, $matches);
        $usernames = $matches[1];

        if (empty($usernames)) {
            return $input;
        }

        // Normalize usernames to lowercase to ensure matching is not case-sensitive.
        $normalizedUsernames = array_map('strtolower', $usernames);

        // Fetch all users by username in a single query.
        $users = User::withTrashed()
            ->where(function ($query) use ($normalizedUsernames) {
                $query->whereIn(DB::raw('LOWER(username)'), $normalizedUsernames)
                    ->orWhereIn(DB::raw('LOWER(display_name)'), $normalizedUsernames);
            })
            ->get(['id', 'username', 'display_name']);

        // Create a lookup map that includes both username and display name as keys.
        $userMap = collect();
        foreach ($users as $user) {
            if ($user->username) {
                $userMap[strtolower($user->username)] = $user;
            }
            if ($user->display_name) {
                $userMap[strtolower($user->display_name)] = $user;
            }
        }

        // Replace each username with the corresponding user ID.
        return preg_replace_callback('/\[user=(.*?)\]/', function ($matches) use ($userMap) {
            $username = strtolower($matches[1]);
            $user = $userMap->get($username);

            return $user ? "[user={$user->id}]" : $matches[0];
        }, $input);
    }

    public static function convertGameSetShortcodesToBackingGame(string $input): string
    {
        // Extract all [game=X?set=Y] or [game=X set=Y] patterns.
        preg_match_all('/\[game=(\d+)(?:\?|\s)set=(\d+)\]/i', $input, $matches, PREG_SET_ORDER);
        if (empty($matches)) {
            return $input;
        }

        // Collect all unique set IDs for bulk processing.
        $setIds = array_unique(array_column($matches, 2));

        // Now, resolve backing games for all sets.
        // Build a map of achievement set ID -> backing game ID.
        $setToBackingGameMap = [];
        $resolveAction = (new ResolveBackingGameForAchievementSetAction());

        foreach ($setIds as $setId) {
            $backingGameId = $resolveAction->execute((int) $setId);
            if ($backingGameId) {
                $setToBackingGameMap[$setId] = $backingGameId;
            }
        }

        // Replace each shortcode with the backing game ID.
        // If no backing game is found, fall back to the original game ID.
        return preg_replace_callback(
            '/\[game=(\d+)(?:\?|\s)set=(\d+)\]/i',
            function ($match) use ($setToBackingGameMap) {
                $originalGameId = $match[1];
                $setId = $match[2];

                $gameId = $setToBackingGameMap[$setId] ?? $originalGameId;

                return "[game={$gameId}]";
            },
            $input
        );
    }

    public static function extractShortcodeIds(string $input): array
    {
        if (!str_contains($input, '[')) {
            return [
                'achievementIds' => [],
                'gameIds' => [],
                'hubIds' => [],
                'eventIds' => [],
                'ticketIds' => [],
                'usernames' => [],
            ];
        }

        // Extract achievement IDs from [ach=X] shortcodes.
        preg_match_all('/\[ach=(\d+)\]/i', $input, $achievementMatches);
        $achievementIds = array_unique(array_map('intval', $achievementMatches[1]));

        // Extract game IDs from [game=X] shortcodes (but not [game=X set=Y]).
        preg_match_all('/\[game=(\d+)(?!\?|\ set=)\]/i', $input, $gameMatches);
        $gameIds = array_unique(array_map('intval', $gameMatches[1]));

        // Extract hub IDs from [hub=X] shortcodes.
        preg_match_all('/\[hub=(\d+)\]/i', $input, $hubMatches);
        $hubIds = array_unique(array_map('intval', $hubMatches[1]));

        // Extract event IDs from [event=X] shortcodes.
        preg_match_all('/\[event=(\d+)\]/i', $input, $eventMatches);
        $eventIds = array_unique(array_map('intval', $eventMatches[1]));

        // Extract ticket IDs from [ticket=X] shortcodes.
        preg_match_all('/\[ticket=(\d+)\]/i', $input, $ticketMatches);
        $ticketIds = array_unique(array_map('intval', $ticketMatches[1]));

        // Extract usernames from [user=X] shortcodes.
        preg_match_all('/\[user=([^\]]+)\]/i', $input, $userMatches);
        $usernames = array_unique($userMatches[1]);

        return [
            'achievementIds' => array_values($achievementIds),
            'gameIds' => array_values($gameIds),
            'hubIds' => array_values($hubIds),
            'eventIds' => array_values($eventIds),
            'ticketIds' => array_values($ticketIds),
            'usernames' => $usernames,
        ];
    }

    public static function fetchRecords(array $shortcodeIds): array
    {
        $results = [];

        foreach ($shortcodeIds as $key => $ids) {
            $ids = array_unique($ids);
            if (empty($ids)) {
                continue;
            }

            switch ($key) {
                case 'achievementIds':
                    $results[$key] = Achievement::whereIn('ID', $ids)
                        ->get()->mapWithKeys(function ($achievement) {
                            return [$achievement->ID => $achievement];
                        });
                    break;

                case 'gameIds':
                    $results[$key] = Game::with('system')->whereIn('ID', $ids)
                        ->get()->mapWithKeys(function ($game) {
                            return [$game->ID => $game];
                        });
                    break;

                case 'hubIds':
                    $results[$key] = GameSet::where('type', GameSetType::Hub)->whereIn('id', $ids)
                        ->get()->mapWithKeys(function ($hub) {
                            return [$hub->id => $hub];
                        });
                    break;

                case 'eventIds':
                    $results[$key] = Event::with('legacyGame')->whereIn('id', $ids)
                        ->get()->mapWithKeys(function ($event) {
                            return [$event->id => $event];
                        });
                    break;

                case 'ticketIds':
                    $results[$key] = Ticket::whereIn('ID', $ids)
                        ->get()->mapWithKeys(function ($ticket) {
                            return [$ticket->ID => $ticket];
                        });
                    break;

                case 'usernames':
                    $userIds = array_filter($ids, fn ($value) => ctype_digit($value));
                    $results[$key] = User::withTrashed()->whereIn('id', $userIds)
                        ->get()->mapWithKeys(function ($user) {
                            return [$user->id => $user];
                        });
                    User::withTrashed()
                        ->where(function ($query) use ($ids) {
                            $query->whereIn('username', $ids)
                                ->orWhereIn('display_name', $ids);
                        })
                        ->get()->map(function ($user) use ($key, &$results) {
                            $results[$key][$user->display_name] ??= $user;
                            $results[$key][$user->username] ??= $user;
                        });

                    break;
            }
        }

        return $results;
    }

    public static function stripAndClamp(
        string $input,
        int $previewLength = 100,
        bool $preserveWhitespace = false,
        ?array $shortcodeRecords = null,
    ): string {
        if (!$preserveWhitespace) {
            $input = trim($input);
        }

        if (!$shortcodeRecords) {
            $shortcodeIds = Shortcode::extractShortcodeIds($input);
            $shortcodeRecords = Shortcode::fetchRecords($shortcodeIds);
        }

        // Inject game and achievement data for shortcodes.
        // This is more desirable than showing "Game 123" or "Achievement 123".
        $injectionShortcodes = [
            // "[game=1]" --> "Sonic the Hedgehog (Mega Drive)"
            '~\[game=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $gameId = (int) $matches[1];
                $game = $shortcodeRecords['gameIds'][$gameId] ?? null;
                if ($game) {
                    return "{$game->title} ({$game->system->name})";
                }

                return "";
            },

            // "[hub=1]" --> "[Central]"
            '~\[hub=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $hubId = (int) $matches[1];
                $hubData = $shortcodeRecords['hubIds'][$hubId] ?? null;
                if ($hubData) {
                    return "{$hubData->title} (Hubs)";
                }

                return "";
            },

            // "[ach=1]" --> "Ring Collector (5)"
            '~\[ach=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $achievementId = (int) $matches[1];
                $achievement = $shortcodeRecords['achievementIds'][$achievementId] ?? null;
                if ($achievement) {
                    return "{$achievement->title} ({$achievement->points})";
                }

                return "";
            },

            // "[user=1]" -->  "@Scott", "[user=Scott]"  -->  "@Scott"
            '~\[user=([^\]]+)]~i' => function ($matches) use ($shortcodeRecords) {
                $userId = $matches[1];
                $user = $shortcodeRecords['usernames'][$userId] ?? null;
                if ($user) {
                    return "@{$user->display_name}";
                }

                return "@Deleted User";
            },
        ];

        if (str_contains($input, '[') && !empty($shortcodeRecords)) {
            foreach ($injectionShortcodes as $pattern => $callback) {
                $input = preg_replace_callback($pattern, $callback, $input);
            }
        }

        // Remove all quoted content, including nested quotes.
        // Keep replacing nested quoted content until no more is found.
        while (preg_match('~\[quote\].*\[/quote\]~is', $input)) {
            $input = preg_replace(
                '~\[quote\]((?:[^[]|\[(?!/?quote])|(?R))*)\[/quote\]~is',
                '',
                $input
            );
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

            // Fragments: opening tags without closing tags.
            '~\[(b|i|u|s|img|code|url|link|quote|spoiler|ach|game|ticket|user)\b[^\]]*?\]~i' => '',
            '~\[(b|i|u|s|img|code|url|link|quote|spoiler|ach|game|ticket|user)\b[^\]]*?$~i' => '...',

            // Fragments: closing tags without opening tags.
            '~\[/?(b|i|u|s|img|code|url|link|quote|spoiler|ach|game|ticket|user)\]~i' => '',
        ];

        foreach ($stripPatterns as $stripPattern => $replacement) {
            $input = preg_replace($stripPattern, $replacement, $input);
        }

        // For cleaner previews, strip all unnecessary whitespace.
        if (!$preserveWhitespace) {
            $input = trim(preg_replace('/\s+/', ' ', $input));
        }

        // As a failsafe, check the last 6 characters for any fragmented shortcodes and purge them.
        $lastSixChars = substr($input, -6);
        if (preg_match('/\[[^\]]{0,5}$/', $lastSixChars)) {
            $input = preg_replace('/\[[^\]]{0,5}$/', '...', $input);
        }

        // If the string is over the preview length, clamp it and add "..."
        // This can happen as a result of the replacement from above.
        if (mb_strlen($input) > $previewLength) {
            $input = mb_substr($input, 0, $previewLength) . '...';
        }

        // Handle edge case: if the input is just ellipses, show nothing.
        if ($input === "...") {
            $input = "";
        }

        return $input;
    }

    public static function convertToMarkdown(
        string $input,
        int $maxLength = 10000,
        bool $preserveWhitespace = false,
        ?array $shortcodeRecords = null,
    ): string {
        if (!$preserveWhitespace) {
            $input = trim($input);
        }

        if (!$shortcodeRecords) {
            $shortcodeIds = Shortcode::extractShortcodeIds($input);
            $shortcodeRecords = Shortcode::fetchRecords($shortcodeIds);
        }

        // Inject game, achievement, user, hub, event, and ticket data as Markdown links.
        $injectionShortcodes = [
            // "[game=1]" --> "[Sonic the Hedgehog (Mega Drive)](https://retroachievements.org/game/1)"
            '~\[game=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $gameId = (int) $matches[1];
                $game = $shortcodeRecords['gameIds'][$gameId] ?? null;
                if ($game) {
                    $url = route('game.show', $matches[1]);

                    return "[{$game->title} ({$game->system->name})]({$url})";
                }

                return "";
            },

            // "[hub=1]" --> "[[Central] (Hubs)](https://retroachievements.org/hub/1)"
            '~\[hub=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $hubId = (int) $matches[1];
                $hubData = $shortcodeRecords['hubIds'][$hubId] ?? null;
                if ($hubData) {
                    $url = route('hub.show', $hubId);

                    return "[{$hubData->title} (Hubs)]({$url})";
                }

                return "";
            },

            // "[ach=1]" --> "[Ring Collector (5)](https://retroachievements.org/achievement/1)"
            '~\[ach=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $achievementId = (int) $matches[1];
                $achievement = $shortcodeRecords['achievementIds'][$achievementId] ?? null;
                if ($achievement) {
                    $url = route('achievement.show', $matches[1]);

                    return "[{$achievement->title} ({$achievement->points})]({$url})";
                }

                return "";
            },

            // "[event=1]" --> "[Event Name (Events)](https://retroachievements.org/event/1)"
            '~\[event=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $eventId = (int) $matches[1];
                $eventData = $shortcodeRecords['eventIds'][$eventId] ?? null;
                if ($eventData) {
                    $url = route('event.show', $eventId);

                    return "[{$eventData->legacyGame->title} (Events)]({$url})";
                }

                return "";
            },

            // "[user=1]" --> "[Scott](https://retroachievements.org/user/Scott)"
            '~\[user=([^\]]+)]~i' => function ($matches) use ($shortcodeRecords) {
                $userId = $matches[1];
                $user = $shortcodeRecords['usernames'][$userId] ?? null;
                if ($user) {
                    $url = route('user.show', ['user' => $user]);

                    return "[{$user->display_name}]({$url})";
                }

                return "Deleted User";
            },

            // "[ticket=123]" --> "[Ticket #123](https://retroachievements.org/ticket/123)"
            '~\[ticket=(\d+)]~i' => function ($matches) use ($shortcodeRecords) {
                $ticketId = (int) $matches[1];
                $ticket = $shortcodeRecords['ticketIds'][$ticketId] ?? null;
                if ($ticket) {
                    $url = route('ticket.show', ['ticket' => $ticket]);

                    return "[Ticket #{$ticketId}]({$url})";
                }

                return "Ticket #{$ticketId}";
            },
        ];

        if (str_contains($input, '[') && !empty($shortcodeRecords)) {
            foreach ($injectionShortcodes as $pattern => $callback) {
                $input = preg_replace_callback($pattern, $callback, $input);
            }
        }

        // Convert [quote] blocks to Markdown blockquotes.
        // Handle nested quotes by processing innermost quotes first.
        while (preg_match('~\[quote\](.*?)\[/quote\]~is', $input)) {
            $input = preg_replace_callback(
                '~\[quote\](.*?)\[/quote\]~is',
                function ($matches) {
                    $content = trim($matches[1]);

                    // Prefix each line with "> " to create a Markdown blockquote.
                    $lines = explode("\n", $content);
                    $quotedLines = array_map(fn ($line) => '> ' . trim($line), $lines);

                    return implode("\n", $quotedLines);
                },
                $input
            );
        }

        // Convert [url] tags to Markdown links.
        // It's important to always be sure a protocol prefix is set,
        // otherwise Discord won't render the link and will render plain text.
        $input = preg_replace_callback(
            '~\[url=(.*?)\](.*?)\[/url\]~is',
            function ($matches) {
                $url = $matches[1];

                // Add protocol prefix if it's missing.
                $scheme = parse_url($url, PHP_URL_SCHEME);
                $host = parse_url($url, PHP_URL_HOST);

                if (empty($scheme)) {
                    $url = 'https://' . ltrim($url, '/');
                } elseif ($scheme === 'http' && str_ends_with($host, 'retroachievements.org')) {
                    $url = str_replace('http://', 'https://', $url);
                }

                return "[{$matches[2]}]({$url})";
            },
            $input
        );

        // Convert [url]link[/url] to just the link.
        $input = preg_replace_callback(
            '~\[url\](.*?)\[/url\]~is',
            function ($matches) {
                $url = $matches[1];

                // Add protocol prefix if it's missing.
                $scheme = parse_url($url, PHP_URL_SCHEME);
                $host = parse_url($url, PHP_URL_HOST);

                if (empty($scheme)) {
                    $url = 'https://' . ltrim($url, '/');
                } elseif ($scheme === 'http' && str_ends_with($host, 'retroachievements.org')) {
                    $url = str_replace('http://', 'https://', $url);
                }

                return $url;
            },
            $input
        );

        // Convert non-custom BBCode to Markdown.
        $conversionPatterns = [
            // "[b]text[/b]" --> "**text**"
            '~\[b\](.*?)\[/b\]~is' => '**$1**',

            // "[i]text[/i]" --> "*text*"
            '~\[i\](.*?)\[/i\]~is' => '*$1*',

            // "[s]text[/s]" --> "~~text~~"
            '~\[s\](.*?)\[/s\]~is' => '~~$1~~',

            // "[u]text[/u]" --> "__text__"
            '~\[u\](.*?)\[/u\]~is' => '__$1__',

            // "[code]code[/code]" --> "```code```"
            '~\[code\](.*?)\[/code\]~is' => '```$1```',

            // "[spoiler]text[/spoiler]" --> "||text||"
            '~\[spoiler\](.*?)\[/spoiler\]~is' => '||$1||',

            // "[img]url[/img]" --> "url"
            '~\[img\](.*?)\[/img\]~i' => '$1',
            '~\[img=(.*?)\]~i' => '$1',
        ];

        foreach ($conversionPatterns as $pattern => $replacement) {
            $input = preg_replace($pattern, $replacement, $input);
        }

        // Strip any remaining unconverted BBCode fragments (but not Markdown links).
        // Match [tag] or [tag=value] or [/tag], but only simple tag names without spaces.
        // Don't match if it's part of a Markdown link [text](url) structure.
        $input = preg_replace('~\[/?[a-z]+(?:=[^\]\s]*?)?\](?!\s*\([^\)]+\))~i', '', $input);

        // Clean up whitespace if needed.
        if (!$preserveWhitespace) {
            $input = trim(preg_replace('/\s+/', ' ', $input));
        }

        // Clamp to max length.
        if (mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength) . '...';
        }

        // If for some reason the input is just ellipses, show nothing.
        if ($input === "...") {
            $input = "";
        }

        return $input;
    }

    private function prefetchUsers(string $input): void
    {
        // Extract all user IDs from the input. We want to fetch them all
        // in a single burst to avoid an N+1 query problem.
        preg_match_all('/\[user=(\d+)\]/', $input, $matches);
        $userIds = array_map('intval', $matches[1]);

        if (!empty($userIds)) {
            $users = User::withTrashed()->whereIn('id', $userIds)->get()->keyBy('id');
            $this->usersCache = $users->all();
        }
    }

    private function parse(string $input, array $options = []): string
    {
        // Resolve game set shortcodes to backing games before render-time.
        // This allows [game=1?set=9534] to display the correct backing game.
        $input = self::convertGameSetShortcodesToBackingGame($input);

        $this->prefetchUsers($input);

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
        // Determine the URL from the shortcode's BBCode or content.
        $url = $shortcode->getBbCode() ?: $shortcode->getContent();

        // Ensure the correct protocol prefix (http/https) is being used.
        $prefixedUrl = $this->protocolPrefix($url);

        // Is this an external link? If so, it needs to go through the redirect gateway.
        $finalUrl = $prefixedUrl;
        if (strpos($prefixedUrl, 'retroachievements.org') === false) {
            $finalUrl = route('redirect', ['url' => $prefixedUrl]);
        }

        // Finally, build and return the needed anchor tag.
        return sprintf(
            '<a href="%s">%s</a>',
            $finalUrl,
            $shortcode->getContent() ?: $this->protocolPrefix($shortcode->getBbCode())
        );
    }

    private function renderLink(ShortcodeInterface $shortcode): string
    {
        // Determine the URL from the shortcode's 'url' parameter or content.
        $url = $shortcode->getParameter('url') ?: $shortcode->getContent();

        if (empty($url)) {
            return '[broken link]';
        }

        // Ensure the correct protocol prefix (http/https) is being used.
        $prefixedUrl = $this->protocolPrefix($url);

        // Is this an external link? If so, it needs to go through the redirect gateway.
        $finalUrl = $prefixedUrl;
        if (strpos($prefixedUrl, 'retroachievements.org') === false) {
            $finalUrl = route('redirect', ['url' => $prefixedUrl]);
        }

        // Finally, build and return the needed anchor tag.
        // HTML escape the content to prevent XSS vulnerabilities.
        return sprintf(
            '<a href="%s">%s</a>',
            $finalUrl,
            htmlspecialchars($shortcode->getContent(), ENT_QUOTES, 'UTF-8')
        );
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

    private function renderQuote(ShortcodeInterface $shortcode): string
    {
        $content = trim($shortcode->getContent() ?? '');

        // $content will contain a leading and trailing <br> if the [quote] tag is on a separate line.
        //
        //   [quote]
        //   This is a quote.
        //   [/quote]
        //
        // We don't want that extra whitespace in the output, so strip them. Leave any intermediary <br>s.
        if (str_starts_with($content, '<br>')) {
            $content = substr($content, 4);
        }
        if (str_ends_with($content, '<br>')) {
            $content = substr($content, 0, -4);
        }

        return '<span class="quotedtext">' . $content . '</span>';
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

        if ($data['ConsoleID'] === System::Events) {
            $achievement = Achievement::find($id);
            if ($achievement->eventData?->source_achievement_id
                && $achievement->eventData->active_from > Carbon::now()) {
                $data['Title'] = $data['AchievementTitle'] = 'Upcoming Challenge';
                $data['Description'] = '?????';
                $data['BadgeName'] = '00000';
            }
        }

        return achievementAvatar($data, iconSize: 24);
    }

    private function embedGame(int $id): string
    {
        $data = Cache::store('array')->rememberForever('game:' . $id . ':game-data', fn () => getGameData($id));

        if (empty($data)) {
            return '';
        }

        return str_replace("\n", '', gameAvatar($data, iconSize: 24));
    }

    private function embedHub(int $id): string
    {
        $data = Cache::store('array')->rememberForever('hub:' . $id . ':hub-data', function () use ($id) {
            $hubGameSet = GameSet::where('type', GameSetType::Hub)
                ->where('id', $id)
                ->first();

            if (!$hubGameSet) {
                return [];
            }

            return [
                'ID' => $hubGameSet->id,
                'Title' => $hubGameSet->title,
                'ConsoleName' => "Hubs",
                'ImageIcon' => $hubGameSet->image_asset_path,
            ];
        });

        if (empty($data)) {
            return '';
        }

        return str_replace("\n", '', gameAvatar($data, iconSize: 24, isHub: true));
    }

    private function embedEvent(int $id): string
    {
        $data = Cache::store('array')->rememberForever('event:' . $id . ':event-data', fn () => $this->getEventData($id));

        if (empty($data)) {
            return '';
        }

        return str_replace("\n", '', gameAvatar($data, iconSize: 24));
    }

    private function embedTicket(int $id): string
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return '';
        }

        return ticketAvatar($ticket, iconSize: 24);
    }

    private function embedUser(?string $userId): string
    {
        if (!$userId) {
            return '';
        }

        if (!isset($this->usersCache[$userId])) {
            return userAvatar($userId, icon: false);
        }

        $user = $this->usersCache[$userId];
        if (!$user) {
            return userAvatar($userId, icon: false);
        }

        return userAvatar($user, icon: false);
    }

    private function autolinkRetroachievementsUrls(string $text): string
    {
        // see https://stackoverflow.com/a/2271552/580651:
        // [...] it's probably safe to assume a semicolon at the end of a URL is meant as sentence punctuation.
        // The same goes for other sentence-punctuation characters like periods, question marks, quotes, etc..
        // lookahead: (?<![!,.?;:"\'()])
        return (string) preg_replace_callback(
            '~
                (?:https?://)?             # Optional scheme. Either http or https.
                ((?:[\w-]+\.)?)            # Optional subdomains, include the period in the group.
                retroachievements\.org     # Host + TLD.
                (?:                        # Optional path
                  /([\w!#$%&\'()*+,./:;=?@\[\]-]*)    # Capture path in group 2 if any.
                )?
                (?<![!,.?;:"\'()])         # Do not end with punctuation.
                (?!                        # Assert URL is not pre-linked.
                  [^<>]*>                  # Either inside a start tag,
                  | [^<>]*</a>             # or inside an end tag.
                )                          # End negative lookahead assertion.
            ~ix',
            function ($matches) {
                $subdomain = $matches[1];
                $path = isset($matches[2]) ? '/' . $matches[2] : '';

                return '<a href="https://' . $subdomain . 'retroachievements.org' . $path . '">https://' . $subdomain . 'retroachievements.org' . $path . '</a>';
            },
            $text
        );
    }

    private function autolinkUrls(string $text): string
    {
        // see https://stackoverflow.com/a/2271552/580651:
        // [...] it's probably safe to assume a semicolon at the end of a URL is meant as sentence punctuation.
        // The same goes for other sentence-punctuation characters like periods, question marks, quotes, etc..
        // lookahead: (?<![!,.?;:"\'()-])
        return preg_replace_callback(
            '~
            (https?://[\w!#$%&\'()*+,./:;=?@\[\]-]+(?<![!,.?;:"\'()]))
            (?!                 # Assert URL is not pre-linked.
              [^<>]*>           # Either inside a start tag,
              | [^<>]*</a>      # End recognized pre-linked alts.
            )                   # End negative lookahead assertion.
            ~ix',
            function ($matches) {
                // If we use Laravel's `route()` helper, this will point to "localhost" in tests.
                $redirectBaseUrl = "https://retroachievements.org/redirect?url=";
                $redirectUrl = $redirectBaseUrl . urlencode($matches[1]);

                return '<a href="' . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</a>';
            },
            $text
        );
    }

    private function embedVideo(string $videoUrl): string
    {
        return '<div class="embed-responsive embed-responsive-16by9"><iframe class="embed-responsive-item" src="' . $videoUrl . '" allowfullscreen></iframe></div>';
    }

    private function getEventData(int $id): array
    {
        $event = Event::find($id);

        if (!$event) {
            return [];
        }

        return [
            'ID' => $event->legacyGame->id,
            'EventID' => $event->id,
            'Title' => $event->legacyGame->title,
            'ConsoleName' => "Events",
            'ImageIcon' => $event->image_asset_path,
        ];
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

                // Parse the query parameters and populate them into $query.
                parse_str(ltrim($matches[2], '?'), $query);

                // Check if the "t" parameter (timestamp) is present.
                if (isset($query['t'])) {
                    // "t" has to be converted to a time compatible with youtube-nocookie.com embeds.
                    $query['start'] = $this->convertYouTubeTime($query['t']);

                    // Once converted, remove the "t" parameter so we don't accidentally duplicate it.
                    unset($query['t']);
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
            '~(?:https?://)?clips.twitch.tv/([a-zA-Z0-9-_]+)~i',
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
