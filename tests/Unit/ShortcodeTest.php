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
            '<Spoiler>',
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
}
