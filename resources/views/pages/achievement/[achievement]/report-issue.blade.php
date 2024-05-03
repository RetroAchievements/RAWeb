<?php

use function Laravel\Folio\{middleware, name};

middleware(['auth', 'can:view,achievement']);
name('achievement.report-issue');

?>

@props([
    'achievement' => null, // Achievement
])

@php

use App\Community\Enums\TicketType;

$unlockedHardcore = false;
$unlockedSoftcore = false;
$unlockedManually = false;
$hasHardcoreUnlocks = false;
$hasSoftcoreUnlocks = false;
$hasSession = false;

$playerAchievements = request()->user()->playerAchievements()
    ->join('Achievements', 'player_achievements.achievement_id', '=', 'Achievements.ID')
    ->where('Achievements.GameID', $achievement->game->id)
    ->get();

$ticketType = TicketType::DidNotTrigger;

foreach ($playerAchievements as $playerAchievement) {
    if ($playerAchievement->achievement_id === $achievement->id) {
        $unlockedHardcore = $playerAchievement->unlocked_hardcore_at !== null;
        $unlockedSoftcore = $playerAchievement->unlocked_at !== null;
        $unlockedManually = $playerAchievement->unlocker_id !== null;
    }

    $hasHardcoreUnlocks |= $playerAchievement->unlocked_hardcore_at !== null;
    $hasSoftcoreUnlocks |= $playerAchievement->unlocked_at !== null;
    $hasSession = true;
}

if ($unlockedHardcore || ($unlockedSoftcore && !$hasHardcoreUnlocks)) {
    $ticketType = TicketType::TriggeredAtWrongTime;
}

if (!$hasSession) {
    $hasSession = request()->user()->playerGames()->where('game_id', $achievement->game->id)->exists();
}

@endphp

<x-app-layout
    pageTitle="Report Issue - {{ $achievement->Title }}"
    pageDescription="Report an issue with the achievement: {{ $achievement->Title }}"
>
    <x-achievement.breadcrumbs 
        :achievement="$achievement"
        currentPageLabel="Report Issue"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! achievementAvatar($achievement, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $achievement->Title }} - Report Issue</h1>
    </div>

    <div>
        @if (!$hasSession)
            {{-- don't say anything if the user has never loaded the game --}}
        @elseif ($ticketType === TicketType::DidNotTrigger)
            You <span class="font-bold">have not</span> unlocked this achievement.
        @elseif ($unlockedHardcore)
            You <span class="font-bold">have</span> unlocked this achievement.
        @else
            You <span class="font-bold">have</span> unlocked this achievement <span class="font-bold">in softcore</span>.
        @endif
    </div>

    <div class="mt-3">
        What sort of issue would you like to report?
    </div>

    @if (!$hasSession)
        {{-- don't allow player to create tickets if they've never loaded the game --}}
    @elseif ($ticketType === TicketType::DidNotTrigger)
        <x-ticket.guide-link buttonText="Create Ticket"
                             href="{{ route('achievement.create-ticket', $achievement) }}?type={{ TicketType::DidNotTrigger }}">
            I met the requirements, but the achievement did not trigger.
        </x-ticket.guide-link>

        <x-ticket.guide-link buttonText="Create Ticket"
                             href="{{ route('achievement.create-ticket', $achievement) }}?type={{ TicketType::TriggeredAtWrongTime }}">
            I unlocked this achievement without meeting the requirements, and then I reset it.
        </x-ticket.guide-link>

        @php
            $subject = urlencode("Manual Unlock: $achievement->title ({$achievement->game->title})");
            $message = urlencode("I'd like a manual unlock for [ach=$achievement->id]:\n(provide link to video/screenshot showing evidence)");
        @endphp
        <x-ticket.guide-link buttonText="Request Manual Unlock"
                             href="{{ route('message.create') }}?to=RAdmin&subject={{ $subject }}&message={{ $message }}">
            The achievement triggered, but the unlock didn't appear on my profile.
        </x-ticket.guide-link>
    @else
        <x-ticket.guide-link buttonText="Create Ticket"
                             href="{{ route('achievement.create-ticket', $achievement) }}?type={{ TicketType::TriggeredAtWrongTime }}">
            I unlocked this achievement without meeting the requirements.
        </x-ticket.guide-link>
    @endif

    @php
        $subject = urlencode("Typo: $achievement->title ({$achievement->game->title})");
        $message = urlencode("I'd like to report a spelling/grammar error in [ach=$achievement->id]:\n(Describe the issue here)");
    @endphp
    <x-ticket.guide-link buttonText="Message QATeam"
                         href="{{ route('message.create') }}?to=QATeam&subject={{ $subject }}&message={{ $message }}">
        There is a spelling or grammatical error in the title or description.
    </x-ticket.guide-link>

    @php
        $subject = urlencode("Incorrect type: $achievement->title ({$achievement->game->title})");
        $message = urlencode("I'd like to report a misclassification error in [ach=$achievement->id]:\n(Describe the issue here)");
    @endphp
    <x-ticket.guide-link buttonText="Message QATeam"
                         href="{{ route('message.create') }}?to=QATeam&subject={{ $subject }}&message={{ $message }}">
        The achievement type (progression/win/missable) is not correct.
    </x-ticket.guide-link>

    @php
        $subject = urlencode("Unwelcome Concept: $achievement->title ({$achievement->game->title})");
        $message = urlencode("I'd like to report an unwelcome concept in [ach=$achievement->id]:\n(Describe the issue here)");
    @endphp
    <x-ticket.guide-link buttonText="Message DevCompliance"
                         href="{{ route('message.create') }}?to=DevCompliance&subject={{ $subject }}&message={{ $message }}">
        <span>The achievement contains an <a href="https://docs.retroachievements.org/Unwelcome-Concepts/">unwelcome concept</a>.</span>
    </x-ticket.guide-link>

    @php
        $subject = urlencode("Issue: $achievement->title ({$achievement->game->title})");
        $message = urlencode("I'd like to report an issue with [ach=$achievement->id]:\n(Describe the issue here)");
    @endphp
    <x-ticket.guide-link buttonText="Message RAdmin"
                         href="{{ route('message.create') }}?to=RAdmin&subject={{ $subject }}&message={{ $message }}">
        I have an issue with this achievement that is not described above.
    </x-ticket.guide-link>

</x-app-layout>
