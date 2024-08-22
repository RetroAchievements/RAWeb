@props([
    'rank' => 1,
    'user' => null, // User
    'score' => 0,
])

@if (request()->user() && $user->id === request()->user()->id)
    <tr style='outline: thin solid'>
@else
    <tr>
@endif

        <td class="text-right">{{ $rank }}</td>
        <td class="whitespace-nowrap">
            <x-user.avatar display="icon" icon-size="xs" :user="$user" />
            <x-user.avatar hasHref="true" :user="$user" />
        </td>
        <td class="text-right">{{ localized_number($score) }}</td>
    </tr>