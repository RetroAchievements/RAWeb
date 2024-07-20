<?php

use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\TicketState;
use App\Enums\Permissions;
use Illuminate\Support\Facades\Auth;

?>

@props([
    'claimData' => [],
    'consoleId' => 0,
    'forumTopicId' => null,
    'gameId' => 0,
    'gameTitle' => 'Unknown game',
    'isOfficial' => true,
    'isSoleAuthor' => false,
    'numAchievements' => 0,
    'user' => null, // ?User
])

<?php
$allClaimFilters = ClaimFilters::AllFilters;
$primaryClaimId = 0;
$primaryClaimStatus = ClaimStatus::Active;
$primaryClaimUser = null;
$primaryClaimMinutesActive = 0;
$primaryClaimMinutesLeft = 0;
$hasGameClaimed = false;
$isSoleAuthor = false;

$userClaimCount = 0;
$userHasClaimSlot = false;
$openTickets = null;

$userPermissions = $user?->getAttribute('Permissions') ?? Permissions::Unregistered;

// Get user claim data.
if (isset($user) && $userPermissions >= Permissions::JuniorDeveloper) {
    $userClaimCount = getActiveClaimCount($user, false, false);
    $userHasClaimSlot = $userClaimCount < permissionsToClaim($userPermissions);
    $openTickets = countOpenTicketsByDev($user);
}

$claimListLength = count($claimData);

// Get the first entry returned for the primary claim data.
if ($claimListLength > 0 && $claimData[0]['ClaimType'] == ClaimType::Primary) {
    $primaryClaimId = $claimData[0]['ID'];
    $primaryClaimUser = $claimData[0]['User'];
    $primaryClaimMinutesActive = $claimData[0]['MinutesActive'];
    $primaryClaimMinutesLeft = $claimData[0]['MinutesLeft'];
    $primaryClaimStatus = $claimData[0]['Status'];

    foreach ($claimData as $claim) {
        if (isset($claim['User']) && $claim['User'] === $user?->User) {
            $hasGameClaimed = true;
        }
    }
}

$claimType = $claimListLength > 0 && (!$hasGameClaimed || $primaryClaimUser !== $user?->User) ? ClaimType::Collaboration : ClaimType::Primary;
$isCollaboration = $claimType === ClaimType::Collaboration;
$claimSetType = $numAchievements > 0 ? ClaimSetType::Revision : ClaimSetType::NewSet;
$isRevision = $claimSetType === ClaimSetType::Revision;
$hasOpenTickets = $openTickets[TicketState::Open] > 0;
$createTopic = !$isRevision && $userPermissions >= Permissions::Developer && !isset($forumTopicId);
$claimBlockedByMissingForumTopic = !$isRevision && $userPermissions == Permissions::JuniorDeveloper && !isset($forumTopicId);

// User has an open claim or is claiming own set or is making a collaboration claim and missing forum topic is not blocking
$canClaim = ($userHasClaimSlot || $isSoleAuthor || $isCollaboration) && !$hasGameClaimed && !$claimBlockedByMissingForumTopic;

$revisionDialogFlag = $isRevision && !$isSoleAuthor;
$ticketDialogFlag = $hasOpenTickets;
$isRecentPrimaryClaim = $primaryClaimMinutesActive <= 1440;
?>

<script>
function makeClaim() {
    const gameTitle = "{!! html_entity_decode($gameTitle) !!}";
    const hasRevisionFlag = {{ (int) $revisionDialogFlag }};
    const hasTicketFlag = {{ (int) $ticketDialogFlag}};

    let revisionMessage = '';
    if (hasRevisionFlag) {
        revisionMessage = 'Please ensure a revision plan has been posted and approved before making this claim.\n\n';
    }

    let ticketMessage = '';
    if (hasTicketFlag) {
        ticketMessage = 'Please ensure any open tickets have been addressed before making this claim.\n\n';
    }

    const message = revisionMessage + ticketMessage + 'Are you sure you want to claim ' + gameTitle + '?';
    return confirm(message);
}

function dropClaim() {
    const gameTitle = "{!! html_entity_decode($gameTitle) !!}";

    const message = 'Are you sure you want to drop the claim for ' + gameTitle + '?';
    return confirm(message);
}

function reviewClaim() {
    const gameTitle = "{!! html_entity_decode($gameTitle) !!}";

    const message = 'Are you sure you want to change the claim status for ' + gameTitle + ' to In Review?';
    return confirm(message);
}

function activateClaim() {
    const gameTitle = "{!! html_entity_decode($gameTitle) !!}";

    const message = 'Are you sure you want to change the claim status for ' + gameTitle + ' to Active?';
    return confirm(message);
}

function extendClaim() {
    const gameTitle = "{!! html_entity_decode($gameTitle) !!}";

    const message = 'Are you sure you want to extend the claim for ' + gameTitle + '?';
    return confirm(message);
}

