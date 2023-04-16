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
}
