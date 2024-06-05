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

    $columns = [
        $claimsService->getGameColumn(),
        $claimsService->getDeveloperColumn(),
        $claimsService->getClaimTypeColumn(),
        $claimsService->getSetTypeColumn(),
        $claimsService->getStatusColumn(),
        $claimsService->getSpecialColumn(),
        $claimsService->getClaimDateColumn(),
        $claimsService->getExpirationDateColumn(),
        $claimsService->getExpirationStatusColumn(),
    ];

    $selectFilters = [
        $claimsService->getSystemFilter(onlyValid: false),
        $claimsService->getClaimTypeFilter(),
        $claimsService->getSetTypeFilter(),
        $claimsService->getStatusFilter(),
        $claimsService->getSpecialFilter(),
        $claimsService->getDeveloperTypeFilter(),
    ];

    $sorts = $claimsService->getSorts();

    $filterOptions = $claimsService->getFilterOptions(request());
    $claims = $claimsService->getClaims($filterOptions);

    $activeClaimsCount = AchievementSetClaim::count();

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

<x-app-layout pageTitle="Claims">
    <div class="mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">Claims</h1>
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
