@props([
    'availableCheckboxFilters' => [],
    'availableRadioFilters' => [],
    'availableSelectFilters' => [],
    'availableSorts' => [],
    'columns' => [],
    'consoles' => [],
    'filterOptions' => [],
    'games' => [],
    'noGamesMessage' => 'No games.',
    'sortOrder' => 'title',
    'shouldAlwaysShowMetaSurface' => false,
    'shouldShowCount' => false,
    'totalUnfilteredCount' => null, // ?int
])

<?php
$groupByConsole = isset($filterOptions['console']) && $filterOptions['console'];
$areFiltersPristine = count(request()->query()) === 0;
$numGames = count($games);

$areGamesMaybePresent = (
    !$areFiltersPristine
    || $numGames > 0
    || $shouldAlwaysShowMetaSurface
);
?>

<div>
    @if ($areGamesMaybePresent)
        <x-meta-panel
            :availableCheckboxFilters="$availableCheckboxFilters"
            :availableRadioFilters="$availableRadioFilters"
            :availableSelectFilters="$availableSelectFilters"
            :availableSorts="$availableSorts"
            :filterOptions="$filterOptions"
            :selectedSortOrder="$sortOrder"
        />
    @endif

    @if ($shouldShowCount)
        <p class="mb-4 text-xs">
            Viewing
            <span class="font-bold">{{ localized_number($numGames) }}</span>
            @if (!$areFiltersPristine && isset($totalUnfilteredCount) && $totalUnfilteredCount !== $numGames)
                of {{ localized_number($totalUnfilteredCount) }}
            @endif
            {{ trans_choice(__('resource.game.title'), $numGames) }}
        </p>
    @endif

    <?php
    $totals = [];
    foreach ($columns as $column) {
        if (array_key_exists('javascript', $column)) {
            $column['javascript']();
        }
        if (array_key_exists('tally', $column)) {
            $totals[$column['header']] = 0;
        }
    }
    ?>

    @foreach ($consoles as $console)
        @if ($groupByConsole)
            <h2 class="flex gap-x-2 items-center text-h3">
                <img src="{{ getSystemIconUrl($console->ID) }}" alt="Console icon" width="24" height="24">
                <span>{{ $console->Name }}</span>
            </h2>
            <?php foreach ($totals as $key => $value) { $totals[$key] = 0; } ?>
        @endif

        <div class="overflow-x-auto lg:overflow-x-visible">
            <table class='table-highlight mb-4'>
                <thead>
                    <tr class="do-not-highlight lg:sticky lg:top-[42px] z-[11] bg-box">
                        @foreach ($columns as $column)
                            @php
                                $styles = [];
                                if (array_key_exists('width', $column)) {
                                    $styles[] = 'width:' . $column['width'] . '%';
                                }
                                if (array_key_exists('tooltip', $column)) {
                                    $styles[] = 'cursor:help';
                                }
                                $alignment = $column['align'] ?? 'left';
                                $class = $alignment !== 'left' ? "text-$alignment" : '';
                            @endphp

                            <th
                                class="{{ $class }}" style="{{ implode('; ', $styles) }}"
                                @isset($column['tooltip']) title="{{ $column['tooltip'] }}" @endisset
                            >
                                {{ $column['header'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($games as $game)
                        @if ($groupByConsole && $game['ConsoleID'] !== $console['ID'])
                            @continue
                        @endif
                        <tr>
                        <?php
                            foreach ($columns as $column) {
                                $column['render']($game);

                                if (array_key_exists('tally', $column)) {
                                    $totals[$column['header']] += $column['tally']($game);
                                }
                            }
                        ?>
                        </tr>
                    @endforeach

                    @if (count($totals) > 0)
                        <tr>
                        <?php
                            foreach ($columns as $column) {
                                if (!array_key_exists($column['header'], $totals)) {
                                    echo "<td></td>";
                                    continue;
                                }
                                $total = $totals[$column['header']];

                                if (array_key_exists('render_tally', $column)) {
                                    $column['render_tally']($total);
                                } else {
                                    echo "<td class='text-right'><b>" . localized_number($total) . "</b></td>";
                                }
                            }
                        ?>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if (!$groupByConsole)
            @break
        @endif
    @endforeach

    @if (empty($games) && $areGamesMaybePresent)
        <div class="mb-12">
            <x-empty-state>{{ $noGamesMessage }}</x-empty-state>
        </div>
    @endif
</div>
