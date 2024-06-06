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
    $claimsService->sortOrder = '-expiring';

    $columns = [
        $claimsService->getGameColumn(),
        $claimsService->getDeveloperColumn(),
        $claimsService->getClaimTypeColumn(),
        $claimsService->getSetTypeColumn(),
        $claimsService->getSpecialColumn(),
        $claimsService->getClaimDateColumn(),
        $claimsService->getExpirationDateColumn(),
        $claimsService->getExpirationStatusColumn(),
    ];

    $selectFilters = [
        $claimsService->getClaimTypeFilter(),
        $claimsService->getSetTypeFilter(),
        $claimsService->getSpecialFilter(),
        $claimsService->getDeveloperTypeFilter(),
    ];

    $sorts = $claimsService->getSorts();

    $expiringClaims = AchievementSetClaim::primaryClaim()
        ->activeOrInReview()
        ->where('Finished', '<', Carbon::now()->addDays(7));
    $expiringClaimsCount = $expiringClaims->count();

    $filterOptions = $claimsService->getFilterOptions(request());
    $claims = $claimsService->getClaims($filterOptions, $expiringClaims);

    return $view->with([
        'claims' => $claims,
        'availableSelectFilters' => $selectFilters,
        'availableSorts' => $sorts,
        'filterOptions' => $filterOptions,
        'sortOrder' => $claimsService->sortOrder,
        'numFilteredClaims' => $claimsService->numFilteredClaims,
        'currentPage' => $claimsService->pageNumber,
        'totalPages' => $claimsService->totalPages,
        'expiringClaimsCount' => $expiringClaimsCount,
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
    'expiringClaimsCount' => 0,
    'columns' => [],
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
        :totalClaims="$expiringClaimsCount"
        :numFilteredClaims="$numFilteredClaims"
        :currentPage="$currentPage"
        :totalPages="$totalPages"
        :columns="$columns"
    />
</x-app-layout>
