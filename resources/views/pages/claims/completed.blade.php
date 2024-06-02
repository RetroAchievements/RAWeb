<?php

use App\Models\AchievementSetClaim;
use App\Platform\Services\AchievementSetClaimListService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\AchievementSetClaim::class]);
name('claims.completed');

render(function (View $view) {
    $claimsService = new AchievementSetClaimListService();
    $claimsService->sortOrder = '-enddate';
    $claimsService->defaultFilters['status'] = 'complete';

    $columns = [
        $claimsService->getGameColumn(),
        $claimsService->getDeveloperColumn(),
        $claimsService->getSetTypeColumn(),
        $claimsService->getFinishedDateColumn(),
    ];

    $selectFilters = [
        $claimsService->getSetTypeFilter(),
    ];

    $completedClaims = AchievementSetClaim::primaryClaim()
        ->complete()
        ->where('Finished', '>', Carbon::now()->subDays(90));
    $completedClaimsCount = $completedClaims->count();

    $filterOptions = $claimsService->getFilterOptions(request());
    $claims = $claimsService->getClaims($filterOptions, $completedClaims);

    return $view->with([
        'claims' => $claims,
        'availableSelectFilters' => $selectFilters,
        'filterOptions' => $filterOptions,
        'numFilteredClaims' => $claimsService->numFilteredClaims,
        'currentPage' => $claimsService->pageNumber,
        'totalPages' => $claimsService->totalPages,
        'completedClaimsCount' => $completedClaimsCount,
        'columns' => $columns,
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
    'columns' => [],
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
        :columns="$columns"
    />
</x-app-layout>