function completeClaim() {
    const gameTitle = "{!! html_entity_decode($gameTitle) !!}";
    const showEarlyReleaseWarning = {{ (int) $isRecentPrimaryClaim }};

    let earlyReleaseMessage = '';
    if (showEarlyReleaseWarning) {
        earlyReleaseMessage = 'Please ensure you have approval to complete this claim with 24 hours of the claim being made.\n\n';
    }

    let message = earlyReleaseMessage + 'This will inform all set requestors that new achievements have been added.\n\n';
    message += 'Are you sure you want to complete the claim for ' + gameTitle + '?';
    return confirm(message);
}
</script>

@if ($canClaim)
    <form
        action="/request/set-claim/make-claim.php"
        method="post"
        onsubmit="return makeClaim()"
    >
        {!! csrf_field() !!}
        <input type="hidden" name="game" value="{{ $gameId }}">
        <input type="hidden" name="claim_type" value="{{ $claimType }}">
        <input type="hidden" name="set_type" value="{{ $claimSetType }}">
        @if ($createTopic)
            <input type="hidden" name="create_topic" value="1">
        @endif
        <button class="btn">
            Make
            {{ ClaimSetType::toString($claimSetType) }}
            {{ ClaimType::toString($claimType) }}
            Claim
            @if ($createTopic)
                and Forum Topic
            @endif
        </button>
    </form>

@elseif ($hasGameClaimed)
    @if ($primaryClaimUser === $user?->User && $primaryClaimMinutesLeft <= 10080)
        <form
            action="/request/set-claim/extend-claim.php"
            method="post"
            onsubmit="return extendClaim()"
        >
            {!! csrf_field() !!}
            <input type="hidden" name="game" value="{{ $gameId }}">
            <button class="btn">Extend Claim</button>
        </form>
    @endif

    @if ($primaryClaimStatus === ClaimStatus::InReview && $primaryClaimUser === $user?->User)
        <div class="ml-2">Cannot Drop Claim while In Review</div>
    @else
        <form
            class="mb-1"
            action="/request/set-claim/drop-claim.php"
            method="post"
            onsubmit="return dropClaim()"
        >
            {!! csrf_field() !!}
            <input type="hidden" name="game" value="{{ $gameId }}">
            <input type="hidden" name="claim_type" value="{{ $claimType }}">
            <input type="hidden" name="set_type" value="{{ $claimSetType }}">
            <button class="btn">Drop {{ ClaimType::toString($claimType) }} Claim</button>
        </form>
    @endif

@elseif (!$userHasClaimSlot)
    <div class="ml-2">Maximum number of games claimed</div>

@elseif ($claimBlockedByMissingForumTopic)
    <div class="ml-2">Forum Topic Needed for Claim</div>

@endif

<!-- If the set has achievements and the current user is the primary claim owner, then allow completing the claim. -->
@if ($primaryClaimStatus !== ClaimStatus::InReview && $user?->User === $primaryClaimUser && $numAchievements > 0)
    <!-- For valid consoles, only allow completing if core achievements exist. -->
    <!-- For rollout consoles, achievements can't be pushed to core, so don't restrict completing. -->
    @if (isValidConsoleId($consoleId) && !$isOfficial)
        <p class='ml-2'>Cannot Complete Claim from Unofficial</p>
    @else
        <form
            action="/request/set-claim/complete-claim.php"
            method="post"
            onsubmit="return completeClaim()"
        >
            {!! csrf_field() !!}
            <input type="hidden" name="game" value="{{ $gameId }}">
            <button class="btn">Complete Claim</button>
            @if ($isRecentPrimaryClaim)
                <a
                    href="https://docs.retroachievements.org/guidelines/developers/claims-system.html#how-to-complete-a-claim"
                    target="_blank"
                    rel="noreferrer"
                    class="ml-3 text-danger underline"
                    title="You made a claim on this game within the last 24 hours."
                >
                    Within 24 Hours of Claim!
                </a>
            @endif
        </form>
    @endif
@endif

<!-- If the author is a jr. dev and the current user is a full dev, allow the set to be changed to Review status -->
@if ($primaryClaimUser && $userPermissions >= Permissions::Moderator && $primaryClaimUser !== $user?->User)
    <?php $primaryClaimUserPermissions = getUserPermissions($primaryClaimUser); ?>
    @if ($primaryClaimUserPermissions < Permissions::Developer)
        @if ($primaryClaimStatus !== ClaimStatus::InReview)
            <form
                class="mb-1"
                action="/request/set-claim/update-claim-status.php"
                method="post"
                onsubmit="return reviewClaim()"
            >
                {!! csrf_field() !!}
                <input type="hidden" name="game" value="{{ $gameId }}">
                <input type="hidden" name="claim" value="{{ $primaryClaimId }}">
                <input type="hidden" name="claim_status" value="{{ ClaimStatus::InReview }}">
                <button class="btn">Mark Claim for Review</button>
            </form>
        @else
            <form
                class="mb-1"
                action="/request/set-claim/update-claim-status.php"
                method="post"
                onsubmit="return activateClaim()"
            >
                {!! csrf_field() !!}
                <input type="hidden" name="claim" value="{{ $primaryClaimId }}">
                <input type="hidden" name="claim_status" value="{{ ClaimStatus::Active }}">
                <button class="btn">Complete Claim Review</button>
            </form>
        @endif
    @endif
@endif

<a class="btn btn-link" href="{{ route('game.claims', ['game' => $gameId]) }}">Claim History</a>
