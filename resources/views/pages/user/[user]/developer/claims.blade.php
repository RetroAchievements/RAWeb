<?php

use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\User;
use App\Platform\Services\AchievementSetClaimListService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:viewAny,' . App\Models\AchievementSetClaim::class, 'can:view,user']);
name('developer.claims');

render(function (View $view, User $user) {
    $claimsService = new AchievementSetClaimListService();
    $claimsService->mergeActiveStatuses = true;
    $claimsService->sortOrder = '-enddate';

    $selectFilters = array_filter($claimsService->getSelectFilters(), function ($filter) {
        return ($filter['kind'] !== 'developerType');
    });
    $sorts = [
        'title' => 'Game Title',
        '-enddate' => 'Latest Completion/Expiration Date',
    ];

    if ($user->achievementSetClaims()->activeOrInReview()->exists()) {
        $sorts['expiring'] = 'Expiring Soonest';
    }

    $filterOptions = $claimsService->getFilterOptions(request());
    if ($filterOptions['type'] === ClaimType::Primary) { // default claim type is primary. if not provided, we want all
        if (!Request::exists('filter.type')) {
            $filterOptions['type'] = -1; // All
        }
    }
    $claims = $claimsService->getClaims($filterOptions, $user->achievementSetClaims()->getQuery());

    $userClaimsCount = $user->achievementSetClaims()->count();

    return $view->with([
        'user' => $user,
        'claims' => $claims,
        'availableSelectFilters' => $selectFilters,
        'availableSorts' => $sorts,
        'filterOptions' => $filterOptions,
        'sortOrder' => $claimsService->sortOrder,
        'numFilteredClaims' => $claimsService->numFilteredClaims,
        'currentPage' => $claimsService->pageNumber,
        'totalPages' => $claimsService->totalPages,
        'userClaimsCount' => $userClaimsCount,
    ]);
});

?>

@props([
    'user' => null, // User
    'claims' => null, // Collection<int, AchievementSetClaim>
    'availableSelectFilters' => [],
    'availableSorts' => [],
    'filterOptions' => [],
    'sortOrder' => null,
    'numFilteredClaims' => 0,
    'currentPage' => 1,
    'totalPages' => 1,
    'userClaimsCount' => 0,
])

<x-app-layout pageTitle="{{ $user->display_name }}'s Claims">
    <div class="mb-1 w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">{{ $user->display_name }}'s Claims</h1>
    </div>

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
        showDeveloper="{{ false }}"
        showExpirationStatus="{{ true }}"
    />
</x-app-layout>
