<?php

use App\Models\AchievementSetClaim;
use App\Platform\Services\AchievementSetClaimListService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\AchievementSetClaim::class]);
name('claims.expiring');

render(function (View $view) {
    $claimsService = new AchievementSetClaimListService();
    $claimsService->sortOrder = 'enddate';

    $selectFilters = $claimsService->getSelectFilters(showActiveStatuses: true);
    $sorts = [
        'title' => 'Game Title',
        'developer' => 'Developer',
        'enddate' => 'Expiring Soonest',
    ];
    $filterOptions = $claimsService->getFilterOptions(request());
    $claims = $claimsService->getClaims($filterOptions, AchievementSetClaim::where('Finished', '<', Carbon::now()->addDays(7)));

    $activeClaimsCount = AchievementSetClaim::activeOrInReview()->count();

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
])

<x-app-layout pageTitle="Expiring Claims">
    <div class="mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">Expiring Claims</h1>
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
        completionColumnName="Expiration Date"
        showExpirationStatus="true"
    />
</x-app-layout>
