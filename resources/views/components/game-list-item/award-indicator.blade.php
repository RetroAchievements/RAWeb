@props([
    'highestAwardKind' => null,
])

<?php
$awardTitles = [
    'beaten-softcore' => 'Beaten (casual)',
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
