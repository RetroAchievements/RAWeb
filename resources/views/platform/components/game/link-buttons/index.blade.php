@props([
    'allowedLinks' => ['forum-topic', 'game-files', 'guide', 'code-notes', 'tickets', 'set-requestors', 'suggested-games'],
    'gameAchievementsCount' => 0,
    'gameForumTopicId' => null, // ?int
    'gameGuideUrl' => null, // ?string
    'gameId' => 1,
    'isViewingOfficial' => false,
    'variant' => 'stacked', // 'stacked' | 'row'
])

<?php
use App\Community\Enums\TicketFilters;
use App\Community\Models\ForumTopic;
use App\Site\Enums\Permissions;
use Illuminate\Support\Facades\Auth;

$me = Auth::user();

$doesForumTopicExist = false;
if ($gameForumTopicId) {
    $doesForumTopicExist = ForumTopic::where('ID', $gameForumTopicId)->exists();
}

$canCreateForumTopic = !$doesForumTopicExist && $me && $me->Permissions >= Permissions::Developer;

$canSeeForumLink = in_array('forum-topic', $allowedLinks);
$canSeeSupportedGameFiles = in_array('game-files', $allowedLinks) && $me && $me->Permissions >= Permissions::Registered;
$canSeeCodeNotes = in_array('code-notes', $allowedLinks) && $me && $me->Permissions >= Permissions::Registered;
$canSeeGuide = in_array('guide', $allowedLinks) && $gameGuideUrl;
$canSeeOpenTickets = in_array('tickets', $allowedLinks) && $me && $me->Permissions >= Permissions::Registered;
$canSeeSetRequestors = in_array('set-requestors', $allowedLinks) && $me && $me->Permissions >= Permissions::Registered && $gameAchievementsCount === 0;
$canSeeSuggestedGames = in_array('suggested-games', $allowedLinks) && $me && $me->Permissions >= Permissions::Registered;

if ($canSeeOpenTickets) {
    $numOpenTickets = countOpenTickets(
        !$isViewingOfficial,
        TicketFilters::Default,
        null,
        null,
        null,
        $gameId,
    );
}
?>

<ul class="flex @if ($variant === 'stacked') flex-col @endif gap-2">
    @if ($canSeeForumLink)
        @if ($doesForumTopicExist)
            <x-game.link-buttons.game-link-button
                icon="💬"
                href="{{ '/viewtopic.php?t=' . $gameForumTopicId }}"
            >
                Official Forum Topic
            </x-game.link-buttons.game-link-button>
        @elseif ($canCreateForumTopic)
            <x-game.link-buttons.create-forum-topic-button :gameId="$gameId" />
        @endif
    @endif

    @if ($canSeeGuide)
        <x-game.link-buttons.game-link-button
            icon="📖"
            href="{{ $gameGuideUrl }}"
        >
            Guide
        </x-game.link-buttons.game-link-button>
    @endif

    @if ($canSeeSupportedGameFiles)
        <x-game.link-buttons.game-link-button
            icon="💾"
            href="{{ '/linkedhashes.php?g=' . $gameId }}"
        >
            Supported Game Files
        </x-game.link-buttons.game-link-button>
    @endif

    @if ($canSeeCodeNotes)
        <x-game.link-buttons.game-link-button
            icon="📑"
            href="{{ '/codenotes.php?g=' . $gameId }}"
        >
            Code Notes
        </x-game.link-buttons.game-link-button>
    @endif

    @if ($canSeeOpenTickets)
        @if ($isViewingOfficial)
        <x-game.link-buttons.game-link-button
            icon="🎫"
            href="{{ '/ticketmanager.php?g=' . $gameId }}"
        >
            Open Tickets ({{ $numOpenTickets }})
        </x-game.link-buttons.game-link-button>
        @else
        <x-game.link-buttons.game-link-button
            icon="🎫"
            href="{!! '/ticketmanager.php?g=' . $gameId . '&f=5' !!}"
        >
            Open Unofficial Tickets ({{ $numOpenTickets }})
        </x-game.link-buttons.game-link-button>
        @endif
    @endif

    @if ($canSeeSetRequestors)
        <x-game.link-buttons.game-link-button
            icon="📜"
            href="{{ '/setRequestors.php?g=' . $gameId }}"
        >
            Set Requestors
        </x-game.link-buttons.game-link-button>
    @endif

    @if ($canSeeSuggestedGames)
        <x-game.link-buttons.game-link-button
            icon="🕹️"
            href="{{ route('game.suggest-for-game', $gameId) }}"
        >
            Find Something Similar to Play
        </x-game.link-buttons.game-link-button>
    @endif
</ul>
