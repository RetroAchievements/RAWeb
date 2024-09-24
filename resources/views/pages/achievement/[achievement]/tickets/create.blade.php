<?php

use App\Community\Enums\TicketType;
use App\Models\Achievement;
use App\Models\Emulator;
use App\Platform\Enums\UnlockMode;
use App\Platform\Services\UserAgentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,achievement', 'can:create,' . App\Models\Ticket::class]);
name('achievement.create-ticket');

render(function (View $view, Achievement $achievement) {
    $user = Auth::user();

    // TODO migrate this logic to a service

    $ticketID = getExistingTicketID($user, $achievement->id);
    if ($ticketID !== 0) {
        abort_with(redirect(route('ticket.show', ['ticket' => $ticketID]))->withErrors(__('legacy.error.ticket_exists')));
    }

    $selectedType = (int) old('issue', request()->input('type'));
    $selectedMode = old('mode');
    $selectedHash = old('hash');

    $selectedEmulator = old('emulator');
    $emulatorVersion = old('emulator_version');
    $emulatorCore = old('emulator_core');
    $extra = old('extra', request()->input('extra'));

    if ($selectedEmulator === null) {
        $userAgent = null;
        $selectedHash = null;

        $unlock = $user->playerAchievements()->where('achievement_id', $achievement->id)->first();
        if ($unlock !== null) {
            $playerSession = $user->playerSessions()->firstWhere('player_sessions.id', $unlock->player_session_id);
            $userAgent = $playerSession?->user_agent;
            $selectedHash = $playerSession?->gameHash?->md5;
        }

        if ($userAgent === null) {
            // find the most recent session lasting at least five minutes
            $playerSession = $user->playerSessions()
                ->where('game_id', $achievement->game->id)
                ->where('duration', '>=', '5')
                ->orderBy('updated_at', 'DESC')
                ->first();
            $userAgent = $playerSession?->user_agent;
            $selectedHash = $playerSession?->gameHash?->md5;
        }

        if ($userAgent !== null) {
            $userAgentService = new UserAgentService();
            $decoded = $userAgentService->decode($userAgent);

            $selectedEmulator = $decoded['client'];
            $emulatorVersion = $decoded['clientVersion'];
            $emulatorCore = $decoded['clientVariation'] ?? null;
        }

        if ($unlock?->unlocked_hardcore_at) {
            // user has hardcore unlock for achievement
            $selectedMode = UnlockMode::Hardcore;
        } elseif ($user->playerSessions()->where('hardcore', 1)->exists()) {
            // user has hardcore sessions for game
            $selectedMode = UnlockMode::Hardcore;
        }
    }

    return $view->with([
        'emulatorCore' => $emulatorCore,
        'emulatorVersion' => $emulatorVersion,
        'selectedEmulator' => $selectedEmulator,
        'selectedHash' => $selectedHash,
        'selectedMode' => $selectedMode,
        'selectedType' => $selectedType,
        'extra' => $extra,
    ]);
});

?>

@props([
    'emulatorCore' => null, // ?string
    'emulatorVersion' => '',
    'selectedEmulator' => '',
    'selectedHash' => '',
    'selectedMode' => 0, // UnlockMode
    'selectedType' => '',
    'extra' => '',
])

