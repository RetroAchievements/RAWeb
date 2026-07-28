<?php

use App\Models\Ticket;
use App\Platform\Services\TicketViewService;
use App\Platform\Services\UserAgentService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,ticket']);
name('ticket.show');

render(function (View $view, Ticket $ticket) {
    abort_if(!$ticket->ticketable, 404);

    $userAgentService = new UserAgentService();
    $ticketService = new TicketViewService();
    $ticketService->load($ticket);
    $sessions = $ticketService->buildHistory($ticket, Auth::user());

    return $view->with([
        'ticket' => $ticket,
        'unlocksSinceReported' => $ticketService->unlocksSinceReported,
        'openTickets' => $ticketService->openTickets,
        'closedTickets' => $ticketService->closedTickets,
        'sessions' => $sessions,
        'clients' => $ticketService->getClientBreakdown($userAgentService),
        'userAgentService' => $userAgentService,
        'contactReporterUrl' => $ticketService->contactReporterUrl,
        'existingUnlock' => $ticketService->existingUnlock,
        'reporterLeaderboardEntry' => $ticketService->reporterLeaderboardEntry,
        'ticketNotes' => $ticketService->ticketNotes,
    ]);
});

?>

@props([
    'ticket' => null, // ?Ticket
    'unlocksSinceReported' => 0,
    'openTickets' => [],
    'closedTickets' => [],
    'sessions' => [],
    'clients' => [],
    'userAgentService' => null, // ?UserAgentService
    'contactReporterUrl' => '',
    'existingUnlock' => null, // ?PlayerAchievement
    'reporterLeaderboardEntry' => null, // ?LeaderboardEntry
    'ticketNotes' => '',
])

@php

use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\Permissions;
use App\Enums\PlayerGameActivityEventType;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\TicketableType;
use App\Platform\Enums\ValueFormat;
use App\Platform\Services\TriggerDecoderService;
use Illuminate\Support\Carbon;

$user = Auth::user();
$permissions = $user->getAttribute('Permissions');
$commentData = [];
$ticketListStatusFilter = match ($ticket->state) {
    TicketState::Resolved, TicketState::Closed => 'resolved',
    TicketState::Quarantined => 'quarantined',
    default => 'unresolved',
};
$ticketListBreadcrumbLabel = match ($ticketListStatusFilter) {
    'resolved' => 'Resolved Tickets',
    'quarantined' => 'Quarantined Tickets',
    default => 'Open Tickets',
};

$ticketable = $ticket->getTicketableModel();
$isAchievementTicket = $ticket->ticketable_type === TicketableType::Achievement->value;
$ticketableGame = $ticketable->getTicketableGame();
$ticketableTitle = $ticketable->getTicketableTitle();
$ticketableAssignee = $ticketable->getTicketableAssignee();

@endphp

<x-app-layout
    pageTitle="Ticket {{ $ticket->id }}: {!! $ticketableTitle !!} ({!! $ticket->type->label() !!})"
    pageDescription="{{ $ticketable->description }}"
    pageImage="{{ $ticketable->getTicketableIconUrl() }}"
    pageType="retroachievements:ticket"
