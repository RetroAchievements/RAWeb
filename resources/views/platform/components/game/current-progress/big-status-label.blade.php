@props([
    'isBeatable' => false,
    'isBeatenHardcore' => false,
    'isBeatenSoftcore' => false,
    'isCompleted' => false,
    'isMastered' => false,
])

<?php
// Determine what color the label should be.
$colorClassName = "text-text-muted";
if ($isBeatenSoftcore || $isBeatenHardcore || $isCompleted) {
    $colorClassName = "text-neutral-300 light:text-neutral-500";
}
if ($isMastered) {
    $colorClassName = "text-yellow-400 light:text-yellow-600";
}

// Determine what the content of the label should be.
$statusLabel = "Unfinished";
if ($isBeatable && $isBeatenSoftcore) {
    $statusLabel = "Beaten (softcore)";
}
if ($isBeatable && $isBeatenHardcore) {
    $statusLabel = "Beaten";
}
if ($isCompleted) {
    $statusLabel = "Completed";
}
if ($isMastered) {
    $statusLabel = "Mastered";
}
// This case can occur on legacy completion/mastery awards
// where progression achievements were added after the user
// had already mastered the game. It's an edge case, but we
// try to gracefully handle it anyway.
if (($isCompleted || $isMastered) && ($isBeatable && !$isBeatenHardcore && !$isBeatenSoftcore)) {
    $statusLabel = "Unbeaten";
}
?>

<p class="text-lg {{ $colorClassName }} mb-2.5 mt-0.5">
    {{ $statusLabel }}
</p>