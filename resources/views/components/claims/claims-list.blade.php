@props([
    'claims' => [],
    'totalClaims' => 0,
    'numFilteredClaims' => 0,
    'offset' => 0,
    'currentPage' => 0,
    'totalPages' => 0,
    'columns' => [],
])

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
                                @php $value = $column['value']($claim) @endphp
                                <td class="py-2">
                                    @if ($column['type'] === 'text')
                                        {{ $value }}
                                    @elseif ($column['type'] === 'date')
                                        <span class="smalldate whitespace-nowrap">{{ $value }}</span>
                                    @elseif ($column['type'] === 'game')
                                        <x-game.multiline-avatar
                                            :gameId="$value->id"
                                            :gameTitle="$value->title"
                                            :gameImageIcon="$value->ImageIcon"
                                            :consoleName="$value->system->name"
                                        />
                                    @elseif ($column['type'] === 'user')
                                        {!! userAvatar($value) !!}
                                    @elseif ($column['type'] === 'expiration')
                                        @if ($value['isExpired'])
                                            <span class="text-danger">{{ $value['value'] }}</span>
                                        @else
                                            {{ $value['value'] }}
                                        @endif
                                    @endif
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
