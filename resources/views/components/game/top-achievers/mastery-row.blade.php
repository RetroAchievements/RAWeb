@props([
    'rank' => 1,
    'masteryUser' => null, // User
    'masteryDate' => null, // Carbon
    'includeTime' => true,
    'iconSize' => 'xs',
])

@if ($masteryUser->id === request()->user()->id)
    <tr style='outline: thin solid'>
@else
    <tr>
@endif

        <td class="text-right">{{ $rank }}</td>
        <td class="whitespace-nowrap">
            @if ($iconSize === 'xs')
                {!! userAvatar($masteryUser, iconSize: 16, iconClass: 'icon-xs') !!}
            @elseif ($iconSize == 'sm')
                {!! userAvatar($masteryUser, iconSize: 32, iconClass: 'icon-sm') !!}
            @endif
        </td>

        @if ($includeTime === true)
            <td><span class="smalldate">{{ $masteryDate->format('F j Y, g:ia') }}</span></td>
        @else
            <td><span class="smalldate">{{ $masteryDate->format('F j Y') }}</span></td>
        @endif
    </tr>