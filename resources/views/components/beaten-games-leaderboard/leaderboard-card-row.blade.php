@props([
    'isHighlighted' => false,
    'paginatedRow' => null,
])

<div class="rounded bg-embed p-2 {{ $isHighlighted ? 'my-2' : '' }}" @if ($isHighlighted) style="outline: thin solid;" @endif>
    <div class="flex items-center justify-between">
        <div class="flex gap-x-3 items-center">
            <p class="text-sm">
                @if (isset($paginatedRow->rank_number))
                    #{{ localized_number($paginatedRow->rank_number) }}
                @endif
            </p>
            {!! userAvatar($paginatedRow->User, iconClass: 'rounded-sm mr-1') !!}
        </div>

        <p>{{ localized_number($paginatedRow->total_awards) }} games</p>
    </div>
</div>
