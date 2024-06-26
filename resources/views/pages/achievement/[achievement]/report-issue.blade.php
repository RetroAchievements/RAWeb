<?php

use App\Community\Enums\TicketType;
use App\Models\Achievement;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth']);
name('achievement.report-issue');

render(function (View $view, Achievement $achievement) {
    if (!Auth::user()->can('view', $achievement)) {
        return abort(401);
    }
    
    // TODO migrate this logic to a service

    $unlockedHardcore = false;
    $unlockedSoftcore = false;
    $unlockedManually = false;
    $hasHardcoreUnlocks = false;
    $hasSoftcoreUnlocks = false;
    $hasSession = false;

    $playerAchievements = Auth::user()->playerAchievements()
        ->forGame($achievement->game)
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
        $hasSession = request()->user()->hasPlayed($achievement->game);
    }

    return $view->with([
        'unlockedHardcore' => $unlockedHardcore,
        'hasSession' => $hasSession,
        'ticketType' => $ticketType,
    ]);
});

?>

@props([
    'hasSession' => false,
    'ticketType' => 2, // TicketType
    'unlockedHardcore' => false,
])

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
        <x-ticket.guide-link
            buttonText="Create Ticket"
            href="{{ route('achievement.create-ticket', ['achievement' => $achievement]) }}?type={{ TicketType::DidNotTrigger }}"
        >
            I met the requirements, but the achievement did not trigger.
        </x-ticket.guide-link>

        <x-ticket.guide-link
            buttonText="Create Ticket"
            href="{{ route('achievement.create-ticket', ['achievement' => $achievement]) }}?type={{ TicketType::TriggeredAtWrongTime }}"
        >
            I unlocked this achievement without meeting the requirements, and then I reset it.
        </x-ticket.guide-link>

        @php
            $subject = urlencode("Manual Unlock: {$achievement->title} [{$achievement->id}] ({$achievement->game->title})");
            $message = urlencode("I'd like a manual unlock for [ach={$achievement->id}]:\n(Provide link to video/screenshot showing evidence)");
        @endphp
        <x-ticket.guide-link
            buttonText="Request Manual Unlock"
            href="{{ route('message.create') }}?to=RAdmin&subject={{ $subject }}&message={{ $message }}&templateKind=manual-unlock"
        >
            The achievement triggered, but the unlock didn't appear on my profile.
        </x-ticket.guide-link>
    @else
        <x-ticket.guide-link
            buttonText="Create Ticket"
            href="{{ route('achievement.create-ticket', ['achievement' => $achievement]) }}?type={{ TicketType::TriggeredAtWrongTime }}"
        >
            I unlocked this achievement without meeting the requirements.
        </x-ticket.guide-link>
    @endif

    @php
        $subject = urlencode("Typo: {$achievement->title} [{$achievement->id}] ({$achievement->game->title})");
        $message = urlencode("I'd like to report a spelling/grammar error in [ach={$achievement->id}]:\n(Describe the issue here)");
    @endphp
    <x-ticket.guide-link
        buttonText="Message WritingTeam"
        href="{{ route('message.create') }}?to=WritingTeam&subject={{ $subject }}&message={{ $message }}&templateKind=writing-error"
    >
        There is a spelling or grammatical error in the title or description.
    </x-ticket.guide-link>

    @php
        $subject = urlencode("Incorrect type: {$achievement->title} [{$achievement->id}] ({$achievement->game->title})");
        $message = urlencode("I'd like to report a misclassification error in [ach={$achievement->id}]:\n(Describe the issue here)");
    @endphp
    <x-ticket.guide-link
        buttonText="Message QATeam"
        href="{{ route('message.create') }}?to=QATeam&subject={{ $subject }}&message={{ $message }}&templateKind=misclassification"
    >
        The achievement type (progression/win/missable) is not correct.
    </x-ticket.guide-link>

    @php
        $subject = urlencode("Unwelcome Concept: {$achievement->title} [{$achievement->id}] ({$achievement->game->title})");
        $message = urlencode(
            "I'd like to report an unwelcome concept in [ach={$achievement->id}].\n\n" .

            "- Which Unwelcome Concept:\n" .
            "(Insert which concept from the docs here)\n\n" .

            "- Detailed Explanation:\n" .
            "(Provide as much detail as possible here. Assume the reader may not have played the game before. The more detail you provide, the better your case.)"
        );
    @endphp
    <x-ticket.guide-link
        buttonText="Message DevCompliance"
        href="{{ route('message.create') }}?to=DevCompliance&subject={{ $subject }}&message={{ $message }}&templateKind=unwelcome-concept"
    >
        The achievement contains an <a href="https://docs.retroachievements.org/guidelines/content/unwelcome-concepts.html">unwelcome concept</a>.
    </x-ticket.guide-link>

    @php
        $subject = urlencode("Issue: {$achievement->title} [{$achievement->id}] ({$achievement->game->title})");
        $message = urlencode("I'd like to report an issue with [ach={$achievement->id}]:\n(Describe the issue here)");
    @endphp
    <x-ticket.guide-link
        buttonText="Message QATeam"
        href="{{ route('message.create') }}?to=QATeam&subject={{ $subject }}&message={{ $message }}&templateKind=achievement-issue"
    >
        I have an issue with this achievement that is not described above.
    </x-ticket.guide-link>
</x-app-layout>
