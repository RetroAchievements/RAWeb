@props([
    'consoles' => [],
    'games' => [],
    'sortOrder' => 'title',
    'availableSorts' => [],
    'filterOptions' => [],
    'availableFilters' => [],
    'columns' => [],
    'noGamesMessage' => 'No games.',
])

<div>
    @if (count($consoles) < 1)
        <p>{{ $noGamesMessage }}</p><br/>
    @else
        <x-meta-panel
            :availableSorts="$availableSorts"
            :selectedSortOrder="$sortOrder"
            :availableFilters="$availableFilters"
            :filterOptions="$filterOptions"
        />
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
            @if ($filterOptions['console'])
                <h2 class="flex gap-x-2 items-center text-h3">
                    <img src="{{ getSystemIconUrl($console->ID) }}" alt="Console icon" width="24" height="24">
                    <span>{{ $console->Name }}</span>
                </h2>
                <?php foreach ($totals as $key => $value) { $totals[$key] = 0; } ?>
            @endif

            <div>
                <table class='table-highlight mb-4'>
                    <thead>
                        <tr class="do-not-highlight sticky top-[42px] z-10 bg-box">
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
                            @if ($filterOptions['console'] && $game['ConsoleID'] != $console['ID'])
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

            @if (!$filterOptions['console'])
                @break
            @endif
        @endforeach
    @endif

</div>
