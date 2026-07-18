@props([
    'leaderboard' => null, // Leaderboard
])

@php

use App\Platform\Services\TriggerDecoderService;

$triggerDecoderService = new TriggerDecoderService();
$gameId = $leaderboard->game_id;
$parts = $leaderboard->trigger_parts;

$startGroups = $triggerDecoderService->decode($parts['start']);
$triggerDecoderService->addCodeNotes($startGroups, $gameId);

$cancelGroups = $triggerDecoderService->decode($parts['cancel']);
$triggerDecoderService->addCodeNotes($cancelGroups, $gameId);

$submitGroups = $triggerDecoderService->decode($parts['submit']);
$triggerDecoderService->addCodeNotes($submitGroups, $gameId);

$valueGroups = $triggerDecoderService->decodeValue($parts['value']);
$triggerDecoderService->addCodeNotes($valueGroups, $gameId);

@endphp

<x-leaderboard.trigger-part :groups="$startGroups" :definition="$parts['start']" header="Start" />
<x-leaderboard.trigger-part :groups="$cancelGroups" :definition="$parts['cancel']" header="Cancel" />
<x-leaderboard.trigger-part :groups="$submitGroups" :definition="$parts['submit']" header="Submit" />
<x-leaderboard.trigger-part :groups="$valueGroups" :definition="$parts['value']" header="Value" />
