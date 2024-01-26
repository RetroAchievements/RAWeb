@props([
    'claims' => [],
    'label' => 'Label',
    'numAllowedClaims' => null, // ?int
])

<div>
    <p class="text-2xs mb-1">
        <span class="font-bold">{{ $label }}</span>

        @if ($numAllowedClaims !== null && count($claims) <= $numAllowedClaims)
            ({{ count($claims) }} of {{ $numAllowedClaims }} allowed)
        @endif
    </p>

    <div class="grid md:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-x-12 gap-y-1">
        @foreach ($claims as $claim)
            <div class="text-2xs">
                {!!
                    gameAvatar([
                        'ID' => $claim['GameID'],
                        'Title' => $claim['GameTitle'],
                        'ImageIcon' => $claim['GameIcon'],
                    ], iconSize: 22, iconClass: 'rounded-sm')
                !!}
            </div>
        @endforeach
    </div>
</div>
