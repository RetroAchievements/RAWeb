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

        $this->assertEquals($expected, $this->action->execute($input));
    }

    public function testItEscapesHtmlContent(): void
    {
        $input = '<script>alert("xss")</script><b>bold</b>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;&lt;b&gt;bold&lt;/b&gt;';

        $this->assertEquals($expected, $this->action->execute($input));
    }

    public function testItNormalizesMultipleLineBreaks(): void
    {
        $input = "Line 1\n\n\n\nLine 2";
        $expected = "Line 1<br /><br />\nLine 2";

        $this->assertEquals($expected, $this->action->execute($input));
    }

    public function testItPreservesSingleLineBreaksBetweenListItems(): void
    {
        $input = "Line 1\n- Item 1\n- Item 2\n- Item 3";
        $expected = "Line 1<br />- Item 1<br />- Item 2<br />- Item 3";

        $this->assertEquals($expected, $this->action->execute($input));
    }

    public function testItPreservesSingleLineBreaksBetweenListItems2(): void
    {
        $input = "Line 1<br />- Item 1<br />- Item 2<br />- Item 3";
        $expected = "Line 1<br />- Item 1<br />- Item 2<br />- Item 3";

        $this->assertEquals($expected, $this->action->execute($input));
    }

    public function testItHandlesAttemptedMaliciousInput(): void
    {
        $cases = [
            // Script injection.
            '<script>alert(1)</script>' => '&lt;script&gt;alert(1)&lt;/script&gt;',

            // HTML entity encoding bypasses.
            '&#60;script&#62;alert(1)&#60;/script&#62;' => '&amp;#60;script&amp;#62;alert(1)&amp;#60;/script&amp;#62;',
        ];

        foreach ($cases as $input => $expected) {
            $output = $this->action->execute($input);
            $this->assertEquals($expected, $output, "Failed on case {$input}");
        }
    }
}
