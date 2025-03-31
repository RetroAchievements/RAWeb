<?php

declare(strict_types=1);

namespace Tests\Feature\Community\Actions;

use App\Community\Actions\FormatLegacyCommentPayloadAction;
use Tests\TestCase;

class FormatLegacyCommentPayloadActionTest extends TestCase
{
    private FormatLegacyCommentPayloadAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new FormatLegacyCommentPayloadAction();
    }

    public function testItFormatsBasicText(): void
    {
        $input = 'Hello World';
        $expected = 'Hello World';

        $this->assertEquals($expected, $this->action->execute($input, isTicketComment: false));
    }

    public function testItEscapesHtmlContent(): void
    {
        $input = '<script>alert("xss")</script><b>bold</b>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;&lt;b&gt;bold&lt;/b&gt;';

        $this->assertEquals($expected, $this->action->execute($input, isTicketComment: true));
    }

    public function testGivenTicketCommentItAcceptsRenderingGameHashLinks(): void
    {
        $link = '<a href="/game/253/hashes" title="Test Game">hash123</a>';
        $input = "Check this hash: {$link}";
        $expected = "Check this hash: {$link}";

        $this->assertEquals($expected, $this->action->execute($input, isTicketComment: true));
    }

    public function testGivenNotATicketCommentItDoesNotRenderGameHashLinks(): void
    {
        $link = '<a href="/game/253/hashes" title="Test Game">hash123</a>';
        $input = "Check this hash: {$link}";
        $expected = "Check this hash: &lt;a href=&quot;/game/253/hashes&quot; title=&quot;Test Game&quot;&gt;hash123&lt;/a&gt;";

        $this->assertEquals($expected, $this->action->execute($input, isTicketComment: false));
    }

    public function testItNormalizesMultipleLineBreaks(): void
    {
        $input = "Line 1\n\n\n\nLine 2";
        $expected = "Line 1<br /><br />\nLine 2";

        $this->assertEquals($expected, $this->action->execute($input, isTicketComment: false));
    }

    public function testItPreservesSingleLineBreaksBetweenListItems(): void
    {
        $input = "Line 1\n- Item 1\n- Item 2\n- Item 3";
        $expected = "Line 1<br />- Item 1<br />- Item 2<br />- Item 3";

        $this->assertEquals($expected, $this->action->execute($input, isTicketComment: false));
    }

    public function testItPreservesSingleLineBreaksBetweenListItems2(): void
    {
        $input = "Line 1<br />- Item 1<br />- Item 2<br />- Item 3";
        $expected = "Line 1<br />- Item 1<br />- Item 2<br />- Item 3";

        $this->assertEquals($expected, $this->action->execute($input, isTicketComment: false));
    }

    public function testItHandlesAttemptedMaliciousInput(): void
    {
        $cases = [
            // Script injection.
            '<script>alert(1)</script>' => '&lt;script&gt;alert(1)&lt;/script&gt;',

            // Non-game hash links.
            '<a href="/malicious/path">click me</a>' => '&lt;a href=&quot;/malicious/path&quot;&gt;click me&lt;/a&gt;',

            // Invalid game hash paths.
            '<a href="/game/index.php">hack</a>' => '&lt;a href=&quot;/game/index.php&quot;&gt;hack&lt;/a&gt;',
            '<a href="/game/../../etc/passwd/hashes">hack</a>' => '&lt;a href=&quot;/game/../../etc/passwd/hashes&quot;&gt;hack&lt;/a&gt;',

            // XSS in link text.
            '<a href="/game/123/hashes"><script>alert(1)</script></a>' => '&lt;a href=&quot;/game/123/hashes&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;&lt;/a&gt;',

            // Quote escaping attempts.
            '<a href="/game/123/hashes" onmouseover="alert(1)">hack</a>' => '&lt;a href=&quot;/game/123/hashes&quot; onmouseover=&quot;alert(1)&quot;&gt;hack&lt;/a&gt;',

            // HTML entity encoding bypasses.
            '&#60;script&#62;alert(1)&#60;/script&#62;' => '&amp;#60;script&amp;#62;alert(1)&amp;#60;/script&amp;#62;',
        ];

        foreach ($cases as $input => $expected) {
            $output = $this->action->execute($input, isTicketComment: true);
            $this->assertEquals($expected, $output, "Failed on case {$input}");
        }
    }
}
