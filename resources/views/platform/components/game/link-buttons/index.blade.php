@props([
    'allowedLinks' => ['forum-topic', 'game-files', 'guide', 'code-notes', 'tickets', 'set-requestors'],
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
if ($gameForumTopicId && in_array('forum-topic', $allowedLinks)) {
    $doesForumTopicExist = ForumTopic::where('ID', $gameForumTopicId)->exists();
}

$canCreateForumTopic = !$doesForumTopicExist && $me && $me->Permissions >= Permissions::Developer;
$canSeeSupportedGameFiles = $me && $me->Permissions >= Permissions::Registered;
$canSeeCodeNotes = $me && $me->Permissions >= Permissions::Registered;
$canSeeOpenTickets = $me && $me->Permissions >= Permissions::Registered;
$canSeeSetRequestors = $me && $me->Permissions >= Permissions::Registered && $gameAchievementsCount === 0;

$numOpenTickets = countOpenTickets(
    !$isViewingOfficial,
    TicketFilters::Default,
    null,
    null,
    null,
    $gameId,
)
?>

<ul class="flex @if ($variant === 'stacked') flex-col @endif gap-2">
    @if (in_array('forum-topic', $allowedLinks) && $doesForumTopicExist)
        <x-game.link-buttons.game-link-button
            icon="💬"
            href="{{ '/viewtopic.php?t=' . $gameForumTopicId }}"
        >
            Official Forum Topic
        </x-game.link-buttons.game-link-button>
    @endif

    @if (in_array('forum-topic', $allowedLinks) && $canCreateForumTopic)
        <x-game.link-buttons.create-forum-topic-button :gameId="$gameId" />
    @endif

    @if (in_array('guide', $allowedLinks) && $gameGuideUrl)
        <x-game.link-buttons.game-link-button
            icon="📖"
            href="{{ $gameGuideUrl }}"
        >
            Guide
        </x-game.link-buttons.game-link-button>
    @endif

    @if (in_array('game-files', $allowedLinks) && $canSeeSupportedGameFiles)
        <x-game.link-buttons.game-link-button
            icon="💾"
            href="{{ '/linkedhashes.php?g=' . $gameId }}"
        >
            Supported Game Files
        </x-game.link-buttons.game-link-button>
    @endif

    @if (in_array('code-notes', $allowedLinks) && $canSeeCodeNotes)
        <x-game.link-buttons.game-link-button
            icon="📑"
            href="{{ '/codenotes.php?g=' . $gameId }}"
        >
            Code Notes
        </x-game.link-buttons.game-link-button>
    @endif

    @if (in_array('tickets', $allowedLinks) && $canSeeOpenTickets)
        <x-game.link-buttons.game-link-button
            icon="🎫"
            href="{{ '/ticketmanager.php?g=' . $gameId }}"
        >
            Open @if (!$isViewingOfficial) Unofficial @endif Tickets ({{ $numOpenTickets }})
        </x-game.link-buttons.game-link-button>
    @endif

    @if (in_array('set-requestors', $allowedLinks) && $canSeeSetRequestors)
        <x-game.link-buttons.game-link-button
            icon="📜"
            href="{{ '/setRequestors.php?g=' . $gameId }}"
        >
            Set Requestors
        </x-game.link-buttons.game-link-button>
    @endif
</ul>
