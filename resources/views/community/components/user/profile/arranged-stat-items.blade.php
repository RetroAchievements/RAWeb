@props([
    'stats' => [
        ['label' => 'Label A', 'value' => 0], ['label' => 'Label B', 'value' => 0],
        ['label' => 'Label C', 'value' => 1], ['label' => 'Label D', 'value' => 1],
        ['label' => 'Label E', 'value' => 2], ['label' => 'Label F', 'value' => 2],
    ]
])

<div class="grid md:grid-cols-2 gap-x-12 gap-y-1">
    <div class="flex flex-col gap-y-1">
        @foreach ($stats as $index => $stat)
            @if ($index % 2 === 0)
                <x-user.profile.stat-element
                    :href="$stat['href'] ?? null"
                    :hrefLabel="$stat['hrefLabel'] ?? null"
                    :isMuted="$stat['isMuted'] ?? false"
                    :label="$stat['label']"
                    :shouldEnableBolding="isset($stat['shouldEnableBolding']) ? $stat['shouldEnableBolding'] : true"
                    :value="$stat['value']"
                    :weightedPoints="$stat['weightedPoints'] ?? null"
                />
            @endif
        @endforeach
    </div>

    <div class="flex flex-col gap-y-1">
        @foreach ($stats as $index => $stat)
            @if ($index % 2 !== 0)
                <x-user.profile.stat-element
                    :href="$stat['href'] ?? null"
                    :hrefLabel="$stat['hrefLabel'] ?? null"
                    :isMuted="$stat['isMuted'] ?? false"
                    :label="$stat['label']"
                    :shouldEnableBolding="isset($stat['shouldEnableBolding']) ? $stat['shouldEnableBolding'] : true"
                    :value="$stat['value']"
                    :weightedPoints="$stat['weightedPoints'] ?? null"
                />
            @endif
        @endforeach
    </div>
</div>