<script>
function reportIssueComponent() {
    return {
        description: document.getElementById('description').value ?? '',
        emulator: document.getElementById('emulator').value ?? '',

        displayCore() {
            if (['RetroArch', 'RALibRetro', 'Bizhawk'].includes(this.emulator)) {
                document.getElementById('core-row').style.display = '';
            } else {
                document.getElementById('core-row').style.display = 'none';
            }
        },

        get descriptionIsNetworkProblem() {
            const networkRegex = /(manual\s+unlock|internet)/ig;
            return networkRegex.test(this.description);
        },

        get descriptionIsUnhelpful() {
            const unhelpfulRegex = /(n'?t|not?).*(work|trigger)/ig;
            return this.description.length < 25 && unhelpfulRegex.test(this.description);
        }
    }
}
</script>

<x-app-layout
    pageTitle="Create Ticket - {{ $achievement->Title }}"
    pageDescription="Create a ticket for the achievement: {{ $achievement->Title }}"
>
    <x-achievement.breadcrumbs 
        :achievement="$achievement"
        currentPageLabel="Create Ticket"
    />

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! achievementAvatar($achievement, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $achievement->Title }} - Create Ticket</h1>
    </div>

    <div x-data="reportIssueComponent()">

        <form action="/request/ticket/create.php" method="post">
            {{ csrf_field() }}
            <input type="hidden" value="{{ $achievement->id }}" name="achievement">
            @if (!empty($extra))
                <input type="hidden" value="{{ $extra }}" name="extra" />
            @endif
            <table class='table-highlight'>
                <tbody>
                <tr class="alt">
                    <td><label for="issue">Issue</label></td>
                    <td>
                        <select name="issue" id="issue" required>
                            <option value="" @if (empty($selectedType)) selected @endif disabled hidden>Select your issue...</option>
                            @foreach (TicketType::cases() as $type)
                            <option value="{{ $type }}" @if ($selectedType === $type) selected @endif>{{ TicketType::toString($type) }}</option>
                            @endforeach
                        </select>
                        <x-modal-trigger buttonLabel="What do these mean?" modalTitleLabel="Issue Kinds">
                            <x-modal-content.issue-description />
                        </x-modal-trigger>
                    </td>
                </tr>
                <tr>
                    <td><label for="emulator">Emulator</label></td>
                    <td>
                        <select
                            name="emulator"
                            id="emulator"
                            required
                            x-model="emulator"
                            x-init="displayCore()"
                            x-on:change="displayCore()"
                        >
                            <option @if ($selectedEmulator === null) selected @endif disabled hidden>Select your emulator...</option>
                            @foreach (Emulator::forSystem($achievement->game->system->id)->active()->get() as $emulator)
                                <option value="{{ $emulator->handle }}" @if ($selectedEmulator === $emulator->handle) selected @endif>
                                    {{ $emulator->handle }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="emulator_version">Emulator Version</label></td>
                    <td>
                        <input
                            type="text"
                            name="emulator_version"
                            id="emulator_version"
                            required
                            placeholder="Emulator version"
                            value="{{ $emulatorVersion }}"
                        >
                        <x-modal-trigger buttonLabel="Why?" modalTitleLabel="Why do I need this?">
                            <x-modal-content.why-emulator-version />
                        </x-modal-trigger>
                    </td>
                </tr>
                <tr id="core-row">
                    <td>
                        <label for="core">Core</label>
                    </td>
                    <td>
                        <input
                            class="w-full"
                            type="text"
                            name="core"
                            id="core"
                            placeholder="Which core did you use?"
                            value="{{ $emulatorCore }}"
                        >
                    </td>
                </tr>
                <tr>
                    <td><label for="mode">Mode:</label></td>
                    <td>
                        <select name="mode" id="mode" required>
                            @foreach (UnlockMode::cases() as $mode)
                            <option value="{{ $mode }}" @if ($selectedMode === $mode) selected @endif>{{ UnlockMode::toString($mode) }}</option>
                            @endforeach
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="hash">Supported Game File Hash</label></td>
                    <td>
                        @php
                        $hashes = collect(getHashListByGameID($achievement->game->id))
                            ->sortBy('Name')
                            ->groupBy(fn (array $hashData) => (int) empty($hashData['Name']))
                            ->reverse()
                            ->flatten(1);
                        @endphp
                        <select name="hash" id="hash" required>
                            <option value="Unknown">I don't know.</option>
                            @foreach ($hashes as $hashData)
                                <option value="{{ $hashData['Hash'] }}" @if ($selectedHash === $hashData['Hash']) selected @endif>
                                    @if (empty($hashData['Name']))
                                        {{ $hashData['Hash'] }}
                                    @else
                                        {{ $hashData['Hash'] }} - {{ $hashData['Name'] }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <x-modal-trigger buttonLabel="How do I find this?" modalTitleLabel="Find the Game File Hash">
                            <x-modal-content.how-to-find-hash />
                        </x-modal-trigger>
                    </td>
                </tr>
                <tr>
                    <td><label for="description">Description</label></td>
                    <td colspan="2">
                        <p>
                        Please describe what you were doing around the time of the problem. Also mention if you
                        were using any non-default settings, in game cheats, glitches, or were otherwise playing
                        in some way that may differ from the normal expected gameplay that the developer would
                        have used when creating the achievement.
                        </p>
                        <textarea
                            class="w-full forum mt-2 mb-1"
                            name="description"
                            id="description"
                            style="height:160px"
                            rows="5"
                            cols="61"
                            placeholder="Describe your issue here."
                            required
                            x-model="description"
                        >{{ old('description') }}</textarea>

                        <div x-cloak>
                            <p x-show="descriptionIsNetworkProblem">
                                Please do not use this tool for network issues.
                                See <a href="https://docs.retroachievements.org/general/faq.html#how-do-i-request-a-manual-unlock">here</a>
                                for instructions on how to request a manual unlock.
                            </p>
                            <p x-show="descriptionIsUnhelpful">
                                Please be more specific with your issue&mdash;such as by adding specific reproduction steps or what you
                                did before encountering it&mdash;instead of simply stating that it doesn't work. The more specific, the better.
                            </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td colspan="2" class="text-right">
                        <button class="btn" :disabled="descriptionIsUnhelpful">Submit Issue Report</button>
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</x-app-layout>
