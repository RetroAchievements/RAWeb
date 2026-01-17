{{--
    This partial is loaded via @include, so @props() unfortunately can't be used.

    Expected variables from the parent view:
    - $triggers: Collection of Trigger models with version history.
    - $lazyLoad: bool - Whether to load data asynchronously (true for complex achievements).
    - $summaries: array<int, string> - Pre-computed summaries (only when $lazyLoad is false).
    - $diffs: array<int, array> - Pre-computed diffs (only when $lazyLoad is false).

    For simple achievements (<50KB total conditions), summaries and diffs are pre-computed
    on the server for instant display. For complex achievements, they're loaded via Livewire.
--}}

@if ($triggers->count() >= 1)
@php
    $minVersion = $triggers->min('version');
@endphp

<section class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="px-6 py-4 text-sm font-medium text-neutral-950 dark:text-white">
        Version History ({{ $triggers->count() }} {{ Str::plural('version', $triggers->count()) }})
    </div>

    <div
        class="px-6 pb-4"
        x-data="{
            expanded: {},
            viewMode: {},
            showAll: false,
            lazyLoad: {{ ($lazyLoad ?? false) ? 'true' : 'false' }},
            summaries: @js($summaries ?? []),
            diffs: @js($diffs ?? []),
            loadingSummaries: {{ ($lazyLoad ?? false) ? 'true' : 'false' }},
            loadingDiff: {},
            async init() {
                if (this.lazyLoad) {
                    this.summaries = await $wire.loadAllSummaries();
                    this.loadingSummaries = false;
                }
            },
            async toggleVersion(version) {
                this.expanded[version] = !this.expanded[version];
                if (this.lazyLoad && this.expanded[version] && !this.diffs[version] && !this.loadingDiff[version]) {
                    this.loadingDiff[version] = true;
                    const result = await $wire.loadVersionDiff(version);
                    this.diffs[version] = result.diff;
                    this.loadingDiff[version] = false;
                }
            },
            getDiffStatusClass(status) {
                switch (status) {
                    case 'added': return 'bg-emerald-500/10 border-l-4 border-emerald-500';
                    case 'removed': return 'bg-red-500/10 border-l-4 border-red-500 line-through opacity-60';
                    case 'modified': return 'bg-amber-500/10 border-l-4 border-amber-500';
                    default: return '';
                }
            },
            isFieldChanged(changedFields, field) {
                return changedFields && changedFields.includes(field);
            },
            hasSourceChanged(changedFields) {
                return changedFields && (changedFields.includes('SourceType') || changedFields.includes('SourceSize') || changedFields.includes('SourceAddress'));
            },
            hasTargetChanged(changedFields) {
                return changedFields && (changedFields.includes('TargetType') || changedFields.includes('TargetSize') || changedFields.includes('TargetAddress'));
            }
        }"
    >
        @foreach ($triggers as $trigger)
            <div
                class="border-b border-neutral-200 dark:border-neutral-700 py-3 last:border-0"
                x-show="showAll || {{ $loop->index }} < 8"
            >
                {{-- Version header --}}
                <button
                    @click="toggleVersion({{ $trigger->version ?? "'draft'" }})"
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
                            @if ($trigger->version)
                                <span class="text-neutral-500 dark:text-neutral-400">
                                    <template x-if="loadingSummaries">
                                        <span class="animate-pulse text-neutral-400">Loading...</span>
                                    </template>
                                    <template x-if="!loadingSummaries">
                                        <span x-text="summaries[{{ $trigger->version }}] || ''"></span>
                                    </template>
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
                                :class="{ 'rotate-180': expanded[{{ $trigger->version ?? "'draft'" }}] }"
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
                    x-show="expanded[{{ $trigger->version ?? "'draft'" }}]"
                    x-cloak
                    x-collapse
                >
                    <div class="pt-3">
                        {{-- Toggle buttons: Diff | Raw --}}
                        <div class="flex gap-1 mb-3">
                            <button
                                @click="viewMode[{{ $trigger->version ?? "'draft'" }}] = 'diff'"
                                :class="(viewMode[{{ $trigger->version ?? "'draft'" }}] ?? 'diff') === 'diff'
                                    ? 'bg-neutral-200 dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100'
                                    : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                                class="px-3 py-1 text-xs font-medium rounded transition-colors"
                            >
                                Diff
                            </button>

                            <button
                                @click="viewMode[{{ $trigger->version ?? "'draft'" }}] = 'raw'"
                                :class="viewMode[{{ $trigger->version ?? "'draft'" }}] === 'raw'
                                    ? 'bg-neutral-200 dark:bg-neutral-700 text-neutral-900 dark:text-neutral-100'
                                    : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                                class="px-3 py-1 text-xs font-medium rounded transition-colors"
                            >
                                Raw
                            </button>
                        </div>

                        {{-- Diff view (default) --}}
                        <div x-show="(viewMode[{{ $trigger->version ?? "'draft'" }}] ?? 'diff') === 'diff'">
                            {{-- Loading state --}}
                            <template x-if="loadingDiff[{{ $trigger->version ?? "'draft'" }}]">
                                <div class="py-8 text-center text-neutral-500 dark:text-neutral-400">
                                    <svg class="animate-spin size-5 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Loading diff...
                                </div>
                            </template>

                            {{-- Diff content (rendered dynamically from JS data) --}}
                            <template x-if="diffs[{{ $trigger->version ?? "'draft'" }}] && !loadingDiff[{{ $trigger->version ?? "'draft'" }}]">
                                <div class="space-y-4">
                                    <template x-for="(group, groupIndex) in diffs[{{ $trigger->version ?? "'draft'" }}]" :key="groupIndex">
                                        <div class="rounded-lg bg-neutral-50 dark:bg-neutral-800/50 overflow-hidden">
                                            <div class="px-4 py-2 bg-neutral-100 dark:bg-neutral-800 border-b border-neutral-200 dark:border-neutral-700">
                                                <span class="font-medium text-sm text-neutral-700 dark:text-neutral-300" x-text="group.Label"></span>
                                                <template x-if="group.DiffStatus === 'added'">
                                                    <span class="ml-2 text-xs text-emerald-600 dark:text-emerald-400">(new group)</span>
                                                </template>
                                                <template x-if="group.DiffStatus === 'removed'">
                                                    <span class="ml-2 text-xs text-red-600 dark:text-red-400">(removed group)</span>
                                                </template>
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
                                                        <template x-for="(condition, condIndex) in group.Conditions" :key="condIndex">
                                                            <tr
                                                                :class="getDiffStatusClass(condition.DiffStatus) + ' border-b border-neutral-100 dark:border-neutral-700/50 last:border-b-0'"
                                                            >
                                                                <td class="px-3 py-1.5 text-neutral-400" x-text="condition.RowIndex || ''"></td>

                                                                {{-- Flag column --}}
                                                                <td class="px-3 py-1.5">
                                                                    <template x-if="isFieldChanged(condition.ChangedFields, 'Flag')">
                                                                        <span>
                                                                            <span x-show="condition.OldValues?.Flag" class="line-through opacity-50 mr-1" x-text="condition.OldValues?.Flag"></span>
                                                                            <span class="text-amber-600 dark:text-amber-400 font-medium" x-text="condition.Flag || ''"></span>
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="!isFieldChanged(condition.ChangedFields, 'Flag')">
                                                                        <span x-text="condition.Flag || ''"></span>
                                                                    </template>
                                                                </td>

                                                                {{-- Source column --}}
                                                                <td class="px-3 py-1.5">
                                                                    <template x-if="hasSourceChanged(condition.ChangedFields)">
                                                                        <span>
                                                                            <span class="line-through opacity-50 mr-1" x-text="[condition.OldValues?.SourceType, condition.OldValues?.SourceSize, condition.OldValues?.SourceAddress].filter(Boolean).join(' ')"></span>
                                                                            <span class="text-amber-600 dark:text-amber-400" x-text="[condition.SourceType, condition.SourceSize, condition.SourceAddress].filter(Boolean).join(' ')"></span>
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="!hasSourceChanged(condition.ChangedFields)">
                                                                        <span x-text="[condition.SourceType, condition.SourceSize, condition.SourceAddress].filter(Boolean).join(' ')"></span>
                                                                    </template>
                                                                </td>

                                                                {{-- Cmp column --}}
                                                                <td class="px-3 py-1.5">
                                                                    <template x-if="isFieldChanged(condition.ChangedFields, 'Operator')">
                                                                        <span>
                                                                            <span class="line-through opacity-50 mr-1" x-text="condition.OldValues?.Operator || ''"></span>
                                                                            <span class="text-amber-600 dark:text-amber-400" x-text="condition.Operator || ''"></span>
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="!isFieldChanged(condition.ChangedFields, 'Operator')">
                                                                        <span x-text="condition.Operator || ''"></span>
                                                                    </template>
                                                                </td>

                                                                {{-- Target column --}}
                                                                <td class="px-3 py-1.5">
                                                                    <template x-if="hasTargetChanged(condition.ChangedFields)">
                                                                        <span>
                                                                            <span class="line-through opacity-50 mr-1" x-text="[condition.OldValues?.TargetType, condition.OldValues?.TargetSize, condition.OldValues?.TargetAddress].filter(Boolean).join(' ')"></span>
                                                                            <span class="text-amber-600 dark:text-amber-400" x-text="[condition.TargetType, condition.TargetSize, condition.TargetAddress].filter(Boolean).join(' ')"></span>
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="!hasTargetChanged(condition.ChangedFields)">
                                                                        <span x-text="[condition.TargetType, condition.TargetSize, condition.TargetAddress].filter(Boolean).join(' ')"></span>
                                                                    </template>
                                                                </td>

                                                                {{-- Hit count column --}}
                                                                <td class="px-3 py-1.5">
                                                                    <template x-if="isFieldChanged(condition.ChangedFields, 'HitTarget')">
                                                                        <span>
                                                                            <span class="line-through opacity-50 mr-1" x-text="'(' + (condition.OldValues?.HitTarget || '0') + ')'"></span>
                                                                            <span class="text-amber-600 dark:text-amber-400" x-text="'(' + (condition.HitTarget || '0') + ')'"></span>
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="!isFieldChanged(condition.ChangedFields, 'HitTarget') && condition.HitTarget && condition.HitTarget !== '0'">
                                                                        <span x-text="'(' + condition.HitTarget + ')'"></span>
                                                                    </template>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Initial state before loading --}}
                            <template x-if="!diffs[{{ $trigger->version ?? "'draft'" }}] && !loadingDiff[{{ $trigger->version ?? "'draft'" }}]">
                                <div class="py-4 text-center text-neutral-400 dark:text-neutral-500 text-sm">
                                    Click to load diff...
                                </div>
                            </template>
                        </div>

                        {{-- Raw view, shown when user selects the 'Raw' toggle button/tab --}}
                        <div x-show="viewMode[{{ $trigger->version ?? "'draft'" }}] === 'raw'" x-cloak>
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
