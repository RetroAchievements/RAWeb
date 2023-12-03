@props([
    'rawTitle' => '',
])

<?php
$processedTitle = html_entity_decode($rawTitle, ENT_QUOTES, 'UTF-8');

// If we don't strip consecutive spaces, the
// browser doesn't collapse them in forum <pre> tags.
$processedTitle = preg_replace('/\s+/', ' ', $processedTitle);
?>

{{ $processedTitle }}