>
    <div class="navpath">
        <a href="{{ route('tickets.index', ['filter' => ['status' => $ticketListStatusFilter]]) }}">{{ $ticketListBreadcrumbLabel }}</a>
        &raquo;
        <a href="{{ route('game.tickets', ['game' => $ticketableGame, 'filter' => ['status' => $ticketListStatusFilter]]) }}">{{ $ticketableGame->title }}</a>
        &raquo;
        <span class="font-bold">Ticket {{ $ticket->id }}</span>
    </div>

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        @if ($isAchievementTicket)
            {!! achievementAvatar($ticketable, label: false, iconSize: 48, iconClass: 'rounded-xs') !!}
        @endif
        <h1 class="mt-[10px] w-full">{{ $ticketableTitle }} ({{ $ticket->type->label() }})</h1>
    </div>

    <div class="grid md:grid-cols-2 gap-x-12 gap-y-1">
        <div class="flex flex-col gap-y-1">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Ticket Information</p>
            <div class="relative w-full p-2 bg-embed rounded-sm">
                <x-ticket.stat-element label="State">{{ $ticket->state->label() }}</x-ticket.stat-element>
                <x-ticket.stat-element label="Reporter">{!! userAvatar($ticket->reporter ?? 'Deleted User', iconSize: 16) !!}</x-ticket.stat-element>
                <x-ticket.stat-element label="Reported at">{{ getNiceDate($ticket->created_at->unix()) }}</x-ticket.stat-element>
                <x-ticket.stat-element label="Report type">{{ $ticket->type->label() }}</x-ticket.stat-element>
                @if ($ticket->emulator_version)
                    <x-ticket.stat-element label="Emulator">{{ $ticket->emulator?->name ?? 'Unknown' }} {{ $ticket->emulator_version }}</x-ticket.stat-element>
                @else
                    <x-ticket.stat-element label="Emulator">{{ $ticket->emulator?->name ?? 'Unknown' }}</x-ticket.stat-element>
                @endif
                @if ($ticket->emulator_core)
                    <x-ticket.stat-element label="Core">{{ $ticket->emulator_core }}</x-ticket.stat-element>
                @endif
                @if ($ticket->gameHash)
                    <x-ticket.stat-element label="Hash"><a href='{!! route("game.hashes.index", ["game" => $ticketableGame]) !!}' title='{{ $ticket->gameHash->name }}'>{{ $ticket->gameHash->md5 }}</a></x-ticket.stat-element>
                @else
                    <x-ticket.stat-element label="Hash">Unknown</x-ticket.stat-element>
                @endif
                <x-ticket.stat-element label="Mode">{{ $ticket->hardcore ? "Hardcore" : "Casual" }}</x-ticket.stat-element>
                @if ($ticket->state->isResolved())
                    @if ($ticket->resolver)
                        <x-ticket.stat-element label="Resolved by">{!! userAvatar($ticket->resolver ?? 'Deleted User', iconSize: 16) !!}</x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="Resolved by"><span class="text-muted">Unknown</span></x-ticket.stat-element>
                    @endif
                    <x-ticket.stat-element label="Resolved at">{{ getNiceDate($ticket->resolved_at->unix()) }}</x-ticket.stat-element>
                @elseif ($isAchievementTicket)
                    <x-ticket.stat-element label="Times earned since reported">{{ $unlocksSinceReported }}</x-ticket.stat-element>
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-y-1">
            @if ($isAchievementTicket)
                <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Achievement Information</p>
                <div class="relative w-full p-2 bg-embed rounded-sm">
                    <x-ticket.stat-element label="Achievement">{!! achievementAvatar($ticketable, iconSize: 16) !!}</x-ticket.stat-element>
                    <x-ticket.stat-element label="Game">{!! gameAvatar($ticketableGame, iconSize: 16) !!}</x-ticket.stat-element>
                    @php
                        $authorLabel = 'Author';
                        if (!$ticket->author?->is($ticketable->developer)) {
                            $authorLabel = 'Maintainer';
                        }
                    @endphp
                    <x-ticket.stat-element label="{{ $authorLabel }}">{!! userAvatar($ticket->author ?? 'Deleted User', iconSize: 16) !!}</x-ticket.stat-element>

                    @if ($ticketable->type)
                        <x-ticket.stat-element label="Type">{{ __('achievement-type.' . $ticketable->type) }}</x-ticket.stat-element>
                    @endif
            @else
                <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Leaderboard Information</p>
                <div class="relative w-full p-2 bg-embed rounded">
                    <x-ticket.stat-element label="Leaderboard"><a href="{{ $ticketable->getTicketableUrl() }}">{{ $ticketableTitle }}</a></x-ticket.stat-element>
                    <x-ticket.stat-element label="Game">{!! gameAvatar($ticketableGame, iconSize: 16) !!}</x-ticket.stat-element>
                    <x-ticket.stat-element label="Author">{!! userAvatar($ticketableAssignee ?? 'Deleted User', iconSize: 16) !!}</x-ticket.stat-element>
                    @if ($ticketable->format)
                        <x-ticket.stat-element label="Format">{{ ValueFormat::toString($ticketable->format) }}</x-ticket.stat-element>
                    @endif
                    <x-ticket.stat-element label="Rank order">{{ $ticketable->rank_asc ? 'Lower is better' : 'Higher is better' }}</x-ticket.stat-element>
                    @if ($reporterLeaderboardEntry)
                        @php
                            $reporterRank = $ticketable->getRank($reporterLeaderboardEntry->score);
                            $totalEntries = $ticketable->entries()->count();
                            $reporterScoreLabel = $ticketable->format
                                ? ValueFormat::format($reporterLeaderboardEntry->score, $ticketable->format)
                                : (string) $reporterLeaderboardEntry->score;
                        @endphp
                        <x-ticket.stat-element label="Reporter's entry">#{{ $reporterRank }} / {{ $totalEntries }} - {{ $reporterScoreLabel }}</x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="Reporter's entry"><span class="text-muted">No entry yet</span></x-ticket.stat-element>
                    @endif
            @endif

                    @php $label = $ticket->state->isOpen() ? 'Other open tickets' : 'Open tickets' @endphp
                    @if (empty($openTickets))
                        <x-ticket.stat-element label="{{ $label }}"><span class="text-muted">None</span></x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="{{ $label }} ({{ count($openTickets) }})">
                            @foreach ($openTickets as $ticketId)
                                <a href="{{ route('ticket.show', ['ticket' => $ticketId])}}">{!! $ticketId !!}</a>{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        </x-ticket.stat-element>
                    @endif

                    @php $label = $ticket->state->isOpen() ? 'Closed tickets' : 'Other closed tickets' @endphp
                    @if (empty($closedTickets))
                        <x-ticket.stat-element label="{{ $label }}"><span class="text-muted">None</span></x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="{{ $label }} ({{ count($closedTickets) }})">
                            @foreach ($closedTickets as $ticketId)
                                <a href="{{ route('ticket.show', ['ticket' => $ticketId])}}">{!! $ticketId !!}</a>{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        </x-ticket.stat-element>
                    @endif
                </div>
        </div>
    </div>

    @if ($isAchievementTicket && $ticket->reporter)
      @canany(['manage', 'viewHistory'], Ticket::class)
        <div class="mt-2">
            <div class="flex w-full justify-between border-embed-highlight items-center">
                <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">
                    Player history for game
                </p>
            </div>

            <div class="relative w-full p-2 bg-embed rounded-sm">
                <div class="w-full relative flex gap-x-3">
                    <x-user.client-list :clients="$clients" />

                    <button id="unlockHistoryButton" class="absolute bottom-0 right-0 btn"
                            onclick="toggleExpander('unlockHistoryButton', 'unlockHistoryContent')">Unlock History ▼</button>
                </div>

                <div id="unlockHistoryContent" class="hidden devboxcontainer">
                    @if (empty($sessions))
                        {{ $ticket->reporter->display_name }} has not earned any achievements for this game.
                    @else
                        <x-alert title="Warning">
                            <p>
                                Timestamps are captured when the server receives the request. A player experiencing
                                network issues may have an unusual sequence of unlocks within a small period of time.
                            </p>
                        </x-alert>

                        <x-user.game-activity
                            :game="$ticketableGame"
                            :user="$user"
                            :sessions="$sessions"
                            :userAgentService="$userAgentService"
                        />
                    @endif
                </div>

                <div class="flex mt-2 w-full justify-between border-embed-highlight items-center">
                    @if ($existingUnlock)
                        @php $unlockedAt = $existingUnlock->unlocked_hardcore_at ?? $existingUnlock->unlocked_at; @endphp
                        @if ($existingUnlock->unlocker_id)
                            <span>Manually unlocked by {!! userAvatar(User::firstWhere('id', $existingUnlock->unlocker_id), icon:false) !!} at {{ getNiceDate($unlockedAt->unix()) }}</span>
                        @else
                            {{ $ticket->reporter->display_name }} earned this achievement at
                            {{ getNiceDate($unlockedAt->unix()) }}
                            @if ($unlockedAt > $ticket->created_at)
                                (after the report)
                            @else
                                (before the report)
                            @endif
                        @endif
                    @else
                        <div class="flex w-full justify-between border-embed-highlight items-center">
                            {{ $ticket->reporter->display_name }} did not earn this achievement
                            @can('manuallyAward', App\Models\PlayerAchievement::class)
                                <script>
                                    function AwardManually(hardcore) {
                                        showStatusMessage('Awarding...');

                                        $.post('/request/user/award-achievement.php', {
                                            user: '{{ $ticket->reporter->display_name }}',
                                            achievement: {{ $ticketable->id }},
                                            hardcore: hardcore
                                        })
                                        .done(function () {
                                            location.reload();
                                        });
                                    }
                                </script>
                                <div class="flex items-center gap-x-3">
                                    <button class="btn" onclick="AwardManually(0)">Award Casual</button>
                                    @if ($ticket->hardcore)
                                    <button class="btn" onclick="AwardManually(1)">Award Hardcore</button>
                                    @endif
                                </div>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
      @endcanany
    @endif

    <div class="mt-2">
        <div class="flex w-full justify-between border-embed-highlight items-center">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">
                Comments
            </p>
            @if ($ticket->reporter)
                @can('manage', Ticket::class)
                    <div class="flex flex-col gap-y-1">
                        <a class="btn" href="{!! $contactReporterUrl !!}">
                            Message the reporter
                        </a>
                    </div>
                @endcan
            @endif
        </div>

        <div class="commentscomponent">
            <table id="feed" class="table-highlight">
                <tbody>
                    <x-comment.item
                        :commentableType="CommentableType::AchievementTicket"
                        :author="$ticket->reporter"
                        :when="$ticket->created_at"
                        :payload="$ticketNotes"
                    />
                    @php $numArticleComments = getRecentArticleComments(CommentableType::AchievementTicket, $ticket->id, $commentData) @endphp
                    @php $allowDelete = $user->hasRole(Role::MODERATOR) @endphp
                    @foreach ($commentData as $comment)
                        @php
                            $when = Carbon::createFromTimestampUTC($comment['Submitted']);
                            $commentUser = match($comment['User']) {
                                $ticket->reporter?->username => $ticket->reporter,
                                $user->username => $user,
                                default => User::withTrashed()->where('username', $comment['User'])->first()
                            };
                        @endphp
                        <x-comment.item
                            :author="$commentUser"
                            :when="$when"
                            :payload="nl2br($comment['CommentPayload'])"
                            :commentableType="CommentableType::AchievementTicket"
                            :commentableId="$ticket->id"
                            :commentId="$comment['ID']"
                            :allowDelete="$allowDelete || $comment['User'] === $user->username"
                        />
                    @endforeach

                    <x-comment.input-row
                        :commentableType="CommentableType::AchievementTicket"
                        :commentableId="$ticket->id"
                        :article="$ticket"
                    />
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <form method="post" action="/request/ticket/update.php" id="ticketActionForm">
            {!! csrf_field() !!}
            <input type="hidden" name="ticket" value="{{ $ticket->id }}">
            @if ($permissions >= Permissions::Developer)
                @if ($ticket->state->isOpen() || $ticket->state === TicketState::Quarantined)
                    <select name="action" id="ticketAction" required>
                        <option value="" disabled selected hidden>Choose an action...</option>
                        @if ($ticket->state === TicketState::Quarantined)
                            <option value="{{ TicketAction::Reopen }}">Approve this ticket</option>
                        @endif

                        @if ($ticket->state !== TicketState::Quarantined)
                            <option value="{{ TicketAction::Resolved }}">Resolve as fixed (add comments about your fix above)</option>
                        @endif

                        @if ($ticket->state === TicketState::Open)
                            @if (!$ticket->reporter->trashed())
                                @php
                                    $lastComment = null;
                                    $hasNonReporterComment = false;
                                    foreach ($commentData as $comment) {
                                        if ($comment['User'] === 'Server') {
                                            continue;
                                        }
                                        $lastComment = $comment;
                                        if ($comment['User'] !== $ticket->reporter->username) {
                                            $hasNonReporterComment = true;
                                        }
                                    }

                                    $assigneeName = $ticketableAssignee?->display_name ?? $ticket->author?->display_name;
                                    $isLatestCommentFromReporter = $lastComment !== null
                                        && $lastComment['User'] === $ticket->reporter->username
                                        && $hasNonReporterComment;
                                    $canTransferToReporter = $lastComment !== null
                                        && (
                                            $isLatestCommentFromReporter
                                            || $lastComment['User'] === $user->username
                                            || ($assigneeName && $lastComment['User'] === $assigneeName)
                                        );
                                @endphp
                                @if ($canTransferToReporter)
                                    <option
                                        value="{{ TicketAction::Request }}"
                                        @if ($isLatestCommentFromReporter)
                                            data-confirm="The reporter made the latest comment. Transfer this ticket back without adding a new comment?"
                                        @endif
                                    >
                                        Transfer to reporter{{ $isLatestCommentFromReporter ? ' without new comment' : '' }} - {{ $ticket->reporter->display_name }}
                                    </option>
                                @endif
                            @endif
                        @elseif ($ticket->state === TicketState::Request)
                            <option value="{{ TicketAction::Reopen }}">Transfer to author - {{ $ticketableAssignee?->display_name ?? $ticket->author?->display_name }}</option>
                        @endif

                        @if ($ticket->state !== TicketState::Quarantined)
                            <option value="{{ TicketAction::Demoted }}">
                                {{ $isAchievementTicket ? 'Demote achievement to Unofficial' : 'Demote leaderboard to Unpromoted' }}
                            </option>
                        @endif

                        <option value="{{ TicketAction::Network }}">Close - Network problems</option>
                        <option value="{{ TicketAction::NotEnoughInfo }}">Close - Not enough information</option>
                        <option value="{{ TicketAction::WrongRom }}">Close - Wrong ROM</option>
                        <option value="{{ TicketAction::UnableToReproduce }}">Close - Unable to reproduce</option>
                        @if (!$ticket->emulator?->can_debug_triggers)
                            <option
                                value="{{ TicketAction::UnableToDebug }}"
                                data-confirm="If the player provided reproduction steps, you should still try to reproduce the issue before closing the ticket. Are you sure you want to close this ticket?"
                            >
                                Close - Unable to debug due to no toolkit support
                            </option>
                        @endif
                        <option value="{{ TicketAction::ClosedMistaken }}">Close - Mistaken report</option>
                        <option value="{{ TicketAction::ClosedOther }}">Close - Another reason (add comments above)</option>
                    </select>
                    <button class='btn' type="submit">Perform action</button>

                    <script>
                        document.getElementById('ticketActionForm').addEventListener('submit', function(e) {
                            const actionSelect = document.getElementById('ticketAction');
                            const selectedOption = actionSelect.options[actionSelect.selectedIndex];
                            const confirmMessage = selectedOption.getAttribute('data-confirm');

                            if (confirmMessage && !confirm(confirmMessage)) {
                                e.preventDefault();

                                return false;
                            }
                        });
                    </script>
                @else
                    <input type="hidden" name="action" value="{{ TicketAction::Reopen }}">
                    <button class='btn'>Reopen this ticket</button>
                @endif
            @elseif ($user->id === $ticket->reporter->id)
                @if ($ticket->state->isOpen() || $ticket->state === TicketState::Quarantined)
                    <input type="hidden" name="action" value="{{ TicketAction::ClosedMistaken }}">
                    <button class='btn'>Close as mistaken report</button>
                @endif
            @endif
        </form>

        @canany(['manage', 'viewLogic',], Ticket::class)
            @php
                $logicLabel = $isAchievementTicket ? 'Achievement Logic' : 'Leaderboard Logic';
                $logicIdLabel = $isAchievementTicket ? 'Achievement ID' : 'Leaderboard ID';
            @endphp
            <div class="mt-4 w-full relative flex gap-x-3">
                <button id="ticketableLogicButton" class="btn"
                        onclick="toggleExpander('ticketableLogicButton', 'ticketableLogicContent')">{{ $logicLabel }} ▼</button>
            </div>

            <div id="ticketableLogicContent" class="hidden devboxcontainer">
                <ul class="list-disc ml-4 mb-2">
                    <li>{{ $logicIdLabel }}: {{ $ticketable->id }}</li>
                    <li>
                        Mem:
                        <code>{{ $ticketable->trigger_definition }}</code>
                    </li>
                </ul>

                <p>Mem explained:</p>
                <div>
                    @if ($isAchievementTicket)
                        @php
                            $triggerDecoderService = new TriggerDecoderService();
                            $groups = $triggerDecoderService->decode($ticketable->trigger_definition);
                            $triggerDecoderService->addCodeNotes($groups, $ticketable->game_id);
                        @endphp
                        <x-trigger.viewer :groups="$groups" />
                    @else
                        <x-leaderboard.trigger-viewer :leaderboard="$ticketable" />
                    @endif
                </div>
            </div>
        @endcanany
    </div>
</x-app-layout>
