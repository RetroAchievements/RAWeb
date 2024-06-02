@props([
    'claims' => [],
    'totalClaims' => 0,
    'numFilteredClaims' => 0,
    'offset' => 0,
    'currentPage' => 0,
    'totalPages' => 0,
    'columns' => [],
])

@php

use App\Community\Enums\ClaimType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Models\Game;

@endphp

<div>
    <div class="w-full flex mb-2 justify-between">
        <div class="flex items-center">
            <p class="text-xs">
                Viewing
                <span class="font-bold">{{ localized_number($numFilteredClaims) }}</span>
                @if ($numFilteredClaims != $totalClaims)
                    of {{ localized_number($totalClaims) }}
                @endif
                {{ trans_choice(__('resource.claim.title'), $totalClaims) }}
            </p>
        </div>
        @if ($totalPages)
        <div class="flex items-center">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
        @endif
    </div>

    @if (!empty($claims))
        <div class="overflow-x-auto lg:overflow-x-visible">
            <table class='table-highlight mb-4'>
                <thead>
                    <tr class="do-not-highlight lg:sticky lg:top-[42px] z-[11] bg-box">
                        @foreach ($columns as $column)
                            <th>{{ $column['header'] }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($claims as $claim)
                        <tr>
                            @foreach ($columns as $column)
                                <td>
                                    {!! $column['render']($claim) !!}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($totalPages)
        <div class="w-full flex items-center justify-end">
            <x-paginator :totalPages="$totalPages" :currentPage="$currentPage" />
        </div>
    @endif
</div>
