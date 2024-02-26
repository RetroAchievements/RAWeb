@props([
    'isUserOnCurrentPage' => false,
    'paginator' => null,
    'targetUserRankingData' => null, // always null if unauthenticated, no user filter, or no rank
])

<div class="sm:hidden flex flex-col gap-y-1">
    @foreach ($paginator as $paginatedRow)
        <x-beaten-games-leaderboard.leaderboard-card-row :paginatedRow="$paginatedRow" />
    @endforeach

    @if ($targetUserRankingData && $targetUserRankingData['userRank'] && !$isUserOnCurrentPage)
        <x-beaten-games-leaderboard.leaderboard-card-row
            :isHighlighted="true"
            :paginatedRow="$targetUserRankingData['userRankingData']"
            :rank="$targetUserRankingData['userRank']"
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
            <x-beaten-games-leaderboard.leaderboard-table-row :paginatedRow="$paginatedRow" />
        @endforeach

        @if ($targetUserRankingData && $targetUserRankingData['userRank'] && !$isUserOnCurrentPage)
            <tr class="do-not-highlight"><td colspan="5">&nbsp;</td></tr>

            <x-beaten-games-leaderboard.leaderboard-table-row
                :isHighlighted="true"
                :paginatedRow="$targetUserRankingData['userRankingData']"
                :rank="$targetUserRankingData['userRank']"
            />
        @endif
    </tbody>
</table>
