<x-card.container imgSrc="{{ $badgeUrl }}" imgKind="game">
    <div class="relative h-full text-2xs">
        <!-- Game Name -->
        <p class="font-bold -mt-1 line-clamp-2 {{ mb_strlen($rawTitle) > 24 ? 'text-sm leading-5 mb-1' : 'text-lg leading-6 mb-0.5' }}">
            <x-game-title :rawTitle="$rawTitle" />
        </p>

        <!-- Console Icon and Name -->
        <div class="flex items-center gap-x-1">
            <img src="{{ $gameSystemIconSrc }}" width="18" height="18" alt="{{ $consoleName }} console icon">
            <span class="block text-sm tracking-tighter">{{ $consoleName }}</span>
        </div>

        @if ($achievementsCount > 0 || mb_strpos($rawTitle, '~Z~') !== false)
            <!-- Progression Status -->
            @if ($highestProgressionStatus && $highestProgressionAwardDate)
                <x-cards.game.highest-progression-status
                    :highestProgressionStatus="$highestProgressionStatus"
                    :highestProgressionAwardDate="$highestProgressionAwardDate"
                    :isEvent="$isEvent"
                />
            @else
                <div class="mb-2"></div>
            @endif
        @endif

        @if ($achievementsCount > 0)
            @if (!$isEvent)
                <div class="-mt-0.5 leading-[0.98rem]">
                    <!-- Achievement Count -->
                    <x-card.info-row label="Achievements">
                        {{ localized_number($achievementsCount) }}
                    </x-card.info-row>

                    @if ($pointsSum > 0)
                        <!-- Points Sum -->
                        <x-card.info-row label="Points">
                            {{ localized_number($pointsSum) }}
                        </x-card.info-row>

                        <!-- RetroPoints & Retro Ratio -->
                        <x-card.info-row label="RetroPoints">
                            <span>
                                {{ $retroPointsSum > 0 ? localized_number($retroPointsSum) : 'None yet' }}
                                @if ($retroRatio != 0)
                                    (&times;{{ $retroRatio }} Rarity)
                                @endif
                            <span>
                        </x-card.info-row>
                    @endif
                </div>
            @endif

            <!-- Revision Notice -->
            @if (count($activeDeveloperUsernames) !== 0)
                <div class="mt-1">
                    <x-cards.game.active-claim-notice
                        :activeDeveloperUsernames="$activeDeveloperUsernames"
                        :activeDevelopersLabel="$activeDevelopersLabel"
                        claimKind="revision"
                    />
                </div>
            @endif
        @else
            <div class="mb-2"></div>

            @if ($isHub)
                <x-card.info-row label="Links">
                    {{ localized_number($altGamesCount) }}
                </x-card.info-row>

                <x-card.info-row label="Last Updated">
                    {{ $lastUpdated->format('j F Y') }}
                </x-card.info-row>
            @elseif (count($activeDeveloperUsernames) === 0)
                <p>
                    @if (mb_strpos($rawTitle, '~Z~') !== false)
                        This achievements set has been retired.
                    @else
                        No achievements yet.
                    @endif
                </p>
            @else
                <x-cards.game.active-claim-notice
                    :activeDeveloperUsernames="$activeDeveloperUsernames"
                    :activeDevelopersLabel="$activeDevelopersLabel"
                />
            @endif
        @endif
    </div>
</x-card.container>