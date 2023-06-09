@props([
    'consoleName' => 'Unknown Console',
    'gameTitle' => 'Unknown Game',
    'iconUrl' => asset("assets/images/system/unknown.png")
])

<?php
// TODO: Migrate renderGameTitle to a Blade component.
$renderedTitle = renderGameTitle($gameTitle);
?>

<h1 class="text-h3">
    <span class="block mb-1">{!! $renderedTitle !!}</span>
    
    <div class="flex items-center gap-x-1">
        <img src="{{ $iconUrl }}" width="24" height="24" alt="Console icon">
        <span class="block text-sm tracking-tighter">{{ $consoleName }}</span>
    </div>
</h1>