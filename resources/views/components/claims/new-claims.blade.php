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
        <div class="flex flex-col gap-y-1 sm:hidden">
            @foreach($claimData as $claim)
                <x-claims.claim-mobile-block :claim="$claim" />
            @endforeach
        </div>

        <div class="hidden sm:block">
            <table class="table-highlight mb-1">
                <thead>
                    <tr class="do-not-highlight">
                        <th>Game</th>
                        <th>Dev</th>
                        <th>Type</th>
                        <th class="whitespace-nowrap">Started</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($claimData as $claim)
                        <x-claims.claim-table-row :claim="$claim" />
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="text-right">
            <a class="btn btn-link" href="{{ route('claims.active') }}">more...</a>
        </div>
    @endif
</div>
