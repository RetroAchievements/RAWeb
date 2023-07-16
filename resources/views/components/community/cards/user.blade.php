<x-tooltip-card imgSrc="{{ $avatarUrl }}">
    <div class="relative h-full text-2xs">
        <!-- Role -->
        @if($canShowUserRole)
            <div class="absolute top-[-14px] right-[-21px]">
                <p class="text-2xs tracking-tighter flex items-center justify-center pl-2 pr-5 pt-2 bg-menu-link text-box-bg rounded">
                    {{ $roleLabel }}
                </p>
            </div>
        @endif

        <!-- Username -->
        <p class="font-bold text-lg -mt-1 {{ $useExtraNamePadding ? "pt-3" : "" }}">{{ $username }}</p>

        <!-- Motto -->
        @if($motto !== null && mb_strlen($motto) > 2)
            <div class="mb-1 rounded bg-bg text-text italic p-1 text-2xs hyphens-auto">
                <p>{{ $motto }}</p>
            </div>
        @endif

        <!-- Points -->
        @if($hardcorePoints > $softcorePoints)
            <p>
                <span class="font-bold">Points:</span>
                {{ localized_number($hardcorePoints) }}
                ({{ localized_number($retroPoints) }})
            </p>
        @elseif($softcorePoints > 0)
            <p>
                <span class="font-bold">Softcore Points:</span>
                {{ localized_number($softcorePoints) }}
            </p>
        @else
            <p><span class="font-bold">Points:</span> 0</p>
        @endif

        <!-- Site Rank -->
        <p>
            <span class="font-bold">{{ $rankLabel }}</span>
            @if($isUntracked)
                <span>Untracked</span>
            @else
                {{ $siteRank === 0 ? "Needs at least $rankMinPoints points" : "#" . localized_number($siteRank) }}
                {{ $rankPctLabel }}
            @endif
        </p>

        <!-- Last Activity -->
        @if($lastActivity)
            <p>
                <span class="font-bold">Last Activity:</span>
                {{ $lastActivity }}
            </p>
        @endif

        <!-- Member Since -->
        @if($memberSince)
            <p>
                <span class="font-bold">Member Since:</span>
                {{ getNiceDate(strtotime($memberSince), $justDay = true) }}
            </p>
        @endif
    </div>
</x-tooltip-card>
