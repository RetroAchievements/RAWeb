<?php

use App\Models\Role;
use App\Models\TriggerTicket;
use App\Platform\Services\TicketViewService;
use App\Platform\Services\UserAgentService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:view,triggerTicket']);
name('ticket.show');

render(function (View $view, TriggerTicket $triggerTicket) {
    $userAgentService = new UserAgentService();
    $ticketService = new TicketViewService();
    $ticketService->load($triggerTicket);
    $sessions = $ticketService->buildHistory($triggerTicket, Auth::user());
    
    return $view->with([
        'ticket' => $triggerTicket,
        'unlocksSinceReported' => $ticketService->unlocksSinceReported,
        'openTickets' => $ticketService->openTickets,
        'closedTickets' => $ticketService->closedTickets,
        'sessions' => $sessions,
        'clients' => $ticketService->getClientBreakdown($userAgentService),
        'userAgentService' => $userAgentService,
        'contactReporterUrl' => $ticketService->contactReporterUrl,
        'existingUnlock' => $ticketService->existingUnlock,
        'ticketNotes' => $ticketService->ticketNotes,
    ]);
});

?>

@props([
    'ticket' => null, // ?TriggerTicket
    'unlocksSinceReported' => 0,
    'openTickets' => [],
    'closedTickets' => [],
    'sessions' => [],
    'clients' => [],
    'userAgentService' => null, // ?UserAgentService
    'contactReporterUrl' => '',
    'existingUnlock' => null, // ?PlayerAchievement
    'ticketNotes' => '',
])

@php

use App\Community\Enums\ArticleType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TriggerTicketState;
use App\Community\Enums\TriggerTicketType;
use App\Enums\Permissions;
use App\Enums\PlayerGameActivityEventType;
use App\Models\User;
use App\Platform\Services\TriggerDecoderService;
use Illuminate\Support\Carbon;

$user = Auth::user();
$permissions = $user->getAttribute('Permissions');
$commentData = [];

@endphp

<x-app-layout
    pageTitle="Ticket {{ $ticket->id }}: {!! $ticket->achievement->Title !!} ({!! $ticket->type->label() !!})"
    pageDescription="{{ $ticket->achievement->Description }}"
    pageImage="{{ media_asset('/Badge/' . $ticket->achievement->BadgeName . '.png') }}"
    pageType="retroachievements:ticket"
