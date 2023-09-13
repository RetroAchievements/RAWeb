@props([
    'isUserOnCurrentPage' => null,
    'myRankingData' => null, // always null if unauthenticated or no rank
    'myUsername' => null,
    'paginator' => null,
])

<div class="sm:hidden flex flex-col gap-y-1">
    @foreach ($paginator as $paginatedRow)
        <x-beaten-games-leaderboard.leaderboard-card-row
            :myUsername="$myUsername"
            :paginatedRow="$paginatedRow"
        />
    @endforeach

    @if ($myRankingData && !$isUserOnCurrentPage)
        <x-beaten-games-leaderboard.leaderboard-card-row
            :isHighlighted="true"
            :paginatedRow="$myRankingData['userRankingData']"
            :rank="$myRankingData['userRank']"
        />
    @endif
</div>

<table class="table-highlight hidden sm:table">
    <thead>
        <tr class="do-not-highlight">
            <th width="50px">Rank</th>
            <th>User</th>
            <th>Most Recent Game Beaten</th>
            <th>Beaten When</th>
            <th class='text-right'>Games Beaten</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($paginator as $paginatedRow)
            <x-beaten-games-leaderboard.leaderboard-table-row
                :myUsername="$myUsername"
                :paginatedRow="$paginatedRow"
            />
        @endforeach

        @if ($myRankingData && !$isUserOnCurrentPage)
            <tr class="do-not-highlight"><td colspan="5">&nbsp;</td></tr>

            <x-beaten-games-leaderboard.leaderboard-table-row
                :isHighlighted="true"
                :paginatedRow="$myRankingData['userRankingData']"
                :rank="$myRankingData['userRank']"
            />
        @endif
    </tbody>
</table>
