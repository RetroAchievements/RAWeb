@props([
    'stats' => [],
])

<table class="table-highlight">
    <thead>
        <tr class="do-not-highlight">
            <th class="w-[35px] xl:max-w-auto">
                <span class="hidden lg:block xl:hidden">#</span>
                <span class="lg:hidden xl:block">Rank</span>
            </th>
            
            <th>User</th>
            
            <th class="text-right">Points</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($stats as $stat)
            @if ($stat['points_hardcore'] === 0)
                @continue
            @endif

            <tr 
                @class(['outline outline-text' => $stat['user'] === request()->user()->display_name])
            >
                {{-- this ignores ties --}}
                <td class="xl:text-center">{{ $stat['rank'] ?? $loop->iteration }}</td>

                <td>{!! userAvatar($stat['user'], iconSize: 24, iconClass: 'rounded-sm ml-1') !!}</td>

                <td>
                    <div class="flex flex-col items-end">
                        <p class="text-2xs whitespace-nowrap">
                            <span>
                                {{ localized_number($stat['points_hardcore']) }} points
                            </span>
                        </p>

                        <p class="text-2xs whitespace-nowrap">
                            <x-points-weighted-container>
                                ({{ localized_number($stat['points_weighted']) }})
                            </x-points-weighted-container>
                        </p>
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
