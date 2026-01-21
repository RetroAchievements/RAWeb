@use('App\Platform\Services\TriggerDecoderService')
@use('App\Platform\Services\TriggerViewerService')

<x-filament-panels::page>
    @php
        $record = $this->record;
        $trigger = $record->trigger;
        $conditions = $trigger?->conditions;
    @endphp

    @if (!$conditions)
        <x-filament::section>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">No trigger defined.</p>
        </x-filament::section>
    @else
        @php
            $triggerDecoderService = new TriggerDecoderService();
            $triggerViewerService = new TriggerViewerService();

            $groups = $triggerDecoderService->decode($conditions);
            $triggerDecoderService->addCodeNotes($groups, $record->game_id);

            $hasAddAddress = $triggerViewerService->hasAddAddressFlag($groups);
            $addrFormat = $triggerViewerService->getAddressFormat($groups);
            $markdownOutput = $triggerViewerService->generateMarkdown($groups);

            $tooltipStyle = 'white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 11px; max-height: 300px; overflow-y: auto; display: block';
        @endphp

        <div
            x-data="{
                showDecimal: true,
                showAliases: false,
                collapseAddAddress: false,
                copyMarkdown() {
                    navigator.clipboard.writeText(@js($markdownOutput));
                    new FilamentNotification()
                        .title('Copied to clipboard')
                        .success()
                        .send();
                }
            }"
            class="space-y-6"
        >
            {{-- Version History --}}
            @php
                $versionData = $this->getVersionHistoryData();
                $triggers = $versionData['triggers'];
                $lazyLoad = $versionData['lazyLoad'];
                $summaries = $versionData['summaries'] ?? [];
                $diffs = $versionData['diffs'] ?? [];
            @endphp
            @include('filament.resources.achievement-resource.partials.version-history')

            <span>&nbsp;</span>

            {{-- Toolbar --}}
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 p-4 rounded-xl bg-white shadow-sm dark:bg-white/5 ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <x-filament::input.checkbox x-model="showDecimal" />
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Decimal values</span>
                    </label>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <x-filament::input.checkbox x-model="showAliases" />
                        <span class="text-sm text-neutral-600 dark:text-neutral-400">Show aliases</span>
                    </label>

                    @if ($hasAddAddress)
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <x-filament::input.checkbox x-model="collapseAddAddress" />
                            <span class="text-sm text-neutral-600 dark:text-neutral-400">Collapse AddAddress</span>
                        </label>
                    @endif
                </div>

                <x-filament::button size="sm" color="gray" icon="heroicon-o-clipboard-document" @click="copyMarkdown()">
                    Copy Markdown
                </x-filament::button>
            </div>

            @foreach ($groups as $group)
                <x-filament::section>
                    <x-filament::section.heading>
                        {{ $group['Label'] }}
                    </x-filament::section.heading>

                    {{-- Conditions table --}}
                    <div class="overflow-x-auto -mx-6 mt-4">
                        <table class="w-full text-xs">
                            <thead>
                                {{-- Column group headers --}}
                                <tr class="text-[10px] uppercase tracking-wider text-neutral-400 dark:text-neutral-500 border-b border-gray-950/5 dark:border-white/5">
                                    <th class="px-4 pt-2 pb-1"></th>
                                    <th class="px-2 pt-2 pb-1"></th>
                                    <th colspan="3" class="px-2 pt-2 pb-1 text-left font-medium">Source</th>
                                    <th class="px-2 pt-2 pb-1"></th>
                                    <th colspan="3" class="px-2 pt-2 pb-1 text-left font-medium">Target</th>
                                    <th class="px-4 pt-2 pb-1"></th>
                                </tr>

                                {{-- Column headers --}}
                                <tr class="text-neutral-500 dark:text-neutral-400 text-left border-b border-gray-950/5 dark:border-white/10">
                                    <th class="px-4 py-1.5 font-medium text-right w-8">#</th>
                                    <th class="px-2 py-1.5 font-medium">Flag</th>
                                    <th class="px-2 py-1.5 font-medium text-neutral-500 dark:text-neutral-400">Type</th>
                                    <th class="px-2 py-1.5 font-medium text-neutral-500 dark:text-neutral-400">Size</th>
                                    <th class="px-2 py-1.5 font-medium">Mem/Val</th>
                                    <th class="px-2 py-1.5 font-medium text-center border-x border-gray-950/5 dark:border-white/5">Cmp/Op</th>
                                    <th class="px-2 py-1.5 font-medium text-neutral-500 dark:text-neutral-400">Type</th>
                                    <th class="px-2 py-1.5 font-medium text-neutral-500 dark:text-neutral-400">Size</th>
                                    <th class="px-2 py-1.5 font-medium">Mem/Val</th>
                                    <th class="px-4 py-1.5 font-medium text-right">Hits</th>
                                </tr>
                            </thead>

                            @php
                                $addAddressChains = $triggerViewerService->computeAddAddressChains($group['Conditions']);
                            @endphp

                            <tbody class="font-mono">
                                @foreach ($group['Conditions'] as $condition)
                                    @php
                                        $flagClass = $triggerViewerService->getFlagColorClass($condition['Flag'] ?? '');
                                        $hasSourceNote = ($condition['SourceTooltip'] ?? '') !== '';
                                        $hasTargetNote = ($condition['TargetTooltip'] ?? '') !== '';
                                        $hitTarget = $condition['HitTarget'] ?? '';

                                        $chainRows = $addAddressChains[$loop->iteration] ?? [];
                                        $isEndOfAddAddressChain = !empty($chainRows);
                                    @endphp

                                    <tr
                                        x-show="!collapseAddAddress || {{ $condition['Flag'] === 'Add Address' ? 'false' : 'true' }}"
                                        class="{{ $loop->even ? 'bg-gray-950/[0.025] dark:bg-white/[0.04]' : '' }} hover:bg-gray-950/[0.05] dark:hover:bg-white/[0.06]"
                                        :class="{ 'border-l-2 border-l-purple-500 dark:border-l-purple-400': collapseAddAddress && {{ $isEndOfAddAddressChain ? 'true' : 'false' }} }"
                                    >
                                        {{-- Condition number --}}
                                        <td class="px-4 py-1.5 text-right text-neutral-400 dark:text-neutral-500 tabular-nums">
                                            @if ($isEndOfAddAddressChain)
                                                @php
                                                    $rowLabel = count($chainRows) === 1 ? 'row' : 'rows';
                                                    $chainRowsList = implode(', ', $chainRows);
                                                @endphp
                                                <span
                                                    x-show="collapseAddAddress"
                                                    x-cloak
                                                    class="text-purple-500 dark:text-purple-400 underline decoration-dotted underline-offset-2 cursor-help"
                                                    x-tooltip="{ content: 'AddAddress: {{ $rowLabel }} {{ $chainRowsList }}', theme: $store.theme, placement: 'left', interactive: true }"
                                                >{{ $loop->iteration }}</span>
                                                <span x-show="!collapseAddAddress" x-cloak>{{ $loop->iteration }}</span>
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </td>

                                        {{-- Condition flag with our semantic color choice --}}
                                        <td class="px-2 py-1.5 font-medium {{ $flagClass }}">
                                            {{ $condition['Flag'] }}
                                        </td>

                                        {{-- Source columns --}}
                                        <td class="px-2 py-1.5 text-neutral-500 dark:text-neutral-400">{{ $condition['SourceType'] }}</td>
                                        <td class="px-2 py-1.5 text-neutral-500 dark:text-neutral-400">{{ $condition['SourceSize'] }}</td>
                                        <td class="px-2 py-1.5">
                                            @php
                                                $sourceDisplay = $triggerViewerService->formatOperandDisplay($condition, 'Source', $group['Notes'] ?? []);
                                                $isSourceTooltipRedundant = $sourceDisplay['display'] === Str::before($condition['SourceTooltip'] ?? '', "\n");
                                            @endphp

                                            @php
                                                $sourceAliasTooltip = $sourceDisplay['isTruncated']
                                                    ? $sourceDisplay['display']
                                                    : ($isSourceTooltipRedundant ? null : ($condition['SourceTooltip'] ?? null));
                                            @endphp

                                            @if ($condition['SourceType'] === 'Recall')
                                                <span class="{{ $sourceDisplay['cssClass'] }}">{recall}</span>
                                            @elseif ($hasSourceNote)
                                                @if ($sourceAliasTooltip)
                                                    <span
                                                        x-show="showAliases"
                                                        x-cloak
                                                        class="text-emerald-600 dark:text-emerald-400 cursor-help underline decoration-dotted underline-offset-2"
                                                        x-tooltip="{ content: @js('<span style=\'' . $tooltipStyle . '\'>' . e($sourceAliasTooltip) . '</span>'), theme: $store.theme, allowHTML: true, placement: 'left', interactive: true }"
                                                    >
                                                        {{ $sourceDisplay['displayTruncated'] }}
                                                    </span>
                                                @else
                                                    <span
                                                        x-show="showAliases"
                                                        x-cloak
                                                        class="text-emerald-600 dark:text-emerald-400"
                                                    >
                                                        {{ $sourceDisplay['displayTruncated'] }}
                                                    </span>
                                                @endif

                                                <span
                                                    x-show="!showAliases"
                                                    x-cloak
                                                    class="text-blue-600 dark:text-blue-400 cursor-help underline decoration-dotted underline-offset-2"
                                                    x-tooltip="{ content: @js('<span style=\'' . $tooltipStyle . '\'>' . e($condition['SourceTooltip']) . '</span>'), theme: $store.theme, allowHTML: true, placement: 'left', interactive: true }"
                                                >
                                                    {{ $condition['SourceAddress'] }}
                                                </span>
                                            @else
                                                {{ $condition['SourceAddress'] }}
                                            @endif
                                        </td>

                                        {{-- Cmp/op column --}}
                                        @if ($condition['Operator'] === '')
                                            <td class="px-2 py-1.5 text-center border-x border-gray-950/5 dark:border-white/5"></td>
                                            <td colspan="4"></td>
                                        @else
                                            <td class="px-2 py-1.5 text-center text-neutral-500 dark:text-neutral-400 border-x border-gray-950/5 dark:border-white/5">
                                                {{ $condition['Operator'] }}
                                            </td>

                                            {{-- Target columns --}}
                                            <td class="px-2 py-1.5 text-neutral-500 dark:text-neutral-400">{{ $condition['TargetType'] }}</td>
                                            <td class="px-2 py-1.5 text-neutral-500 dark:text-neutral-400">{{ $condition['TargetSize'] }}</td>
                                            <td class="px-2 py-1.5">
                                                @php
                                                    $targetDisplay = $triggerViewerService->formatOperandDisplay($condition, 'Target', $group['Notes'] ?? []);
                                                    $isTargetTooltipRedundant = $targetDisplay['display'] === Str::before($condition['TargetTooltip'] ?? '', "\n");
                                                @endphp

                                                @if ($condition['TargetType'] === 'Recall')
                                                    <span class="text-pink-500 dark:text-pink-400">{recall}</span>
                                                @elseif ($condition['TargetType'] === 'Value')
                                                    @if ($targetDisplay['valueAlias'])
                                                        @if ($targetDisplay['isValueAliasTruncated'])
                                                            <span
                                                                x-show="showAliases"
                                                                x-cloak
                                                                class="text-emerald-600 dark:text-emerald-400 cursor-help underline decoration-dotted underline-offset-2"
                                                                x-tooltip="{ content: @js('<span style=\'' . $tooltipStyle . '\'>' . e($targetDisplay['valueAlias']) . '</span>'), theme: $store.theme, allowHTML: true, placement: 'left', interactive: true }"
                                                            >{{ $targetDisplay['valueAliasTruncated'] }}</span>
                                                        @else
                                                            <span x-show="showAliases" x-cloak class="text-emerald-600 dark:text-emerald-400">{{ $targetDisplay['valueAlias'] }}</span>
                                                        @endif

                                                        <span x-show="!showAliases && showDecimal" x-cloak>{{ $targetDisplay['decimalDisplay'] }}</span>
                                                        @if ($targetDisplay['decimalDisplay'] >= 10)
                                                            <span
                                                                x-show="!showAliases && !showDecimal"
                                                                x-cloak
                                                                class="cursor-help underline decoration-dotted underline-offset-2"
                                                                x-tooltip="{ content: '{{ $targetDisplay['decimalDisplay'] }}', theme: $store.theme, placement: 'left', interactive: true }"
                                                            >{{ $targetDisplay['hexDisplay'] }}</span>
                                                        @else
                                                            <span x-show="!showAliases && !showDecimal" x-cloak>{{ $targetDisplay['hexDisplay'] }}</span>
                                                        @endif
                                                    @else
                                                        <span x-show="showDecimal" x-cloak>{{ $targetDisplay['decimalDisplay'] }}</span>
                                                        @if ($targetDisplay['decimalDisplay'] >= 10)
                                                            <span
                                                                x-show="!showDecimal"
                                                                x-cloak
                                                                class="cursor-help underline decoration-dotted underline-offset-2"
                                                                x-tooltip="{ content: '{{ $targetDisplay['decimalDisplay'] }}', theme: $store.theme, placement: 'left', interactive: true }"
                                                            >{{ $targetDisplay['hexDisplay'] }}</span>
                                                        @else
                                                            <span x-show="!showDecimal" x-cloak>{{ $targetDisplay['hexDisplay'] }}</span>
                                                        @endif
                                                    @endif
                                                @elseif ($hasTargetNote)
                                                    @php
                                                        $targetAliasTooltip = $targetDisplay['isTruncated']
                                                            ? $targetDisplay['display']
                                                            : ($isTargetTooltipRedundant ? null : ($condition['TargetTooltip'] ?? null));
                                                    @endphp

                                                    @if ($targetAliasTooltip)
                                                        <span
                                                            x-show="showAliases"
                                                            x-cloak
                                                            class="text-emerald-600 dark:text-emerald-400 cursor-help underline decoration-dotted underline-offset-2"
                                                            x-tooltip="{ content: @js('<span style=\'' . $tooltipStyle . '\'>' . e($targetAliasTooltip) . '</span>'), theme: $store.theme, allowHTML: true, placement: 'left', interactive: true }"
                                                        >
                                                            {{ $targetDisplay['displayTruncated'] }}
                                                        </span>
                                                    @else
                                                        <span
                                                            x-show="showAliases"
                                                            x-cloak
                                                            class="text-emerald-600 dark:text-emerald-400"
                                                        >
                                                            {{ $targetDisplay['displayTruncated'] }}
                                                        </span>
                                                    @endif

                                                    <span
                                                        x-show="!showAliases"
                                                        x-cloak
                                                        class="text-blue-600 dark:text-blue-400 cursor-help underline decoration-dotted underline-offset-2"
                                                        x-tooltip="{ content: @js('<span style=\'' . $tooltipStyle . '\'>' . e($condition['TargetTooltip']) . '</span>'), theme: $store.theme, allowHTML: true, placement: 'left', interactive: true }"
                                                    >
                                                        {{ $condition['TargetAddress'] }}
                                                    </span>
                                                @else
                                                    {{ $condition['TargetAddress'] }}
                                                @endif
                                            </td>

                                            {{-- Hit count --}}
                                            <td class="px-4 py-1.5 text-right tabular-nums {{ $hitTarget === '0' || $hitTarget === '' ? 'opacity-20' : '' }}">
                                                ({{ $hitTarget ?: '0' }})
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Code notes section --}}
                    @if (!empty($group['Notes']))
                        <details class="border-t border-gray-950/5 dark:border-white/10 -mx-6 mt-4 group">
                            <summary class="px-6 py-3 text-xs font-medium text-neutral-500 dark:text-neutral-400 cursor-pointer hover:text-neutral-700 dark:hover:text-neutral-200 flex items-center gap-1.5 select-none">
                                <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                                Code Notes ({{ count($group['Notes']) }})
                            </summary>

                            <div class="mx-4 mb-4 px-4 py-3 max-h-64 overflow-y-auto rounded-lg bg-gray-950/[0.02] dark:bg-white/[0.03]">
                                <div class="divide-y divide-gray-950/5 dark:divide-white/5">
                                    @foreach ($group['Notes'] as $addr => $note)
                                        <div class="flex gap-3 text-xs py-2 first:pt-0 last:pb-0">
                                            <code class="shrink-0 font-medium text-blue-600 dark:text-blue-400">{{ sprintf($addrFormat, $addr) }}</code>
                                            <span class="font-mono text-neutral-600 dark:text-neutral-400 break-words whitespace-pre-wrap">{{ $note }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
