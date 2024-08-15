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
            <x-user.avatar display="icon" :icon-size="$iconSize" :user="$masteryUser" />
            <x-user.avatar hasHref="true" :user="$masteryUser" />
        </td>

        @if ($includeTime === true)
            <td><span class="smalldate">{{ $masteryDate->format('F j Y, g:ia') }}</span></td>
        @else
            <td><span class="smalldate">{{ $masteryDate->format('F j Y') }}</span></td>
        @endif
    </tr>