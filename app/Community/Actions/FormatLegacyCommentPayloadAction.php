<?php

declare(strict_types=1);

namespace App\Community\Actions;

class FormatLegacyCommentPayloadAction
{
    // Only allow `href` and `title` attributes. Anything else is malicious.
    private const LINK_PATTERN = '/<a\s+href=[\'"](?:https?:\/\/[^\/]+)?\/game\/[\w\-]+\/hashes[\'"](?:\s+title=[\'"][^"\']*[\'"])?\s*>[^<>]*<\/a>/i';

    public function execute(string $payload, bool $isTicketComment): string
    {
        // Replace all <br /> tags with newlines for consistent processing.
        $text = str_replace('<br />', "\n", $payload);

        if (!$isTicketComment) {
            return $this->formatText($text);
        }

        // Store original link tags with angle brackets in markers.
        $marker = '@@LINK_PLACEHOLDER_' . uniqid() . '_@@';
        $links = [];

        // Replace each link with a marker, storing the full HTML tag.
        $text = preg_replace_callback(self::LINK_PATTERN, function ($match) use (&$links, $marker) {
            $links[] = $match[0];

            return $marker . (count($links) - 1) . $marker;
        }, $text);

        // Process the text and restore links.
        $text = $this->formatText($text);
        foreach ($links as $i => $link) {
            $text = str_replace($marker . $i . $marker, $link, $text);
        }

        return $text;
    }

    private function formatText(string $text): string
    {
        // Convert special characters to HTML entities.
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);

        // Convert newlines to <br /> tags and normalize multiple line breaks.
        return preg_replace(
            '/<br\s*\/?>(\s*<br\s*\/?>)+/',
            '<br /><br />',
            nl2br($text)
        );
    }
}
