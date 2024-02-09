@props([
    'highestAwardKind' => null, // null | 'beaten-softcore' | 'beaten-hardcore' | 'completed' | 'mastered'
])

<?php
$awardTitles = [
    'beaten-softcore' => 'Beaten (softcore)',
    'beaten-hardcore' => 'Beaten',
    'completed' => 'Completed',
    'mastered' => 'Mastered',
];
$titleLabel = $awardTitles[$highestAwardKind] ?? 'Unfinished';
?>

<div class="cprogress-ind__root" data-award="{{ $highestAwardKind }}" title="{{ $titleLabel }}">
    {{-- .cprogress-ind__root > div --}}
    <div>
        {{-- .cprogress-ind__root > div > div:first-child --}}
        <div></div>
        
        {{-- .cprogress-ind__root > div > div:last-child --}}
        <div></div>
    </div>
</div>
