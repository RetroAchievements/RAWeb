@props([
    'isDisplayingTags' => true,
    'rawTitle' => '',
])

<?php
$processedTitle = html_entity_decode($rawTitle, ENT_QUOTES, 'UTF-8');
$containsMissableTag = false;

if (Str::contains($processedTitle, '[m]')) {
    $containsMissableTag = true;
    $processedTitle = str_replace('[m]', '', $processedTitle);
}

// If we don't strip consecutive spaces, the
// browser doesn't collapse them in forum <pre> tags.
$processedTitle = preg_replace('/\s+/', ' ', $processedTitle);
?>

{{ $processedTitle }}
@if ($isDisplayingTags && $containsMissableTag)
    <span class="tag missable" title="Missable">
        <abbr>[<span>m</span>]</abbr>
    </span>
@endif
