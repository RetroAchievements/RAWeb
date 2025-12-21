{{--
    This partial is loaded via @include, so @props() unfortunately can't be used.

    Expected variables from the parent view:
    - $triggers: Collection of Trigger models with version history.
    - $summaries: array<int, string> keyed by version number, containing formatted diff summaries.
    - $diffs: array<int, array> keyed by version number, containing decoded diff data for each version.
--}}

@if ($triggers->count() >= 1)
@php
    $minVersion = $triggers->min('version');
@endphp

<section class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="px-6 py-4 text-sm font-medium text-neutral-950 dark:text-white">
        Version History ({{ $triggers->count() }} {{ Str::plural('version', $triggers->count()) }})
    </div>

    <div class="px-6 pb-4" x-data="{ expanded: {}, viewMode: {}, showAll: false }">
        @foreach ($triggers as $trigger)
            <div
                class="border-b border-neutral-200 dark:border-neutral-700 py-3 last:border-0"
                x-show="showAll || {{ $loop->index }} < 8"
            >
                {{-- Version header --}}
                <button
                    @click="expanded['{{ $trigger->version }}'] = !expanded['{{ $trigger->version }}']"
                    class="w-full text-left hover:bg-neutral-50 dark:hover:bg-neutral-800/50 -mx-2 px-2 py-1.5 rounded transition-colors"
                >
                    <div class="flex items-center justify-between gap-4">
                        {{-- Avatar + author + time --}}
                        <div class="flex items-center gap-2 text-sm">
                            @if ($trigger->user)
                                <x-filament-panels::avatar.user :user="$trigger->user" class="!size-5" />
                            @endif

                            <span class="text-neutral-950 dark:text-white font-medium">{{ $trigger->user?->display_name ?? 'Unknown' }}</span>
                            @if ($trigger->created_at->year >= 2013)
                                <span class="text-neutral-400 dark:text-neutral-500">·</span>
                                <span
                                    class="text-neutral-500 dark:text-neutral-400 cursor-help"
                                    x-tooltip="{ content: '{{ $trigger->created_at->format('M j, Y g:ia') }}', theme: $store.theme }"
                                >
                                    {{ $trigger->created_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>

                        {{-- Diff summary + version badge + chevron --}}
                        <div class="flex items-center gap-3 shrink-0 text-sm">
                            @if ($trigger->version && isset($summaries[$trigger->version]))
                                <span class="text-neutral-500 dark:text-neutral-400">
                                    {{ $summaries[$trigger->version] }}
                                </span>
                            @endif

                            @if ($trigger->version)
                                <span class="font-mono text-xs px-2 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                                    v{{ $trigger->version }}
                                </span>
                            @else
                                <span
                                    class="font-mono text-xs px-2 py-0.5 rounded bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 cursor-help"
                                    x-tooltip="{ content: 'This asset is unpublished', theme: $store.theme }"
                                >
                                    Draft
                                </span>
                            @endif

                            <svg
                                class="size-4 text-neutral-400 transition-transform"
                                :class="{ 'rotate-180': expanded['{{ $trigger->version }}'] }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                </button>

                {{-- Expandable content --}}
                <div
                    x-show="expanded['{{ $trigger->version }}']"
                    x-cloak
                    x-collapse
                >
                    <div class="pt-3">
                        {{-- Toggle buttons: Diff | Raw --}}
                        <div class="flex gap-1 mb-3">
                            <button
                                @click="viewMode['{{ $trigger->version }}'] = 'diff'"
                                :class="(viewMode['{{ $trigger->version }}'] ?? 'diff') === 'diff'
                                    ? 'bg-neutral-200 dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100'
                                    : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                                class="px-3 py-1 text-xs font-medium rounded transition-colors"
                            >
                                Diff
                            </button>
                            
                            <button
                                @click="viewMode['{{ $trigger->version }}'] = 'raw'"
                                :class="viewMode['{{ $trigger->version }}'] === 'raw'
                                    ? 'bg-neutral-200 dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100'
                                    : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                                class="px-3 py-1 text-xs font-medium rounded transition-colors"
                            >
                                Raw
                            </button>
                        </div>

                        {{-- Diff view (default) --}}
                        <div x-show="(viewMode['{{ $trigger->version }}'] ?? 'diff') === 'diff'">
                            @if (isset($diffs[$trigger->version]))
                                <div class="space-y-4">
                                    @foreach ($diffs[$trigger->version] as $group)
                                        <div class="rounded-lg bg-neutral-50 dark:bg-neutral-800/50 overflow-hidden">
                                            <div class="px-4 py-2 bg-neutral-100 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                                                <span class="font-medium text-sm text-neutral-700 dark:text-neutral-300">{{ $group['Label'] }}</span>
                                                @if (($group['DiffStatus'] ?? 'unchanged') === 'added')
                                                    <span class="ml-2 text-xs text-emerald-600 dark:text-emerald-400">(new group)</span>
                                                @elseif (($group['DiffStatus'] ?? 'unchanged') === 'removed')
                                                    <span class="ml-2 text-xs text-red-600 dark:text-red-400">(removed group)</span>
                                                @endif
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="w-full text-xs font-mono">
                                                    <thead>
                                                        <tr class="text-left text-neutral-500 dark:text-neutral-400 border-b border-neutral-200 dark:border-neutral-700">
                                                            <th class="px-3 py-2 font-medium">#</th>
                                                            <th class="px-3 py-2 font-medium">Flag</th>
                                                            <th class="px-3 py-2 font-medium">Source</th>
                                                            <th class="px-3 py-2 font-medium">Cmp</th>
                                                            <th class="px-3 py-2 font-medium">Target</th>
                                                            <th class="px-3 py-2 font-medium">Hits</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        @foreach ($group['Conditions'] as $condition)
                                                            @php
                                                                $diffStatus = $condition['DiffStatus'] ?? 'unchanged';
                                                                $rowClass = match($diffStatus) {
                                                                    'added' => 'bg-emerald-500/10 border-l-4 border-emerald-500',
                                                                    'removed' => 'bg-red-500/10 border-l-4 border-red-500 line-through opacity-60',
                                                                    'modified' => 'bg-amber-500/10 border-l-4 border-amber-500',
                                                                    default => '',
                                                                };

                                                                $changedFields = $condition['ChangedFields'] ?? [];
                                                                $oldValues = $condition['OldValues'] ?? [];
                                                            @endphp

                                                            <tr class="{{ $rowClass }} border-b border-neutral-100 dark:border-neutral-700/50 last:border-b-0">
                                                                <td class="px-3 py-1.5 text-neutral-400">{{ $condition['RowIndex'] ?? '' }}</td>

                                                                {{-- Flag column --}}
                                                                <td class="px-3 py-1.5">
                                                                    @if (in_array('Flag', $changedFields))
                                                                        @if (!empty($oldValues['Flag']))
                                                                            <span class="line-through opacity-50 mr-1">{{ $oldValues['Flag'] }}</span>
                                                                        @endif
                                                                        <span class="text-amber-600 dark:text-amber-400 font-medium">{{ $condition['Flag'] ?? '' }}</span>
                                                                    @else
                                                                        {{ $condition['Flag'] ?? '' }}
                                                                    @endif
                                                                </td>

                                                                {{-- Source column --}}
                                                                <td class="px-3 py-1.5">
                                                                    @php
                                                                        $sourceChanged = array_intersect(['SourceType', 'SourceSize', 'SourceAddress'], $changedFields);
                                                                    @endphp

                                                                    @if (!empty($sourceChanged))
                                                                        <span class="line-through opacity-50 mr-1">{{ $oldValues['SourceType'] ?? '' }} {{ $oldValues['SourceSize'] ?? '' }} {{ $oldValues['SourceAddress'] ?? '' }}</span>
                                                                        <span class="text-amber-600 dark:text-amber-400">{{ $condition['SourceType'] ?? '' }} {{ $condition['SourceSize'] ?? '' }} {{ $condition['SourceAddress'] ?? '' }}</span>
                                                                    @else
                                                                        {{ $condition['SourceType'] ?? '' }}
                                                                        {{ $condition['SourceSize'] ?? '' }}
                                                                        {{ $condition['SourceAddress'] ?? '' }}
                                                                    @endif
                                                                </td>

                                                                {{-- Cmp column --}}
                                                                <td class="px-3 py-1.5">
                                                                    @if (in_array('Operator', $changedFields))
                                                                        <span class="line-through opacity-50 mr-1">{{ $oldValues['Operator'] ?? '' }}</span>
                                                                        <span class="text-amber-600 dark:text-amber-400">{{ $condition['Operator'] ?? '' }}</span>
                                                                    @else
                                                                        {{ $condition['Operator'] ?? '' }}
                                                                    @endif
                                                                </td>

                                                                {{-- Target column --}}
                                                                <td class="px-3 py-1.5">
                                                                    @php
                                                                        $targetChanged = array_intersect(['TargetType', 'TargetSize', 'TargetAddress'], $changedFields);
                                                                    @endphp

                                                                    @if (!empty($targetChanged))
                                                                        <span class="line-through opacity-50 mr-1">{{ $oldValues['TargetType'] ?? '' }} {{ $oldValues['TargetSize'] ?? '' }} {{ $oldValues['TargetAddress'] ?? '' }}</span>
                                                                        <span class="text-amber-600 dark:text-amber-400">{{ $condition['TargetType'] ?? '' }} {{ $condition['TargetSize'] ?? '' }} {{ $condition['TargetAddress'] ?? '' }}</span>
                                                                    @else
                                                                        {{ $condition['TargetType'] ?? '' }}
                                                                        {{ $condition['TargetSize'] ?? '' }}
                                                                        {{ $condition['TargetAddress'] ?? '' }}
                                                                    @endif
                                                                </td>

                                                                {{-- Hit count column --}}
                                                                <td class="px-3 py-1.5">
                                                                    @if (in_array('HitTarget', $changedFields))
                                                                        <span class="line-through opacity-50 mr-1">({{ $oldValues['HitTarget'] ?? '0' }})</span>
                                                                        <span class="text-amber-600 dark:text-amber-400">({{ $condition['HitTarget'] ?? '0' }})</span>
                                                                    @elseif (!empty($condition['HitTarget']) && $condition['HitTarget'] !== '0')
                                                                        ({{ $condition['HitTarget'] }})
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Raw view, shown when user selects the 'Raw' toggle button/tab --}}
                        <div x-show="viewMode['{{ $trigger->version }}'] === 'raw'" x-cloak>
                            <code class="block p-3 font-mono text-[11px] rounded-lg bg-gray-950/[0.02] dark:bg-white/[0.02] border border-gray-950/5 dark:border-white/10 break-all text-neutral-600 dark:text-neutral-400">
                                {{ $trigger->conditions }}
                            </code>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if ($triggers->count() > 8)
            <button
                x-show="!showAll"
                @click="showAll = true"
                class="w-full py-3 text-sm text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300 transition-colors"
            >
                See {{ $triggers->count() - 8 }} more {{ Str::plural('version', $triggers->count() - 8) }}...
            </button>
        @endif
    </div>
</section>
@endif
