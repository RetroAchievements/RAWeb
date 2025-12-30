<?php

declare(strict_types=1);

namespace Tests\Feature\Community;

use App\Models\Achievement;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\GameSetType;
use App\Support\Shortcode\Shortcode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ShortcodeTest extends TestCase
{
    use RefreshDatabase;

    public function testProtocolPrefix(): void
    {
        $this->assertSame(
            '<a href="https://retroachievements.org">https://retroachievements.org</a>',
            Shortcode::render('[url=https://retroachievements.org]')
        );

        $this->assertSame(
            '<a href="https://retroachievements.org">https://retroachievements.org</a>',
            Shortcode::render('[url=http://retroachievements.org]')
        );

        $this->assertSame(
            '<a href="https://retroachievements.org">https://retroachievements.org</a>',
            Shortcode::render('[url=retroachievements.org]')
        );

        $this->assertSame(
            '<a href="https://retroachievements.org/redirect?url=http%3A%2F%2Fexample.com">http://example.com</a>.',
            Shortcode::render('http://example.com.')
        );

        $this->assertSame(
            '<a href="https://retroachievements.org">https://retroachievements.org</a>.',
            Shortcode::render('http://retroachievements.org.')
        );

        $this->assertSame(
            '<a href="https://retroachievements.org/">https://retroachievements.org/</a>.',
            Shortcode::render('http://retroachievements.org/.')
        );

        $this->assertSame(
            '<a href="https://docs.retroachievements.org/guidelines/content/working-with-the-right-rom.html">https://docs.retroachievements.org/guidelines/content/working-with-the-right-rom.html</a>',
            Shortcode::render('docs.retroachievements.org/guidelines/content/working-with-the-right-rom.html')
        );

        $this->assertSame(
            '<a href="https://media.retroachievements.org/12345.png">https://media.retroachievements.org/12345.png</a>',
            Shortcode::render('media.retroachievements.org/12345.png')
        );
    }

    public function testUnclosedCodeTag(): void
    {
        $this->assertSame(
            '<pre class="codetags"></pre>test',
            Shortcode::render('[code]test')
        );
    }

    public function testStripAndClampQuotes(): void
    {
        $this->assertSame(
            'real stuff',
            Shortcode::stripAndClamp('[quote]hello [quote]inner[/quote] there[/quote]real stuff')
        );

        $this->assertSame(
            'BeforeBetweenAfter',
            Shortcode::stripAndClamp('Before[quote]First quote[/quote]Between[quote]Second quote[/quote]After')
        );
    }

    public function testStripAndClampImages(): void
    {
        $this->assertSame(
            '',
            Shortcode::stripAndClamp('[img=https://google.com/icon.png]')
        );

        $this->assertSame(
            '',
            Shortcode::stripAndClamp('[img]https://google.com/icon.png[/img]')
        );
    }

    public function testStripAndClampFormatters(): void
    {
        $this->assertSame(
            'Hello',
            Shortcode::stripAndClamp('[b]Hello[/b]')
        );

        $this->assertSame(
            'Hello',
            Shortcode::stripAndClamp('[i]Hello[/i]')
        );

        $this->assertSame(
            'Hello',
            Shortcode::stripAndClamp('[u]Hello[/u]')
        );

        $this->assertSame(
            'Hello',
            Shortcode::stripAndClamp('[s]Hello[/s]')
        );

        $this->assertSame(
            'Hello',
            Shortcode::stripAndClamp('[code]Hello[/code]')
        );

        $this->assertSame(
            'Hello',
            Shortcode::stripAndClamp('[url=abc.xyz]Hello[/url]')
        );

        $this->assertSame(
            'Hello',
            Shortcode::stripAndClamp('[link=abc.xyz]Hello[/link]')
        );
    }

    public function testStripAndClampSpoilers(): void
    {
        $this->assertSame(
            '{SPOILER}',
            Shortcode::stripAndClamp('[spoiler]Top Secret[/spoiler]')
        );
    }

    public function testStripAndClampTickets(): void
    {
        $this->assertSame(
            'Ticket 123',
            Shortcode::stripAndClamp('[ticket=123]')
        );
    }

    public function testStripAndClampUsers(): void
    {
        $this->assertSame(
            '@Scott',
            Shortcode::stripAndClamp('@Scott')
        );
    }

    public function testStripAndClampFragments(): void
    {
        $this->assertSame(
            'Here is some content ...',
            Shortcode::stripAndClamp('Here is some content [b')
        );

        $this->assertSame(
            'Here is some more content ...',
            Shortcode::stripAndClamp('Here is some more content [/')
        );
    }

    public function testStripAndClampCombinedWordsAndActualShortcodes(): void
    {
        $this->assertSame(
            'I uploaded an img',
            Shortcode::stripAndClamp('I uploaded an img [img=https://google.com/icon.png]')
        );

        $this->assertSame(
            'The code print("Hello") is clean',
            Shortcode::stripAndClamp('The code [code]print("Hello")[/code] is clean')
        );

        $this->assertSame(
            'u the important points',
            Shortcode::stripAndClamp('u [u]the important points[/u]')
        );
    }

    public function testNormalizeUserShortcodes(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'ID' => 123,
            'User' => 'Jamiras',
        ]);

        $rawString = 'https://retroachievements.org/user/Jamiras';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            '[user=123]',
            $normalized
        );
    }

    public function testNormalizeUserShortcodesWithSubpaths(): void
    {
        $rawString = 'https://retroachievements.org/user/Jamiras/progress';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            $rawString,
            $normalized
        );
    }

    public function testNormalizeGameShortcodes(): void
    {
        $rawString = 'https://retroachievements.org/game/1';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            '[game=1]',
            $normalized
        );
    }

    public function testNormalizeGameShortcodesWithSetParam(): void
    {
        $rawString = 'https://retroachievements.org/game/668?set=8659';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            '[game=668?set=8659]',
            $normalized
        );
    }

    public function testNormalizeHubShortcodes(): void
    {
        $rawString = 'https://retroachievements.org/hub/1';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            '[hub=1]',
            $normalized
        );
    }

    public function testNormalizeGameShortcodesWithSubpaths(): void
    {
        $rawString = 'https://retroachievements.org/game/1/hashes';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            $rawString,
            $normalized
        );
    }

    public function testNormalizeAchievementShortcodes(): void
    {
        $rawString = 'https://retroachievements.org/achievement/9';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            '[ach=9]',
            $normalized
        );
    }

    public function testNormalizeAchievementShortcodesWithSubpaths(): void
    {
        $rawString = 'https://retroachievements.org/achievement/9/subpath';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            $rawString,
            $normalized
        );
    }

    public function testNormalizeTicketShortcodes(): void
    {
        $rawString = 'https://retroachievements.org/ticket/100';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            '[ticket=100]',
            $normalized
        );
    }

    public function testNormalizeShortcodesPreservesExternalUrlTag(): void
    {
        $rawString = '[url=https://example.com/game/1][/url]';

        $normalized = normalize_shortcodes($rawString);

        $this->assertEquals(
            $rawString,
            $normalized
        );
    }

    #[DataProvider('youtubeUrlProvider')]
    public function testAutoEmbedYoutube(string $url, string $expected): void
    {
        $this->assertStringContainsString(
            $expected,
            Shortcode::render($url)
        );
    }

    public static function youtubeUrlProvider(): array
    {
        // ["given", "expected"]
        return [
            // A vanilla URL
            ['https://www.youtube.com/v/YYOKMUTTDdA', '//www.youtube-nocookie.com/embed/YYOKMUTTDdA'],

            // With explicit video ID "v"
            ['https://www.youtube.com/watch?v=W3igStIUp8Y', '//www.youtube-nocookie.com/embed/W3igStIUp8Y'],
            ['https://www.youtube.com/watch?v=SXuIABMlZKc&t=1m30s', '//www.youtube-nocookie.com/embed/SXuIABMlZKc?start=90'],
            ['https://www.youtube.com/v/xwmHOLoCyHU?t=4s', '//www.youtube-nocookie.com/embed/xwmHOLoCyHU?start=4'],

            // With shortened or embed formats
            ['https://youtu.be/seESL1hsPas', '//www.youtube-nocookie.com/embed/seESL1hsPas'],
            ['https://youtu.be/qnHoKgIh0gU?t=10s', '//www.youtube-nocookie.com/embed/qnHoKgIh0gU?start=10'],
            ['https://www.youtube.com/embed/xbf2c0JBJic', '//www.youtube-nocookie.com/embed/xbf2c0JBJic'],

            // Additional or complex query parameters
            ['https://www.youtube.com/watch?v=5IsSpAOD6K8&feature=youtu.be', '//www.youtube-nocookie.com/embed/5IsSpAOD6K8'],
            ['https://www.youtube.com/watch?v=bLaSXpqp__E&t=1h1m1s', '//www.youtube-nocookie.com/embed/bLaSXpqp__E?start=3661'],

            // Without "www" or SSL
            ['https://youtube.com/watch?v=_mSmOcmk7uQ', '//www.youtube-nocookie.com/embed/_mSmOcmk7uQ'],
            ['http://www.youtube.com/watch?v=1tNKq6KWPhY', '//www.youtube-nocookie.com/embed/1tNKq6KWPhY'],
        ];
    }

    public function testConvertToMarkdownBold(): void
    {
        $this->assertSame(
            '**Hello**',
            Shortcode::convertToMarkdown('[b]Hello[/b]')
        );
    }

    public function testConvertToMarkdownItalic(): void
    {
        $this->assertSame(
            '*Hello*',
            Shortcode::convertToMarkdown('[i]Hello[/i]')
        );
    }

    public function testConvertToMarkdownBoldAndItalic(): void
    {
        $this->assertSame(
            '***Hello***',
            Shortcode::convertToMarkdown('[b][i]Hello[/i][/b]')
        );
    }

    public function testConvertToMarkdownStrikethrough(): void
    {
        $this->assertSame(
            '~~Hello~~',
            Shortcode::convertToMarkdown('[s]Hello[/s]')
        );
    }

    public function testConvertToMarkdownUnderline(): void
    {
        $this->assertSame(
            '__Hello__',
            Shortcode::convertToMarkdown('[u]Hello[/u]')
        );
    }

    public function testConvertToMarkdownCode(): void
    {
        $this->assertSame(
            '```print("Hello")```',
            Shortcode::convertToMarkdown('[code]print("Hello")[/code]')
        );
    }

    public function testConvertToMarkdownQuote(): void
    {
        $this->assertSame(
            '> This is a quote',
            Shortcode::convertToMarkdown('[quote]This is a quote[/quote]')
        );
    }

    public function testConvertToMarkdownMultilineQuote(): void
    {
        $this->assertSame(
            "> Line 1\n> Line 2\n> Line 3",
            Shortcode::convertToMarkdown("[quote]Line 1\nLine 2\nLine 3[/quote]", 10000, preserveWhitespace: true)
        );
    }

    public function testConvertToMarkdownNestedQuotes(): void
    {
        $this->assertSame(
            '> > Inner quote Outer quote', // nested quotes get prefixed together
            Shortcode::convertToMarkdown('[quote][quote]Inner quote[/quote] Outer quote[/quote]', 10000, preserveWhitespace: true)
        );
    }

    public function testConvertToMarkdownSpoiler(): void
    {
        $this->assertSame(
            '||Top Secret||',
            Shortcode::convertToMarkdown('[spoiler]Top Secret[/spoiler]')
        );
    }

    public function testConvertToMarkdownUrlWithLabel(): void
    {
        $this->assertSame(
            '[Click here](https://example.com)',
            Shortcode::convertToMarkdown('[url=https://example.com]Click here[/url]')
        );
    }

    public function testConvertToMarkdownUrlWithLabelNoProtocol(): void
    {
        $this->assertSame(
            '[Google](https://google.com)', // protocol automatically added
            Shortcode::convertToMarkdown('[url=google.com]Google[/url]')
        );
    }

    public function testConvertToMarkdownUrlWithoutLabel(): void
    {
        $this->assertSame(
            'https://example.com',
            Shortcode::convertToMarkdown('[url]https://example.com[/url]')
        );
    }

    public function testConvertToMarkdownUrlWithoutLabelNoProtocol(): void
    {
        $this->assertSame(
            'https://example.com', // protocol automatically added
            Shortcode::convertToMarkdown('[url]example.com[/url]')
        );
    }

    public function testConvertToMarkdownImage(): void
    {
        $this->assertSame(
            'https://i.imgur.com/image.jpg',
            Shortcode::convertToMarkdown('[img]https://i.imgur.com/image.jpg[/img]')
        );
    }

    public function testConvertToMarkdownImageWithSelfClosingTag(): void
    {
        $this->assertSame(
            'https://i.imgur.com/image.jpg',
            Shortcode::convertToMarkdown('[img=https://i.imgur.com/image.jpg]')
        );
    }

    public function testConvertToMarkdownCombinedFormatting(): void
    {
        $this->assertSame(
            'This is **bold** and *italic* and ~~strikethrough~~',
            Shortcode::convertToMarkdown('This is [b]bold[/b] and [i]italic[/i] and [s]strikethrough[/s]')
        );
    }

    public function testConvertToMarkdownLengthClamping(): void
    {
        $longText = str_repeat('a', 150);
        $result = Shortcode::convertToMarkdown($longText, 100);

        $this->assertSame(103, mb_strlen($result)); // 100 chars + "..."
        $this->assertTrue(str_ends_with($result, '...'));
    }

    public function testConvertToMarkdownWhitespacePreservation(): void
    {
        $input = "Line 1\n\nLine 2\n\nLine 3";
        $result = Shortcode::convertToMarkdown($input, 10000, preserveWhitespace: true);

        $this->assertStringContainsString("\n", $result);
    }

    public function testConvertToMarkdownWhitespaceCollapsing(): void
    {
        $input = "Line 1\n\nLine 2\n\nLine 3";
        $result = Shortcode::convertToMarkdown($input, 10000, preserveWhitespace: false);

        $this->assertSame('Line 1 Line 2 Line 3', $result);
    }

    public function testConvertToMarkdownRemovesUnknownBBCode(): void
    {
        $this->assertSame(
            'Hello World',
            Shortcode::convertToMarkdown('[unknown]Hello[/unknown] [another=value]World')
        );
    }

    public function testConvertToMarkdownGameEmbed(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1, 'Name' => 'Mega Drive']);

        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => 1,
            'ConsoleID' => $system->ID,
            'Title' => 'Sonic the Hedgehog',
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[game=1]');

        // Assert
        $expectedUrl = route('game.show', 1);

        $this->assertSame(
            "[Sonic the Hedgehog (Mega Drive)]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownAchievementEmbed(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1, 'Name' => 'Mega Drive']);

        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => 1,
            'ConsoleID' => $system->ID,
            'Title' => 'Sonic the Hedgehog',
        ]);

        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create([
            'ID' => 123,
            'GameID' => $game->ID,
            'Title' => 'Ring Collector',
            'Points' => 5, // !!
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[ach=123]');

        // Assert
        $expectedUrl = route('achievement.show', 123);

        $this->assertSame(
            "[Ring Collector (5)]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownUserEmbed(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create([
            'ID' => 456,
            'User' => 'Scott',
            'display_name' => 'Scott',
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[user=456]');

        // Assert
        $expectedUrl = route('user.show', ['user' => $user]);

        $this->assertSame(
            "[Scott]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownLegacyUserEmbed(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create([
            'ID' => 456,
            'User' => 'Scott',
            'display_name' => 'Scott',
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[user=Scott]');

        // Assert
        $expectedUrl = route('user.show', ['user' => $user]);

        $this->assertSame(
            "[Scott]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownLegacyUserEmbedConflict(): void
    {
        // Arrange
        /** @var User $user */
        $user = User::factory()->create([
            'ID' => 456,
            'User' => 'Scott',
            'display_name' => 'Scott',
        ]);
        $user2 = User::factory()->create([
            'ID' => 999,
            'User' => '456',
            'display_name' => '456',
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[user=456]');

        // Assert
        $expectedUrl = route('user.show', ['user' => $user]); // ID match should be preferred over name match

        $this->assertSame(
            "[Scott]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownHubEmbed(): void
    {
        // Arrange
        /** @var GameSet $hub */
        $hub = GameSet::factory()->create([
            'id' => 1,
            'title' => '[Central]',
            'type' => GameSetType::Hub,
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[hub=1]');

        // Assert
        $expectedUrl = route('hub.show', 1);

        $this->assertSame(
            "[[Central] (Hubs)]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownEventEmbed(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create(['ID' => System::Events]);

        /** @var Game $legacyGame */
        $legacyGame = Game::factory()->create([
            'ID' => 999,
            'ConsoleID' => $system->ID,
            'Title' => 'Achievement of the Week 2025',
        ]);

        /** @var Event $event */
        $event = Event::factory()->create([
            'id' => 1,
            'legacy_game_id' => $legacyGame->id,
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[event=1]');

        // Assert
        $expectedUrl = route('event.show', 1);

        $this->assertSame(
            "[Achievement of the Week 2025 (Events)]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownTicketEmbed(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1, 'Name' => 'Mega Drive']);

        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => 1,
            'ConsoleID' => $system->ID,
            'Title' => 'Sonic the Hedgehog',
        ]);

        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create([
            'ID' => 1,
            'GameID' => $game->ID,
            'Title' => 'Test Achievement',
        ]);

        /** @var Ticket $ticket */
        $ticket = Ticket::factory()->create([
            'id' => 12345,
            'ticketable_id' => $achievement->id,
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[ticket=12345]');

        // Assert
        $expectedUrl = route('ticket.show', ['ticket' => $ticket]);

        $this->assertSame(
            "[Ticket #12345]({$expectedUrl})",
            $result
        );
    }

    public function testConvertToMarkdownDeletedUser(): void
    {
        $result = Shortcode::convertToMarkdown('[user=99999]');

        $this->assertSame('Deleted User', $result);
    }

    public function testConvertToMarkdownNonExistentResources(): void
    {
        $this->assertSame('', Shortcode::convertToMarkdown('[game=99999]'));
        $this->assertSame('', Shortcode::convertToMarkdown('[ach=99999]'));
        $this->assertSame('', Shortcode::convertToMarkdown('[hub=99999]'));
        $this->assertSame('', Shortcode::convertToMarkdown('[event=99999]'));
    }

    public function testConvertToMarkdownTicketWithoutEntity(): void
    {
        $result = Shortcode::convertToMarkdown('[ticket=99999]');

        $this->assertSame('Ticket #99999', $result);
    }

    public function testConvertToMarkdownMixedResourceEmbeds(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1, 'Name' => 'NES']);

        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => 1,
            'ConsoleID' => $system->ID,
            'Title' => 'Super Mario Bros.',
        ]);

        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create([
            'ID' => 10,
            'GameID' => $game->ID,
            'Title' => 'World 1-1',
            'Points' => 10,
        ]);

        /** @var User $user */
        $user = User::factory()->create([
            'ID' => 5,
            'User' => 'TestUser',
            'display_name' => 'TestUser',
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('[user=5] unlocked [ach=10] in [game=1]!'); // don't forget, we have to use the user ID here

        // Assert
        $gameUrl = route('game.show', 1);
        $achUrl = route('achievement.show', 10);
        $userUrl = route('user.show', ['user' => $user]);

        $this->assertSame(
            "[TestUser]({$userUrl}) unlocked [World 1-1 (10)]({$achUrl}) in [Super Mario Bros. (NES)]({$gameUrl})!",
            $result
        );
    }

    public function testConvertToMarkdownFormattingAroundResourceEmbeds(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1, 'Name' => 'SNES']);

        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => 1,
            'ConsoleID' => $system->ID,
            'Title' => 'Chrono Trigger',
        ]);

        // Act
        $result = Shortcode::convertToMarkdown('Check out **[game=1]** - it\'s [i]amazing[/i]!');

        // Assert
        $gameUrl = route('game.show', 1);

        $this->assertSame(
            "Check out **[Chrono Trigger (SNES)]({$gameUrl})** - it's *amazing*!",
            $result
        );
    }

    public function testConvertToMarkdownNestedFormatting(): void
    {
        $this->assertSame(
            '***bold and italic***',
            Shortcode::convertToMarkdown('[b][i]bold and italic[/i][/b]')
        );
    }

    public function testConvertToMarkdownMultilineCode(): void
    {
        $input = "[code]function test() {\n  return true;\n}[/code]";
        $result = Shortcode::convertToMarkdown($input, 10000, preserveWhitespace: true);

        $this->assertSame("```function test() {\n  return true;\n}```", $result);
    }

    public function testConvertToMarkdownComplexRealisticMessage(): void
    {
        // Arrange
        /** @var System $system */
        $system = System::factory()->create(['ID' => 1, 'Name' => 'PS1']);

        /** @var Game $game */
        $game = Game::factory()->create([
            'ID' => 100,
            'ConsoleID' => $system->ID,
            'Title' => 'Final Fantasy VII',
        ]);

        /** @var Achievement $achievement */
        $achievement = Achievement::factory()->create([
            'ID' => 200,
            'GameID' => $game->ID,
            'Title' => 'Omnislash',
            'Points' => 25,
        ]);

        $input = "Hey everyone! I just unlocked [ach=200] in [game=100]!\n\n[quote]This was **so hard**[/quote]\n\nThe trick is to [spoiler]use Knights of the Round[/spoiler]. Check out [url=https://example.com]this guide[/url] for more tips!";

        // Act
        $result = Shortcode::convertToMarkdown($input, 10000, preserveWhitespace: true);

        // Assert
        $gameUrl = route('game.show', 100);
        $achUrl = route('achievement.show', 200);

        $this->assertStringContainsString("[Omnislash (25)]({$achUrl})", $result);
        $this->assertStringContainsString("[Final Fantasy VII (PS1)]({$gameUrl})", $result);
        $this->assertStringContainsString('> This was **so hard**', $result);
        $this->assertStringContainsString('||use Knights of the Round||', $result);
        $this->assertStringContainsString('[this guide](https://example.com)', $result);
    }

    public function testConvertToMarkdownMismatchedTags(): void
    {
        $result = Shortcode::convertToMarkdown('[b]text[/i]'); // these opening and closing tags are mismatched

        $this->assertSame('text', $result); // both tags are stripped since they don't match
    }
}
