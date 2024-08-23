@props([
    'rank' => 1,
    'user' => null, // User
    'score' => 0,
    'maxScore' => -1,
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
        <td>
            <div class="flex items-center gap-1 float-right">
                @if ($score === $maxScore)
                    <div class="rounded-full bg-[gold] light:bg-yellow-600 w-2 h-2"></div>
                @endif
                <p>{{ localized_number($score) }}</p>
            </div>
        </td>
    </tr>