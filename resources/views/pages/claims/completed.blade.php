<?php

use App\Models\AchievementSetClaim;
use App\Platform\Services\AchievementSetClaimListService;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\AchievementSetClaim::class]);
name('claims.completed');

render(function (View $view) {
    $claimsService = new AchievementSetClaimListService();
    $claimsService->sortOrder = '-enddate';

    $selectFilters = array_filter($claimsService->getSelectFilters(), function ($filter) {
        return ($filter['kind'] === 'setType');
    });
    $filterOptions = $claimsService->getFilterOptions(request());
    if ($filterOptions['status'] === 'active') { // default status is active, which doesn't apply to this view
        $filterOptions['status'] = 'complete';
    }
    $claims = $claimsService->getClaims($filterOptions, AchievementSetClaim::primaryClaim()->complete());

    $completedClaimsCount = AchievementSetClaim::primaryClaim()->complete()->count();

    return $view->with([
        'claims' => $claims,
        'availableSelectFilters' => $selectFilters,
        'filterOptions' => $filterOptions,
        'numFilteredClaims' => $claimsService->numFilteredClaims,
        'currentPage' => $claimsService->pageNumber,
        'totalPages' => $claimsService->totalPages,
        'completedClaimsCount' => $completedClaimsCount,
    ]);
});

?>

@props([
    'claims' => null, // Collection<int, AchievementSetClaim>
    'availableSelectFilters' => [],
    'filterOptions' => [],
    'numFilteredClaims' => 0,
    'currentPage' => 1,
    'totalPages' => 1,
    'completedClaimsCount' => 0,
])

<x-app-layout pageTitle="New Sets & Revisions">
    <div class="mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">New Sets & Revisions</h1>
    </div>

    <x-meta-panel
        :availableSelectFilters="$availableSelectFilters"
        :filterOptions="$filterOptions"
    />

    <x-claims.claims-list
        :claims="$claims"
        :totalClaims="$completedClaimsCount"
        :numFilteredClaims="$numFilteredClaims"
        :currentPage="$currentPage"
        :totalPages="$totalPages"
        completionColumnName="Finished"
        completedOnly="{{ true }}"
    />
</x-app-layout>
