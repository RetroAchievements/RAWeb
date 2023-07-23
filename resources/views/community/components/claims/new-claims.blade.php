<?php

use App\Community\Enums\ClaimFilters;

$claimData = getFilteredClaims(
    claimFilter: ClaimFilters::AllActiveClaims & ~ClaimFilters::CollaborationClaim,
    limit: $count
)
?>

<div class="component">
    <h3>Sets in Progress</h3>

    @if(!$claimData || count($claimData) === 0)
        <div class="h-40 w-full flex flex-col items-center justify-center">
            <img src="assets/images/cheevo/thinking.webp" alt="No sets in progress">
            <p>Couldn't find any sets in progress.</p>
        </div>
    @else
        <div class="overflow-x-auto sm:overflow-x-hidden">
            <table class="table-highlight mb-1">
                <thead>
                    <tr class="do-not-highlight">
                        <th>Game</th>
                        <th colspan="2">Dev</th>
                        <th class="whitespace-nowrap">Started</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($claimData as $claim)
                        <x-claims.new-claim-table-row :claim="$claim" />
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-right">
            <a class="btn btn-link" href="/claimlist.php">more...</a>
        </div>
    @endif
</div>
