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
    $claimsService->sortOrder = '-expiring';
    $claimsService->defaultFilters['status'] = 'activeOrInReview';

    $columns = [
        $claimsService->getGameColumn(),
        $claimsService->getClaimTypeColumn(),
        $claimsService->getSetTypeColumn(),
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

    $sorts = $claimsService->getSorts(withDeveloper: false);

    $filterOptions = $claimsService->getFilterOptions(request());
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
        'columns' => $columns,
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
    'columns' => [],
])

<x-app-layout pageTitle="{{ $user->display_name }}'s Claims">
    <x-user.breadcrumbs :targetUsername="$user->User" currentPage="Claims" />

    <div class="mt-3 mb-6 w-full flex gap-x-3">
        {!! userAvatar($user, label: false, iconSize: 48, iconClass: 'rounded-sm') !!}
        <h1 class="mt-[10px] w-full">{{ $user->display_name }}'s Claims</h1>
    </div>

    @if ($userClaimsCount === 0)
        {{ $user->display_name }} hasn't made any claims.
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
