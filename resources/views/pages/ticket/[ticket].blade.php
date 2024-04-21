<?php

use App\Models\Ticket;
use function Laravel\Folio\{middleware, name};

middleware(['auth', 'can:view,ticket']);
name('ticket.show');

?>

@php

use App\Community\Enums\ArticleType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketFilters;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\Permissions;
use App\Enums\PlayerGameActivityEventType;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\User;
use App\Platform\Services\PlayerGameActivityService;
use App\Platform\Services\TriggerDecoderService;
use App\Platform\Services\UserAgentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

$ticketID = $ticket->ID;

$user = request()->user();
$permissions = $user?->getAttribute('Permissions');

$msgTitle = rawurlencode("Bug Report ({$ticket->achievement->game->Title})");
$msgPayload = "Hi [user={$ticket->reporter->User}], I'm contacting you about [ticket={$ticket->ID}]";
$msgPayload = rawurlencode($msgPayload);
$contactReporterUrl = route('message.create') . "?to={$ticket->reporter->User}&subject=$msgTitle&message=$msgPayload";

$existingUnlock = PlayerAchievement::where('user_id', $ticket->reporter->id)
    ->where('achievement_id', $ticket->achievement->id)
    ->first();

$openTicketLinks = [];
$closedTicketLinks = [];
$achievementTickets = Ticket::where('AchievementID', $ticket->achievement->id);
foreach ($achievementTickets->get() as $otherTicket) {
    if ($otherTicket->ID !== $ticket->ID) {
        $url = '<a href="' . route('ticket.show', $otherTicket) . '">' . $otherTicket->ID . '</a>';
        if (TicketState::isOpen($otherTicket->ReportState)) {
            $openTicketLinks[] = $url;
        } else {
            $closedTicketLinks[] = $url;
        }
    }
}

$unlocksSinceReported = 0;
if (TicketState::isOpen($ticket->ReportState)) {
    $unlocksSinceReported = PlayerAchievement::where('achievement_id', $ticket->achievement->id)
        ->where(function($query) use ($ticket) {
            $query->where('unlocked_at', '>', $ticket->ReportedAt)
                ->orWhere('unlocked_hardcore_at', '>', $ticket->ReportedAt);
        })->count();
}

$ticketType = TicketType::toString($ticket->ReportType);
$ticketSummary = "{$ticket->achievement->Title} ($ticketType)";

$ticketState = TicketState::toString($ticket->ReportState);
$ticketMode = $ticket->Hardcore ? "Hardcore" : "Softcore";

$ticketNotes = nl2br($ticket->ReportNotes);
foreach ($ticket->achievement->game->hashes as $hash) {
    if (stripos($ticketNotes, $hash->md5) !== false) {
        $replacement = '<a href="/linkedhashes.php?g=' . $ticket->achievement->game->id . '" title="' .
            attributeEscape($hash->name) . '">' . $hash->md5 . '</a>';
        $ticketNotes = str_ireplace($hash->md5, $replacement, $ticketNotes);
    }
}

if ($permissions < Permissions::Developer) {
    $history = [];
    $userAgentLinks = [];
} else {
    $userAgentService = new UserAgentService();
    $activity = new PlayerGameActivityService();
    $activity->initialize($ticket->reporter, $ticket->achievement->game);

    $userAgents = [];
    $history = [];
    foreach ($activity->sessions as $session) {
        if ($session['userAgent'] ?? null) {
            if (!in_array($session['userAgent'], $userAgents)) {
                $userAgents[] = $session['userAgent'];
            }
        }
        foreach ($session['events'] as $event) {
            if ($event['type'] === PlayerGameActivityEventType::Unlock) {
                $history[] = $event;
            }
        }
    }
    $history[] = [
        'type' => 'ticket',
        'when' => $ticket->ReportedAt,    
    ];
    usort($history, function ($a, $b) {
        $diff = $b['when']->timestamp - $a['when']->timestamp;
        if ($diff === 0) {
            // two events at same time should be sub-sorted by ID
            $diff = ($a['ID'] ?? 0) - ($b['ID'] ?? 0);
        }

        return $diff;
    });

    $userAgentLinks = [];
    foreach ($userAgents as $userAgent) {
        $decoded = $userAgentService->decode($userAgent);
        $client = $decoded['client'];
        if ($decoded['clientVersion'] !== 'Unknown') {
            $client .= ' (' . $decoded['clientVersion'] . ')';
        }
        if (array_key_exists('clientVariation', $decoded)) {
            $client .= ' - ' . $decoded['clientVariation'];
        }
        $userAgentLinks[] = "<span title=\"$userAgent\">$client</span>";
    }
}

