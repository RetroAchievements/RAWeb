<?php

declare(strict_types=1);

namespace App\Community\Actions;

class FormatLegacyCommentPayloadAction
{
    // Only allow `href` and `title` attributes. Anything else is malicious.
    private const LINK_PATTERN = '/<a\s+href=[\'"](?:https?:\/\/[^\/]+)?\/game\/[\w\-]+\/hashes[\'"](?:\s+title=[\'"][^"\']*[\'"])?\s*>[^<>]*<\/a>/i';

    public function execute(string $payload, bool $isTicketComment): string
    {
        if (!$isTicketComment) {
            return $this->formatText($payload);
        }

        // Store original link tags with angle brackets in markers.
        $marker = '@@LINK_PLACEHOLDER_' . uniqid() . '_@@';
        $links = [];

        // Replace each link with a marker, storing the full HTML tag.
        $text = preg_replace_callback(self::LINK_PATTERN, function ($match) use (&$links, $marker) {
            $links[] = $match[0];

            return $marker . (count($links) - 1) . $marker;
        }, $payload);

        // Process the text and restore links.
        $text = $this->formatText($text);
        foreach ($links as $i => $link) {
            $text = str_replace($marker . $i . $marker, $link, $text);
        }

        return $text;
    }

    private function formatText(string $text): string
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
