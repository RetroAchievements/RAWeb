@props([
    'claims' => [],
    'totalClaims' => 0,
    'numFilteredClaims' => 0,
    'offset' => 0,
    'currentPage' => 0,
    'totalPages' => 0,
    'completionColumnName' => 'Completion/Expiration Date',
    'completedOnly' => false,
    'showExpirationStatus' => false,
    'showDeveloper' => true,
])

@php

use App\Community\Enums\ClaimType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Models\Game;
use Illuminate\Support\Carbon;

$now = Carbon::now();

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
                        <th>Game</th>
                        @if ($showDeveloper)
                            <th>Developer</th>
                        @endif
                        @if (!$completedOnly)
                            <th>Claim Type</th>
                        @endif
                        <th class="whitespace-nowrap">Set Type</th>
                        @if (!$completedOnly)
                            <th>Status</th>
                            <th>Special</th>
                            <th class="whitespace-nowrap">Claim Date</th>
                        @endif
                        <th>{{ $completionColumnName }}</th>
                        @if ($showExpirationStatus)
                            <th>Expiration Status</th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach ($claims as $claim)
                        <tr>
                            <td>
                                <x-game.multiline-avatar
                                    :gameId="$claim->game->ID"
                                    :gameTitle="$claim->game->Title"
                                    :gameImageIcon="$claim->game->ImageIcon"
                                    :consoleName="$claim->game->system->Name"
                                />
                            </td>
                            @if ($showDeveloper)
                                <td>{!! userAvatar($claim->user) !!}</td>
                            @endif
                            @if (!$completedOnly)
                                <td>{{ ClaimType::toString($claim->ClaimType) }}</td>
                            @endif
                            <td>{{ ClaimSetType::toString($claim->SetType) }}</td>
                            @if (!$completedOnly)
                                <td>{{ ClaimStatus::toString($claim->Status) }}</td>
                                <td>{{ ClaimSpecial::toString($claim->Special) }}</td>
                                <td class="smalldate whitespace-nowrap">{{ $claim->Created ? getNiceDate($claim->Created->unix()) : 'Unknown' }}</td>
                            @endif
                            @if ($showExpirationStatus)
                                <td class="smalldate whitespace-nowrap">{{ $claim->Finished ? getNiceDate($claim->Finished->unix()) : 'Unknown' }}</td>
                                <td @if ($claim->Finished < $now) class="text-danger" @endif>
                                    @if (ClaimStatus::isActive($claim->Status))
                                        {{ $claim->Finished->diffForHumans($now, ['syntax' => Carbon::DIFF_RELATIVE_TO_NOW]) }}
                                    @endif
                                </td>
                            @else
                                <td class="smalldate whitespace-nowrap">{{ $claim->Finished ? getNiceDate($claim->Finished->unix()) : 'Unknown' }}</td>
                            @endif
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
