<?php
// Kept in the template as these are view-oriented concerns.
$completedColor = 'rgb(11, 113, 193)';
$masteredColor = 'gold';

$highestProgressionColor = $completedColor;
if ($highestProgressionStatus === 'Mastered') {
    $highestProgressionColor = $masteredColor;
} 
?>

<x-card.container imgSrc="{{ $badgeUrl }}">
    <div class="relative h-full text-xs">
        <!-- Game Name -->
        <p class="font-bold -mt-1 mb-1 line-clamp-2 {{ mb_strlen($rawTitle) > 24 ? 'text-sm leading-5' : 'text-lg leading-6' }}">
            {!! $renderedTitle !!}
        </p>

        <!-- Console Icon and Name -->
        <div class="flex items-center gap-x-1">
            <img src="{{ $gameSystemIconSrc }}" width="24" height="24" alt="{{ $consoleName }} console icon">
            <span class="block text-sm tracking-tighter">{{ $consoleName }}</span>
        </div>

        @if ($achievementsCount > 0 || mb_strpos($rawTitle, '~Z~') !== false)
            <!-- Progression Status -->
            @if ($highestProgressionStatus && $highestProgressionAwardDate)
                <div class="my-1 flex items-center gap-x-1">
                    <div class="rounded-full w-2 h-2" style="background-color: {{ $highestProgressionColor }}"></div>
                    <span>{{ $highestProgressionStatus }} {{ $highestProgressionAwardDate->format('j F Y') }}</span>
                </div>
            @else
                <div class="mb-2"></div>
            @endif
        @endif

        @if ($achievementsCount > 0)
            <!-- Achievement Count -->
            <x-card.info-row label="Achievements">
                {{ localized_number($achievementsCount) }}
            </x-card.info-row>

            @if ($pointsSum > 0)
                <!-- Points Sum -->
                <x-card.info-row label="Points">
                    {{ localized_number($pointsSum) }}
                    <span @if ($retroPointsSum === 0) class="text-text-muted" @endif>
                        ({{ localized_number($retroPointsSum) }})
                    </span>
                </x-card.info-row>

                <!-- Retro Ratio -->
                <x-card.info-row label="Retro Ratio">
                    {{ $retroRatio == 0 ? 'None yet' : $retroRatio }}
                </x-card.info-row>
            @endif

            <!-- Last Updated -->
            @if (!$highestProgressionStatus && !$highestProgressionAwardDate)
                <x-card.info-row label="Last Updated">
                    {{ $lastUpdated->format('j F Y') }}
                </x-card.info-row>
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