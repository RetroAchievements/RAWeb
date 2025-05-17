<?php

use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Platform\Services\AchievementSetClaimListService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\AchievementSetClaim::class, 'can:view,game']);
name('game.claims');

render(function (View $view, Game $game) {
    $claimsService = new AchievementSetClaimListService();
    $claimsService->sortOrder = '-claimdate';
    $claimsService->defaultFilters['status'] = 'all';

    $columns = [
        $claimsService->getDeveloperColumn(),
        $claimsService->getClaimTypeColumn(),
        $claimsService->getSetTypeColumn(),
        $claimsService->getStatusColumn(),
        $claimsService->getSpecialColumn(),
        $claimsService->getClaimDateColumn(),
        $claimsService->getEndDateColumn(),
        $claimsService->getExpirationStatusColumn(),
    ];

    $selectFilters = [
        $claimsService->getClaimTypeFilter(),
        $claimsService->getSetTypeFilter(),
        $claimsService->getMergedActiveStatusesFilter(),
        $claimsService->getSpecialFilter(),
    ];

    $sorts = $claimsService->getSorts(withGame: false);

    $filterOptions = $claimsService->getFilterOptions(request());
    $claims = $claimsService->getClaims($filterOptions, AchievementSetClaim::where('game_id', $game->id));

    $gameClaimsCount = AchievementSetClaim::where('game_id', $game->id)->count();

    return $view->with([
        'game' => $game,
        'claims' => $claims,
        'availableSelectFilters' => $selectFilters,
        'availableSorts' => $sorts,
        'filterOptions' => $filterOptions,
        'sortOrder' => $claimsService->sortOrder,
        'numFilteredClaims' => $claimsService->numFilteredClaims,
        'currentPage' => $claimsService->pageNumber,
        'totalPages' => $claimsService->totalPages,
        'userClaimsCount' => $gameClaimsCount,
        'columns' => $columns,
    ]);
});

?>

@props([
    'game' => null, // Game
    'claims' => null, // Collection<int, AchievementSetClaim>
    'availableSelectFilters' => [],
    'availableSorts' => [],
    'filterOptions' => [],
    'sortOrder' => null,
    'numFilteredClaims' => 0,
    'currentPage' => 1,
    'totalPages' => 1,
    'userClaimsCount' => 0,
    'columns' => [],
])

<x-app-layout pageTitle="Claim History - {{ $game->title }}">
    <x-game.breadcrumbs
        :game="$game"
        currentPageLabel="Claim History"
    />

    <div class="mt-3 w-full flex gap-x-3">
        {!! gameAvatar($game->toArray(), label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">Claim History</h1>
    </div>

    @if ($userClaimsCount === 0)
        {{ $game->title }} hasn't been claimed.
    @else
        <x-meta-panel
            :availableSelectFilters="$availableSelectFilters"
            :availableSorts="$availableSorts"
            :filterOptions="$filterOptions"
            :selectedSortOrder="$sortOrder"
        />

        <x-claims.claims-list
            :claims="$claims"
            :totalClaims="$userClaimsCount"
            :numFilteredClaims="$numFilteredClaims"
            :currentPage="$currentPage"
            :totalPages="$totalPages"
            :columns="$columns"
        />
    @endif
</x-app-layout>
