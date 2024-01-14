<x-card.container imgSrc="{{ $avatarUrl }}">
    <div class="relative h-full text-2xs">
        {{-- Role --}}
        @if($canShowUserRole)
            <div class="absolute top-[-14px] right-[-21px]">
                <div class="h-[25px] flex flex-col text-2xs tracking-tighter items-center justify-center pl-2 pr-5 pt-2 bg-menu-link text-box-bg rounded">
                    <!-- Spacer div due to absolute positioned parent element -->
                    <div class="mb-px"></div>
                    <p class="bottom-[8px] right-0">{{ $roleLabel }}</p>
                </div>
            </div>
        @endif

        {{-- Username --}}
        <p class="font-bold text-lg -mt-1 {{ $useExtraNamePadding ? "pt-3" : "" }}">{{ $username }}</p>

        {{-- Motto --}}
        @if (!empty($motto))
            <div class="mb-1 rounded bg-bg text-text italic p-1 text-2xs hyphens-auto">
                <p style="word-break: break-word;">{{ $motto }}</p>
            </div>
        @endif

        {{-- Points --}}
        @if($hardcorePoints > $softcorePoints)
            <x-card.info-row label="Points">
                {{ localized_number($hardcorePoints) }}
                ({{ localized_number($retroPoints) }})
            </x-card.info-row>
        @elseif($softcorePoints > 0)
            <x-card.info-row label="Softcore Points">{{ localized_number($softcorePoints) }}</x-card.info-row>
        @else
            <x-card.info-row label="Points">0</x-card.info-row>
        @endif

        {{-- Site Rank --}}
        <x-card.info-row :label="$rankLabel">
            @if($isUntracked)
                <span>Untracked</span>
            @else
                {{ $siteRank === 0 ? "Needs at least $rankMinPoints points" : "#" . localized_number($siteRank) }}
                {{ $rankPctLabel }}
            @endif
        </x-card.info-row>

        {{-- Last Activity --}}
        @if($lastActivity)
            <x-card.info-row label="Last Activity">{{ $lastActivity }}</x-card.info-row>
        @endif

        {{-- Member Since --}}
        @if($memberSince)
            <x-card.info-row label="Member Since">
                {{ getNiceDate(strtotime($memberSince), $justDay = true) }}
            </x-card.info-row>
        @endif
    </div>
</x-card.container>