>
    <div class="navpath">
        <a href="{{ route('tickets.index') }}">Open Tickets</a>
        &raquo;
        <a href="{{ route('game.tickets', ['game' => $ticket->achievement->game]) }}">{{ $ticket->achievement->game->Title }}</a>
        &raquo;
        <span class="font-bold">Ticket {{ $ticket->id }}</span>
    </div>

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! achievementAvatar($ticket->achievement, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $ticket->achievement->Title }} ({{ $ticket->type->label() }})</h1>
    </div>

    <div class="grid md:grid-cols-2 gap-x-12 gap-y-1">
        <div class="flex flex-col gap-y-1">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Ticket Information</p>
            <div class="relative w-full p-2 bg-embed rounded">
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
                    <x-ticket.stat-element label="Hash"><a href='{!! route("game.hashes.index", ["game" => $ticket->achievement->game]) !!}' title='{{ $ticket->gameHash->name }}'>{{ $ticket->gameHash->md5 }}</a></x-ticket.stat-element>
                @else
                    <x-ticket.stat-element label="Hash">Unknown</x-ticket.stat-element>
                @endif
                <x-ticket.stat-element label="Mode">{{ $ticket->hardcore ? "Hardcore" : "Softcore" }}</x-ticket.stat-element>
                @if (!$ticket->state->isOpen())
                    @if ($ticket->resolver)
                        <x-ticket.stat-element label="Resolved by">{!! userAvatar($ticket->resolver ?? 'Deleted User', iconSize: 16) !!}</x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="Resolved by"><span class="text-muted">Unknown</span></x-ticket.stat-element>
                    @endif
                    <x-ticket.stat-element label="Resolved at">{{ getNiceDate($ticket->resolved_at->unix()) }}</x-ticket.stat-element>
                @else
                    <x-ticket.stat-element label="Times earned since reported">{{ $unlocksSinceReported }}</b></x-ticket.stat-element>
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-y-1">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Achievement Information</p>
                <div class="relative w-full p-2 bg-embed rounded">
                    <x-ticket.stat-element label="Achievement">{!! achievementAvatar($ticket->achievement, iconSize: 16) !!}</x-ticket.stat-element>
                    <x-ticket.stat-element label="Game">{!! gameAvatar($ticket->achievement->game, iconSize: 16) !!}</x-ticket.stat-element>
                    @php
                        $authorLabel = 'Author';
                        if (!$ticket->author->is($ticket->achievement->developer)) {
                            $authorLabel = 'Maintainer';
                        }
                    @endphp
                    <x-ticket.stat-element label="{{ $authorLabel }}">{!! userAvatar($ticket->author ?? 'Deleted User', iconSize: 16) !!}</x-ticket.stat-element>
        
                    @if ($ticket->achievement->type)
                        <x-ticket.stat-element label="Type">{{ __('achievement-type.' . $ticket->achievement->type) }}</x-ticket.stat-element>
                    @endif

                    @php $label = $ticket->state->isOpen() ? 'Other open tickets' : 'Open tickets' @endphp
                    @if (empty($openTickets))
                        <x-ticket.stat-element label="{{ $label }}"><span class="text-muted">None</span></x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="{{ $label }} ({{ count($openTickets) }})">
                            @foreach ($openTickets as $ticketId)
                                <a href="{{ route('ticket.show', ['triggerTicket' => $ticketId])}}">{!! $ticketId !!}</a>{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        </x-ticket.stat-element>
                    @endif
        
                    @php $label = $ticket->state->isOpen() ? 'Closed tickets' : 'Other closed tickets' @endphp
                    @if (empty($closedTickets))
                        <x-ticket.stat-element label="{{ $label }}"><span class="text-muted">None</span></x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="{{ $label }} ({{ count($closedTickets) }})">
                            @foreach ($closedTickets as $ticketId)
                                <a href="{{ route('ticket.show', ['triggerTicket' => $ticketId])}}">{!! $ticketId !!}</a>{{ $loop->last ? '' : ', ' }}
                            @endforeach
                        </x-ticket.stat-element>
                    @endif
                </div>
            </p>
        </div>
    </div>

    @if ($ticket->reporter)
      @can('manage', TriggerTicket::class)
        <div class="mt-2">
            <div class="flex w-full justify-between border-embed-highlight items-center">
                <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">
                    Player history for game
                </p>
            </div>

            <div class="relative w-full p-2 bg-embed rounded">
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
                            :game="$ticket->achievement->game"
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
                            @if ($permissions >= Permissions::Moderator)
                                <script>
                                    function AwardManually(hardcore) {
                                        showStatusMessage('Awarding...');

                                        $.post('/request/user/award-achievement.php', {
                                            user: '{{ $ticket->reporter->display_name }}',
                                            achievement: {{ $ticket->achievement->id }},
                                            hardcore: hardcore
                                        })
                                        .done(function () {
                                            location.reload();
                                        });
                                    }
                                </script>
                                <div class="flex items-center gap-x-3">
                                    <button class="btn" onclick="AwardManually(0)">Award Softcore</button>
                                    @if ($ticket->hardcore)
                                    <button class="btn" onclick="AwardManually(1)">Award Hardcore</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
      @endcan
    @endif

    <div class="mt-2">
        <div class="flex w-full justify-between border-embed-highlight items-center">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">
                Comments
            </p>
            @if ($ticket->reporter)
                @can('manage', TriggerTicket::class)
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
                        articleType="{{ ArticleType::AchievementTicket }}"
                        :author="$ticket->reporter"
                        :when="$ticket->created_at"
                        :payload="$ticketNotes"
                    />
                    @php $numArticleComments = getRecentArticleComments(ArticleType::AchievementTicket, $ticket->id, $commentData) @endphp
                    @php $allowDelete = $user->hasRole(Role::MODERATOR) @endphp
                    @foreach ($commentData as $comment)
                        @php
                            $when = Carbon::createFromTimestampUTC($comment['Submitted']);
                            $commentUser = match($comment['User']) {
                                $ticket->reporter?->User => $ticket->reporter,
                                $user->User => $user,
                                default => User::withTrashed()->where('User', $comment['User'])->first()
                            };
                        @endphp
                        <x-comment.item
                            :author="$commentUser"
                            :when="$when"
                            :payload="nl2br($comment['CommentPayload'])"
                            articleType="{{ ArticleType::AchievementTicket }}"
                            :articleId="$ticket->id"
                            :commentId="$comment['ID']"
                            :allowDelete="$allowDelete || $comment['User'] === $user->User"
                        />
                    @endforeach

                    <x-comment.input-row
                        articleType="{{ ArticleType::AchievementTicket }}"
                        articleId="{{ $ticket->id }}"
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
                @if ($ticket->state->isOpen())
                    <select name="action" id="ticketAction" required>
                        <option value="" disabled selected hidden>Choose an action...</option>
                        <option value="{{ TicketAction::Resolved }}">Resolve as fixed (add comments about your fix above)</option>
                        @if ($ticket->state === TriggerTicketState::Open)
                            @php
                                $lastComment = null;
                                foreach ($commentData as $comment) {
                                    if ($comment['User'] != 'Server') {
                                        $lastComment = $comment;
                                    }
                                }
                            @endphp
                            @if ($lastComment != null && ($lastComment['User'] === $user->User || $lastComment['User'] === $ticket->achievement->developer->display_name) && !$ticket->reporter->trashed())
                                <option value="{{ TicketAction::Request }}">Transfer to reporter - {{ $ticket->reporter->display_name }}</option>
                            @endif
                        @else
                            <option value="{{ TicketAction::Reopen }}">Transfer to author - {{ $ticket->achievement->developer->display_name }}</option>
                        @endif
                        <option value="{{ TicketAction::Demoted }}">Demote achievement to Unofficial</option>
                        <option value="{{ TicketAction::Network }}">Close - Network problems</option>
                        <option value="{{ TicketAction::NotEnoughInfo }}">Close - Not enough information</option>
                        <option value="{{ TicketAction::WrongRom }}">Close - Wrong ROM</option>
                        <option value="{{ TicketAction::UnableToReproduce }}">Close - Unable to reproduce</option>
                        @if (!$ticket->emulator?->can_debug_triggers)
                            <option value="{{ TicketAction::UnableToDebug }}">Close - Unable to debug due to no toolkit support</option>
                        @endif
                        <option value="{{ TicketAction::ClosedMistaken }}">Close - Mistaken report</option>
                        <option value="{{ TicketAction::ClosedOther }}">Close - Another reason (add comments above)</option>
                    </select>
                    <button class='btn' type="submit">Perform action</button>
                    
                    <script>
                        document.getElementById('ticketActionForm').addEventListener('submit', function(e) {
                            const actionSelect = document.getElementById('ticketAction');

                            // Check if "Unable to debug" is selected. If it is, we'll add a minor speedbump.
                            if (actionSelect.value === '{{ TicketAction::UnableToDebug }}') {
                                if (!confirm('If the player provided reproduction steps, you should still try to reproduce the issue before closing the ticket. Are you sure you want to close this ticket?')) {
                                    e.preventDefault();

                                    return false;
                                }
                            }
                        });
                    </script>
                @else
                    <input type="hidden" name="action" value="{{ TicketAction::Reopen }}">
                    <button class='btn'>Reopen this ticket</button>
                @endif
            @elseif ($user->id === $ticket->reporter->id)
                @if ($ticket->state->isOpen())
                    <input type="hidden" name="action" value="{{ TicketAction::ClosedMistaken }}">
                    <button class='btn'>Close as mistaken report</button>
                @endif
            @endif
        </form>

        @can('manage', TriggerTicket::class)
            <div class="mt-4 w-full relative flex gap-x-3">
                <button id="achievementLogicButton" class="btn"
                        onclick="toggleExpander('achievementLogicButton', 'achievementLogicContent')">Achievement Logic ▼</button>
            </div>

            <div id="achievementLogicContent" class="hidden devboxcontainer">
                <ul class="list-disc ml-4 mb-2">
                    <li>Achievement ID: {{ $ticket->achievement->id }}</li>
                    <li>
                        Mem:
                        <code>{{ $ticket->achievement->MemAddr }}</code>
                    </li>
                </ul>

                <p>Mem explained:</p>
                <div>
                    @php
                        $triggerDecoderService = new TriggerDecoderService();
                        $groups = $triggerDecoderService->decode($ticket->achievement->MemAddr);
                        $triggerDecoderService->addCodeNotes($groups, $ticket->achievement->GameID);
                    @endphp
                    <x-trigger.viewer :groups="$groups" />
                </div>
            </div>
        @endcan
    </div>
</x-app-layout>
