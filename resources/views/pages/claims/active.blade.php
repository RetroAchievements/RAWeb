<?php

use App\Models\AchievementSetClaim;
use App\Platform\Services\AchievementSetClaimListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\AchievementSetClaim::class]);
name('claims.active');

render(function (View $view) {
    $claimsService = new AchievementSetClaimListService();
    $claimsService->sortOrder = '-claimdate';
    $claimsService->defaultFilters['status'] = 'activeOrInReview';

    $columns = [
        $claimsService->getGameColumn(),
        $claimsService->getDeveloperColumn(),
        $claimsService->getSetTypeColumn(),
        $claimsService->getClaimDateColumn(),
    ];

    $selectFilters = [
        $claimsService->getSetTypeFilter(),
    ];

    $sorts = $claimsService->getSorts(withExpiring: false);

    $filterOptions = $claimsService->getFilterOptions(request());
    $claims = $claimsService->getClaims($filterOptions);

    $activeClaimsCount = AchievementSetClaim::primaryClaim()->activeOrInReview()->count();

    return $view->with([
        'claims' => $claims,
        'availableSelectFilters' => $selectFilters,
        'availableSorts' => $sorts,
        'filterOptions' => $filterOptions,
        'sortOrder' => $claimsService->sortOrder,
        'numFilteredClaims' => $claimsService->numFilteredClaims,
        'currentPage' => $claimsService->pageNumber,
        'totalPages' => $claimsService->totalPages,
        'activeClaimsCount' => $activeClaimsCount,
        'columns' => $columns,
    ]);
});

?>

@props([
    'claims' => null, // Collection<int, AchievementSetClaim>
    'availableSelectFilters' => [],
    'availableSorts' => [],
    'filterOptions' => [],
    'sortOrder' => null,
    'numFilteredClaims' => 0,
    'currentPage' => 1,
    'totalPages' => 1,
    'activeClaimsCount' => 0,
    'columns' => [],
])

<x-app-layout pageTitle="Sets in Progress"
              pageDescription="A list of achievement sets currently being constructed at RetroAchievements"
>
    <div class="mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">Sets in Progress</h1>
    </div>

    <x-meta-panel
        :availableSelectFilters="$availableSelectFilters"
        :availableSorts="$availableSorts"
        :filterOptions="$filterOptions"
        :selectedSortOrder="$sortOrder"
    />

    <x-claims.claims-list
        :claims="$claims"
        :totalClaims="$activeClaimsCount"
        :numFilteredClaims="$numFilteredClaims"
        :currentPage="$currentPage"
        :totalPages="$totalPages"
        :columns="$columns"
    />
</x-app-layout>
