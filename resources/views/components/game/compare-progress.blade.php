<?php
use App\Models\PlayerGame;
use App\Models\User;
?>

@props([
    'game' => null,
    'user' => null,
])

<?php
$followedUserIds = $user->following()->select(['UserAccounts.ID', 'UserAccounts.User'])->pluck('ID');

$followedUserCompletion = null;
if (!empty($followedUserIds)) {
    $fields = [
        'user_id',
        'achievements_unlocked',
        'achievements_unlocked_hardcore',
        'achievements_total',
        'points',
        'points_hardcore',
        'points_total',
        'last_played_at',
    ];

    $followedUserCompletion = PlayerGame::where('game_id', $game->ID)
        ->whereIn('user_id', $followedUserIds)
        ->where(function ($query) {
            $query->where('achievements_unlocked', '>', 0)
                ->orWhere('achievements_unlocked_hardcore', '>', 0);
        })
        ->select($fields)
        ->orderBy('achievements_unlocked_hardcore', 'DESC')
        ->orderBy('achievements_unlocked', 'DESC')
        ->get()
        ->toArray();
}

$placeholderUser = new User();
$placeholderUser->User = '[user]';
$placeholderUrl = route('game.compare-unlocks', ['game' => $game, 'user' => $placeholderUser]);
// NOTE: placeholderUrl will be url encoded (i.e '[user]' => '%5Buser%5D')
?>

<script>
jQuery(document).ready(function onReady($) {
    const $searchBoxCompareuser = $(".searchboxgamecompareuser");
    $searchBoxCompareuser.autocomplete({
        source(request, response) {
            request.source = "game-compare";
            $.post("/request/search.php", request).done(function (data) {
                response(data);
            });
        },
        minLength: 2,
    });
    $searchBoxCompareuser.autocomplete({
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        select(event, ui) {
            return false;
        },
    });
    $searchBoxCompareuser.on("autocompleteselect", function (event, ui) {
        const placeholderUrl = "{!! $placeholderUrl !!}";
        window.location = placeholderUrl.replace("%5Buser%5D", ui.item.label);

        return false;
    });
});

function selectSearchBoxUser() {
    const $searchBoxCompareuser = $(".searchboxgamecompareuser");
    const placeholderUrl = "{!! $placeholderUrl !!}";
    window.location = placeholderUrl.replace(
        "%5Buser%5D",
        $searchBoxCompareuser.val()
    );
}
</script>

<div id="gamecompare" class="component">
    <h2 class="text-h3">Compare Progress</h2>
    <div class="nicebox flex flex-col gap-y-2">
        @if ($followedUserCompletion === null)
            <p>RetroAchievements is a lot more fun with others.</p>
            @if ($user === null)
                <a href="/createaccount.php">Create an account</a> or login and start earning achievements today!<br>
            @else
                <p>Find users to follow <a href="/userList.php">here</a>!</p>
            @endif
        @elseif (empty($followedUserCompletion))
            <p>None of your followed users have unlocked any achievements for this game.</p>
        @else
            <p>Compare to a followed user:</p>
            <div class="">
                @foreach ($followedUserCompletion as $completion)
                    @php
                        $friend = User::find($completion['user_id']);
                    @endphp
                    <div class="odd:bg-embed hover:bg-embed-highlight border border-transparent hover:border-[rgba(128,128,128,.3)] p-1 flex items-center justify-between w-full">
                        <div>
                            {!! userAvatar($friend, iconSize: 20) !!}
                        </div>

                        <a
                            class="min-w-[100px] flex flex-col"
                            href="{!! route('game.compare-unlocks', ['game' => $game, 'user' => $friend->User]) !!}"
                        >
                            <x-game-progress-bar
                                containerClassNames="py-2.5"
                                :softcoreProgress="$completion['achievements_unlocked']"
                                :hardcoreProgress="$completion['achievements_unlocked_hardcore']"
                                :maxProgress="$completion['achievements_total']"
                            />
                        </a>
                    </div>
                @endforeach
            </table>
        @endif

        <div>
            <p>Compare with any user:</p>
            <div class="w-full flex items-center gap-x-2">
                <input name="compareuser" type="text" class="searchboxgamecompareuser w-full" placeholder="Enter User..." />
                <button class="btn" onclick="selectSearchBoxUser()">Select</button>
            </div>
        </div>
    </div>
</div>
