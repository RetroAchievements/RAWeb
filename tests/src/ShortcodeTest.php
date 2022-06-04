<?php

namespace Test;

use PHPUnit\Framework\TestCase;
use RA\Shortcode;

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
            '<a href="http://example.com" target="_blank" rel="noopener">http://example.com</a>.',
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
}
