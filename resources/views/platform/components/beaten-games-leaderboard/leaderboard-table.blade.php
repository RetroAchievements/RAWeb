@props([
    'highlightedRank' => null,
    'isHighlightedRankOnCurrentPage' => null,
    'myRankingData' => null, // always null if unauthenticated or no rank
    'paginator' => null,
    'startingRank' => 0,
])

<?php
$cardRank = $startingRank;
$tableRank = $startingRank;
?>

<div class="sm:hidden flex flex-col gap-y-1">
    @foreach ($paginator as $paginatedRow)
        <x-beaten-games-leaderboard.leaderboard-card-row
            :paginatedRow="$paginatedRow"
            :rank="$cardRank"
            :isHighlighted="$isHighlightedRankOnCurrentPage && ($cardRank === $highlightedRank)"
        />

        @php
            $cardRank += 1;
        @endphp
    @endforeach

    @if ($myRankingData && !$isHighlightedRankOnCurrentPage)
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
                :paginatedRow="$paginatedRow"
                :rank="$tableRank"
                :isHighlighted="$isHighlightedRankOnCurrentPage && ($tableRank === $highlightedRank)"
            />

            @php
                $tableRank += 1;
            @endphp
        @endforeach

        @if ($myRankingData && !$isHighlightedRankOnCurrentPage)
            <tr class="do-not-highlight"><td colspan="4">&nbsp;</td></tr>

            <x-beaten-games-leaderboard.leaderboard-table-row
                :isHighlighted="true"
                :paginatedRow="$myRankingData['userRankingData']"
                :rank="$myRankingData['userRank']"
            />
        @endif
    </tbody>
</table>
