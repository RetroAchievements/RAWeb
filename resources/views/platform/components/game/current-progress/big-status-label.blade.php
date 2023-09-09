@props([
    'beatenGameCreditDialogContext' => 's:|h:',
    'gameId' => 0,
    'isBeatable' => false,
    'isBeatenHardcore' => false,
    'isBeatenSoftcore' => false,
    'isCompleted' => false,
    'isMastered' => false,
    'isEvent' => false,
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
if ($isEvent && $statusLabel !== 'Unfinished') {
    $statusLabel = "Awarded";
}

$isBeaten = $isBeatable && ($isBeatenHardcore || $isBeatenSoftcore);

// This case can occur on legacy completion/mastery awards
// where progression achievements were added after the user
// had already mastered the game. It's an edge case, but we
// try to gracefully handle it anyway.
if (($isCompleted || $isMastered) && !$isBeaten) {
    $statusLabel = "Unbeaten";
}
?>

<div class="text-lg {{ $colorClassName }} mb-1.5 mt-0.5 flex items-center gap-x-1">
    <p>{{ $statusLabel }}</p>

    @hasfeature('beat')
        @if (!$isBeaten)
            <x-modal-trigger
                modalTitleLabel="Beaten Game Credit"
                resourceApiRoute="/request/game/beaten-credit.php"
                :resourceId="$gameId"
                :resourceContext="$beatenGameCreditDialogContext"
            >
                <x-slot name="trigger">
                    <x-fas-info-circle
                        aria-label="Learn about beaten game credit"
                        class="{{ $colorClassName }} w-5 h-5 -mt-1"
                    />
                </x-slot>
            </x-modal-trigger>
        @endif
    @endhasfeature
</div>