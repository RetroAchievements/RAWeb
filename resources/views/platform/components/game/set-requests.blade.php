@props([
    'gameId' => 0,
    'user' => null,
])

<?php
use App\Community\Enums\UserGameListType;

$gameRequests = getSetRequestCount($gameId);
$userRequests = getUserRequestsInformation($user, $gameId);
$userRequestsRemaining = $userRequests['remaining'];

$buttonText = 'Request Set';
$onClick = " onclick=\"submitSetRequest('{$user->User}', $gameId)\"";

if ($userRequests['requestedThisGame']) {
    $buttonText = 'Withdraw Request';
} else if ($userRequestsRemaining <= 0) {
    $buttonText = 'No Request Remaining';
    $onClick = '';
}

?>

@if (!empty($onClick))
<script>

function getSetRequestInformation(user, gameID) {
    $.post('/request/user-game-list/set-requests.php', {
        game: gameID,
        user: user,
    })
        .done(function (results) {
            var remaining = parseInt(results.remaining);
            var gameTotal = parseInt(results.gameRequests);
            var thisGame = results.requestedThisGame;

            $('.gameRequestsLabel').html('Set Requests: <a href=\'/setRequestors.php?g=' + gameID + '\'>' + gameTotal + '</a>');
            $('.userRequestsLabel').html('User Requests Remaining: <a href=\'/setRequestList.php?u=' + user + '\'>' + remaining + '</a>');

            var $requestButton = $('.setRequestLabel');
            if (thisGame != 0) {
                $requestButton.text('Withdraw Request');
            } else if (remaining <= 0) {
                $requestButton.text('No Requests Remaining');
            } else {
                $requestButton.text('Request Set');
            }
        });
}

function submitSetRequest(user, gameID) {
    $.post('/request/user-game-list/toggle.php', {
        game: gameID,
        type: '<?= UserGameListType::AchievementSetRequest ?>'
    })
        .done(function () {
            getSetRequestInformation('<?= $user->User ?>', <?= $gameId ?>);
        });
}

</script>
@endif

<div>
    <h2 class='text-h4'>Set Requests</h2>
    <div class='gameRequestsLabel'>Set Requests: <a href='/setRequestors.php?g={{ $gameId }}'>{{ $gameRequests }}</a></div>
    <div><button type='button' class='btn setRequestLabel'{!! $onClick !!}>{{ $buttonText }}</button></div>
    <div class='userRequestsLabel'>User Requests Remaining: <a href='/setRequestList.php?u={{ $user->User }}'>{{ $userRequestsRemaining }}</div>
</div>
