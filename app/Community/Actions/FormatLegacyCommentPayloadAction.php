<?php

declare(strict_types=1);

namespace App\Community\Actions;

class FormatLegacyCommentPayloadAction
{
    public function execute(string $text): string
    {
        // First, normalize all existing <br /> tags to \n for consistent processing.
        $text = preg_replace('/<br\s*\/?>\s*/', "\n", $text);

        // Convert special characters to HTML entities.
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

        // Convert newlines to <br /> tags without adding additional \n.
        $text = str_replace("\n", '<br />', $text);

        // Collapse multiple consecutive line breaks into exactly two.
        $text = preg_replace('/(<br\s*\/?>\s*){2,}/', '<br /><br />', $text);

        // Add required \n after double breaks while preserving single breaks.
        return preg_replace('/<br \/><br \/>/', "<br /><br />\n", $text);
    }
}
