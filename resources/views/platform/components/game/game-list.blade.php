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

            <div><table class='table-highlight mb-4'><tbody>

            <tr>
            <?php
            foreach ($columns as $column) {
                $styles = [];
                if (array_key_exists('width', $column)) {
                    $styles[] = 'width:' . $column['width'] . '%';
                }
                if (array_key_exists('tooltip', $column)) {
                    $styles[] = 'cursor:help';
                }

                if (count($styles) > 0) {
                    echo '<th style="' . implode('; ', $styles) . '"';
                } else {
                    echo '<th';
                }

                $alignment = $column['align'] ?? 'left';
                if ($alignment !== 'left') {
                    echo " class=\"text-$alignment\"";
                }

                if (array_key_exists('tooltip', $column)) {
                    echo " title=\"{$column['tooltip']}\"";
                }

                echo ">{$column['header']}</th>";
            }
            ?>
            </tr>
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

            </tbody></table></div>

            @if (!$filterOptions['console'])
                @break
            @endif
        @endforeach
    @endif

</div>
