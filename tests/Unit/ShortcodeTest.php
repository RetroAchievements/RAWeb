<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Shortcode\Shortcode;
use Tests\TestCase;

final class ShortcodeTest extends TestCase
{
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
            '<a href="https://docs.retroachievements.org/Working-with-the-Right-ROM">https://docs.retroachievements.org/Working-with-the-Right-ROM</a>',
            Shortcode::render('docs.retroachievements.org/Working-with-the-Right-ROM')
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

    /**
     * @dataProvider youtubeUrlProvider
     */
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
}