$pageTitle = "Ticket {$ticket->ID}: $ticketSummary";
@endphp

<x-app-layout
    pageTitle="{{ $pageTitle }}"
    pageDescription="{{ $ticket->achievement->Description }}"
    pageImage="{{ media_asset('/Badge/' . $ticket->achievement->BadgeName . '.png') }}"
    pageType="retroachievements:ticket"
>
    <div class="navpath">
        <a href="/ticketmanager.php">Open Tickets</a>
        &raquo;
        <a href="/ticketmanager.php?g={{ $ticket->achievement->game->ID }}">{{ $ticket->achievement->game->Title }}</a>
        &raquo;
        <span class="font-bold">Ticket {{ $ticket->ID }}</span>
    </div>

    <div class="mt-3 mb-1 w-full flex gap-x-3">
        {!! achievementAvatar($ticket->achievement, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $ticketSummary }}</h1>
    </div>

    <div class="grid md:grid-cols-2 gap-x-12 gap-y-1">
        <div class="flex flex-col gap-y-1">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Ticket Information</p>
            <div class="relative w-full p-2 bg-embed rounded">
                <x-ticket.stat-element label="State">{{ $ticketState }}</x-ticket.stat-element>
                <x-ticket.stat-element label="Reporter">{!! userAvatar($ticket->reporter, iconSize:16) !!}</x-ticket.stat-element>
                <x-ticket.stat-element label="Reported at">{{ getNiceDate($ticket->ReportedAt->unix()) }}</x-ticket.stat-element>
                <x-ticket.stat-element label="Report type">{{ $ticketType }}</x-ticket.stat-element>
                <x-ticket.stat-element label="Mode">{{ $ticketMode }}</x-ticket.stat-element>
                @if (!TicketState::isOpen($ticket->ReportState))
                    @if ($ticket->resolver)
                        <x-ticket.stat-element label="Resolved by">{!! userAvatar($ticket->resolver, iconSize:16) !!}</x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="Resolved by"><span class="text-muted">Unknown</span></x-ticket.stat-element>
                    @endif
                    <x-ticket.stat-element label="Resolved at">{{ getNiceDate($ticket->ResolvedAt->unix()) }}</x-ticket.stat-element>
                @else
                    <x-ticket.stat-element label="Times earned since reported">{{ $unlocksSinceReported }}</b></x-ticket.stat-element>
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-y-1">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">Achievement Information</p>
                <div class="relative w-full p-2 bg-embed rounded">
                    <x-ticket.stat-element label="Achievement">{!! achievementAvatar($ticket->achievement, iconSize:16) !!}</x-ticket.stat-element>
                    <x-ticket.stat-element label="Game">{!! gameAvatar($ticket->achievement->game, iconSize:16) !!}</x-ticket.stat-element>
                    <x-ticket.stat-element label="Author">{!! userAvatar($ticket->achievement->author, iconSize:16) !!}</x-ticket.stat-element>
        
                    @if ($ticket->achievement->type)
                        <x-ticket.stat-element label="Type">{{ __('achievement-type.' . $ticket->achievement->type) }}</x-ticket.stat-element>
                    @endif

                    @php $label = TicketState::isOpen($ticket->ReportState) ? 'Other open tickets' : 'Open tickets' @endphp
                    @if (empty($openTicketLinks))
                        <x-ticket.stat-element label="{{ $label }}"><span class="text-muted">None</span></x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="{{ $label }} ({{ count($openTicketLinks) }})">{!! implode(', ', $openTicketLinks) !!}</x-ticket.stat-element>
                    @endif
        
                    @php $label = TicketState::isOpen($ticket->ReportState) ? 'Closed tickets' : 'Other closed tickets' @endphp
                    @if (empty($closedTicketLinks))
                        <x-ticket.stat-element label="{{ $label }}"><span class="text-muted">None</span></x-ticket.stat-element>
                    @else
                        <x-ticket.stat-element label="{{ $label }} ({{ count($closedTicketLinks) }})">{!! implode(', ', $closedTicketLinks) !!}</x-ticket.stat-element>
                    @endif
                </div>
            </p>
        </div>
    </div>

    @if ($permissions >= Permissions::Developer)
        <div class="mt-2">
            <div class="flex w-full justify-between border-embed-highlight items-center">
                <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">
                    Player history for game
                </p>
            </div>

            <div class="relative w-full p-2 bg-embed rounded">
                <div class="w-full relative flex gap-x-3">
                @if (empty($userAgentLinks))
                    <span class="text-muted">No client data available</span>
                @else
                    <span>
                        <span class="font-bold">Clients used:</span>
                        <span>{!! implode(', ', $userAgentLinks) !!}</span>
                    </span>
                @endif

                    <button id="unlockHistoryButton" class="absolute bottom-0 right-0 btn"
                            onclick="toggleExpander('unlockHistoryButton', 'unlockHistoryContent')">Unlock History ▼</button>
                </div>

                <div id="unlockHistoryContent" class="hidden devboxcontainer">
                    @if (empty($history))
                        {{ $ticket->reporter->User }} has not earned any achievements for this game.
                    @else
                        <table class="do-not-highlight">
                            <tbody>
                                @foreach ($history as $event)
                                    <tr>
                                        <td style="width: 15%">
                                            <span>&nbsp;</span>
                                            <span class="smalldate">{{ $event['when']->format("Y-m-d H:i:s") }}</span>
                                        </td>
                                        <td style="width: 85%">
                                            @if ($event['type'] === PlayerGameActivityEventType::Unlock)
                                                @php $achievement = $event['achievement'] @endphp
                                                {!! achievementAvatar($achievement) !!}
                                                @if ($event['unlocker'] ?? null)
                                                    (unlocked by {!! userAvatar($event['unlocker'], label:true, icon:false) !!})
                                                @endif
                                                @if ($achievement['ID'] === $ticket->achievement->id)
                                                    (reported achievement)
                                                @endif
                                            @else
                                                Ticket created - {{ $ticketType }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="flex mt-2 w-full justify-between border-embed-highlight items-center">
                    @if ($existingUnlock)
                        @php $unlockedAt = $existingUnlock->unlocked_hardcore_at ?? $existingUnlock->unlocked_at; @endphp
                        @if ($existingUnlock->unlocker_id)
                            <span>Manually unlocked by {!! userAvatar(User::firstWhere('id', $existingUnlock->unlocker_id), icon:false) !!} at {{ getNiceDate($unlockedAt->unix()) }}</span>
                        @else
                            {{ $ticket->reporter->User }} earned this achievement at 
                            {{ getNiceDate($unlockedAt->unix()) }}
                            @if ($unlockedAt > $ticket->ReportedAt)
                                (after the report)
                            @else
                                (before the report)
                            @endif
                        @endif
                    @else
                        <div class="flex w-full justify-between border-embed-highlight items-center">
                            {{ $ticket->reporter->User }} did not earn this achievement
                            @if ($permissions >= Permissions::Moderator)
                                <script>
                                    function AwardManually(hardcore) {
                                        showStatusMessage('Awarding...');

                                        $.post('/request/user/award-achievement.php', {
                                            user: '{{ $ticket->reporter->User }}',
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
                                    @if ($ticket->Hardcore)
                                    <button class="btn" onclick="AwardManually(1)">Award Hardcore</button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="mt-2">
        <div class="flex w-full justify-between border-embed-highlight items-center">
            <p role="heading" aria-level="2" class="mb-0.5 text-2xs font-bold">
                Comments
            </p>
            @if ($permissions >= Permissions::Developer)
                <div class="flex flex-col gap-y-1">
                    <a class="btn py-2 block transition-transform lg:active:scale-[97%]" href="{!! $contactReporterUrl !!}">
                        Contact the reporter
                    </a>
                </div>
            @endif
        </div>

        <div class="commentscomponent">
            <table id="feed" class="table-highlight"><tbody>
                <x-comment.item
                    :author="$ticket->reporter"
                    :when="$ticket->ReportedAt"
                    :payload="$ticketNotes"
                />
                @php $numArticleComments = getRecentArticleComments(ArticleType::AchievementTicket, $ticket->ID, $commentData) @endphp
                @php $allowDelete = $permissions >= Permissions::Moderator @endphp
                @foreach ($commentData as $comment)
                    @php
                        $when = Carbon::createFromTimestamp($comment['Submitted']);
                        $commentUser = 
                            ($comment['User'] === $ticket->reporter->User) ? $ticket->reporter :
                            (($comment['User'] === $user->User) ? $user :
                                User::firstWhere('User', $comment['User']));
                    @endphp
                    <x-comment.item
                        :author="$commentUser"
                        :when="$when"
                        :payload="$comment['CommentPayload']"
                        articleType="{{ ArticleType::AchievementTicket }}"
                        :articleId="$ticket->ID"
                        :commentId="$comment['ID']"
                        :allowDelete="$allowDelete"
                    />
                @endforeach

                @if (isset($user) && !$user->isMuted)
                    {!! RenderCommentInputRow($user->User, ArticleType::AchievementTicket, $ticket->ID) !!}
                @endif
            </tbody></table>
        </div>
    </div>

    <div>
        <form method="post" action="/request/ticket/update.php">
            {!! csrf_field() !!}
            <input type="hidden" name="ticket" value="{{ $ticket->ID }}">
            @if ($permissions >= Permissions::Developer)
                @if (TicketState::isOpen($ticket->ReportState))
                    <select name="action" required>
                        <option value="" disabled selected hidden>Choose an action...</option>
                        <option value="{{ TicketAction::Resolved }}">Resolve as fixed (add comments about your fix above)</option>
                        @if ($ticket->ReportState === TicketState::Open)
                            @php
                                $lastComment = null;
                                foreach ($commentData as $comment) {
                                    if ($comment['User'] != 'Server') {
                                        $lastComment = $comment;
                                    }
                                }
                            @endphp
                            @if ($lastComment != null && ($lastComment['User'] === $user->User || $lastComment['User'] === $ticket->achievement->Author))
                                <option value="{{ TicketAction::Request }}">Transfer to reporter - {{ $ticket->reporter->User }}</option>
                            @endif
                        @else
                            <option value="{{ TicketAction::Reopen }}">Transfer to author - {{ $ticket->achievement->Author }}</option>
                        @endif
                        <option value="{{ TicketAction::Demoted }}">Demote achievement to Unofficial</option>
                        <option value="{{ TicketAction::Network }}">Close - Network problems</option>
                        <option value="{{ TicketAction::NotEnoughInfo }}">Close - Not enough information</option>
                        <option value="{{ TicketAction::WrongRom }}">Close - Wrong ROM</option>
                        <option value="{{ TicketAction::UnableToReproduce }}">Close - Unable to reproduce</option>
                        <option value="{{ TicketAction::ClosedMistaken }}">Close - Mistaken report</option>
                        <option value="{{ TicketAction::ClosedOther }}">Close - Another reason (add comments above)</option>
                    </select>
                    <button class='btn'>Perform action</button>           
                @else
                    <input type="hidden" name="action" value="{{ TicketAction::Reopen }}">
                    <button class='btn'>Reopen this ticket</button>
                @endif
            @elseif ($user?->id === $ticket->reporter->id)
                @if (TicketState::isOpen($ticket->ReportState))
                    <input type="hidden" name="action" value="{{ TicketAction::ClosedMistaken }}">
                    <button class='btn'>Close as mistaken report</button>
                @endif
            @endif
        </form>

        @if ($permissions >= Permissions::JuniorDeveloper)
            <div class="mt-4 w-full relative flex gap-x-3">
                <button id="achievementLogicButton" class="btn"
                        onclick="toggleExpander('achievementLogicButton', 'achievementLogicContent')">Achievement Logic ▼</button>
            </div>

            <div id="achievementLogicContent" class="hidden devboxcontainer">
                <div style='clear:both;'></div>
                <li>Achievement ID: {{ $ticket->achievement->id }}</li>
                <div>
                    <li>Mem:</li>
                    <code>{{ $ticket->achievement->MemAddr }}</code>
                    <li>Mem explained:</li>

                    @php
                        $triggerDecoderService = new TriggerDecoderService();
                        $groups = $triggerDecoderService->decode($ticket->achievement->MemAddr);
                        $triggerDecoderService->addCodeNotes($groups, $ticket->achievement->GameID);
                    @endphp
                    <x-trigger.viewer :groups="$groups" />
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
