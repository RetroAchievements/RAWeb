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
            '<a href="http://example.com">http://example.com</a>.',
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
    }

    public function testUnclosedCodeTag(): void
    {
        $this->assertSame(
            '<pre class="codetags"></pre>test',
            Shortcode::render('[code]test')
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

    public function youtubeUrlProvider(): array
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
