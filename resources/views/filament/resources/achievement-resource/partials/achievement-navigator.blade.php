{{--
    This component provides compact inline navigation between achievements in the same set.

    @param array $navData - Navigation data from getAchievementSetNavigationData()
    @param string $pageType - 'view', 'edit', or 'logic' to determine navigation targets
--}}

@use('App\Filament\Resources\AchievementResource')

@php
    $promotedAchievements = $navData['promotedAchievements'];
    $unpromotedAchievements = $navData['unpromotedAchievements'];
    $current = $navData['current'];
    $isPromoted = $navData['isPromoted'];
    $currentIndex = $navData['currentIndex'];
    $totalInStatus = $navData['totalInStatus'];
    $previous = $navData['previous'];
    $next = $navData['next'];

    $totalAll = $promotedAchievements->count() + $unpromotedAchievements->count();

    $pageName = match ($pageType) {
        'edit' => 'edit',
        'logic' => 'logic',
        default => 'view',
    };

    $getUrlForAchievement = fn ($achievement) => AchievementResource::getUrl($pageName, ['record' => $achievement]);

    $prevUrl = $previous ? $getUrlForAchievement($previous) : null;
    $nextUrl = $next ? $getUrlForAchievement($next) : null;
    $baseUrl = AchievementResource::getUrl($pageName, ['record' => '__ID__']);

    $mapAchievementForJs = fn ($a) => [
        'id' => $a->id,
        'title' => $a->title,
        'description' => $a->description,
        'badge_url' => $a->badge_url,
    ];
@endphp

<div
    wire:key="achievement-nav-{{ $current->id }}-{{ $isPromoted ? 'promoted' : 'unpromoted' }}-{{ $totalInStatus }}"
    x-data="{
        open: false,
        search: '',
        promotedList: @js($promotedAchievements->map($mapAchievementForJs)->values()),
        unpromotedList: @js($unpromotedAchievements->map($mapAchievementForJs)->values()),
        filterBySearch(list) {
            if (!this.search.trim()) return list;
            const term = this.search.toLowerCase();
            return list.filter(a =>
                a.title.toLowerCase().includes(term) ||
                a.id.toString().includes(term) ||
                a.description.toLowerCase().includes(term)
            );
        },
        get sections() {
            const sections = [];
            const promoted = this.filterBySearch(this.promotedList);
            const unpromoted = this.filterBySearch(this.unpromotedList);
            if (promoted.length > 0) {
                sections.push({ key: 'promoted', label: 'Promoted', achievements: promoted });
            }
            if (unpromoted.length > 0) {
                sections.push({ key: 'unpromoted', label: 'Unpromoted', achievements: unpromoted });
            }
            return sections;
        },
        get hasResults() {
            return this.sections.length > 0;
        }
    }"
    @keydown.escape.window="open = false"
    class="flex items-center gap-1"
>
    {{-- Previous Achievement Button --}}
    @if ($prevUrl)
        <a
            href="{{ $prevUrl }}"
            wire:navigate
            class="p-1 rounded hover:bg-gray-100 dark:hover:bg-white/5 text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200 transition-colors"
            title="Previous {{ $isPromoted ? 'promoted' : 'unpromoted' }} achievement"
        >
            <x-heroicon-m-chevron-left class="size-4" />
        </a>
    @else
        <span class="p-1 text-neutral-300 dark:text-neutral-600 cursor-not-allowed">
            <x-heroicon-m-chevron-left class="size-4" />
        </span>
    @endif

    {{-- Position Indicator / Dropdown Trigger --}}
    <div class="relative">
        <button
            @click="open = !open; if (open) $nextTick(() => $refs.searchInput?.focus())"
            type="button"
            class="flex items-center gap-1.5 px-2 py-1 text-sm text-neutral-600 dark:text-neutral-300 hover:bg-gray-100 dark:hover:bg-white/5 rounded transition-colors"
        >
            <span>{{ $isPromoted ? 'Promoted' : 'Unpromoted' }} Achievement {{ $currentIndex + 1 }} of {{ $totalInStatus }}</span>
            <x-heroicon-m-chevron-down class="size-3.5" x-bind:class="{ 'rotate-180': open }" />
        </button>

        {{-- Dropdown Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.outside="open = false"
            x-cloak
            class="absolute left-0 z-50 mt-1 w-96 origin-top-left rounded-lg bg-white dark:bg-gray-900 shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10"
        >
            {{-- Search Input (shown for 10+ items) --}}
            @if ($totalAll >= 10)
                <div class="p-2 border-b border-gray-950/5 dark:border-white/10">
                    <input
                        x-ref="searchInput"
                        x-model="search"
                        type="text"
                        placeholder="Search achievements..."
                        class="w-full px-2.5 py-1.5 text-sm bg-gray-50 dark:bg-white/5 border-0 rounded-md placeholder-neutral-400 dark:placeholder-neutral-500 focus:ring-2 focus:ring-primary-500"
                    >
                </div>
            @endif

            {{-- Achievement List --}}
            <div class="max-h-80 overflow-y-auto">
                <template x-for="section in sections" :key="section.key">
                    <div>
                        <div class="px-3 py-1.5 text-xs font-medium text-neutral-500 dark:text-neutral-400 bg-gray-50 dark:bg-gray-800 sticky top-0 z-10">
                            <span x-text="section.label"></span> (<span x-text="section.achievements.length"></span>)
                        </div>
                        <div class="py-1">
                            <template x-for="achievement in section.achievements" :key="section.key + '-' + achievement.id">
                                <a
                                    :href="'{{ $baseUrl }}'.replace('__ID__', achievement.id)"
                                    wire:navigate
                                    @click="open = false"
                                    class="flex items-start gap-2.5 px-3 py-2 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
                                    :class="{ 'bg-primary-50 dark:bg-primary-500/10': achievement.id === {{ $current->id }} }"
                                >
                                    <img
                                        :src="achievement.badge_url"
                                        alt=""
                                        class="size-8 rounded shrink-0"
                                    >
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-mono"
                                                :class="achievement.id === {{ $current->id }}
                                                    ? 'text-primary-500 dark:text-primary-400'
                                                    : 'text-neutral-400 dark:text-neutral-500'"
                                                x-text="achievement.id"
                                            ></span>
                                            <span
                                                class="text-sm truncate"
                                                :class="achievement.id === {{ $current->id }}
                                                    ? 'text-primary-700 dark:text-primary-300 font-medium'
                                                    : 'text-neutral-700 dark:text-neutral-200'"
                                                x-text="achievement.title"
                                            ></span>
                                        </div>
                                        <p
                                            class="text-xs text-neutral-500 dark:text-neutral-400 truncate mt-0.5"
                                            x-text="achievement.description"
                                        ></p>
                                    </div>
                                    <template x-if="achievement.id === {{ $current->id }}">
                                        <div class="size-1.5 rounded-full bg-primary-500 shrink-0 mt-1.5"></div>
                                    </template>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- Empty State --}}
                <template x-if="!hasResults">
                    <div class="px-3 py-4 text-sm text-neutral-500 dark:text-neutral-400 text-center">
                        No achievements match your search.
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Next Achievement Button --}}
    @if ($nextUrl)
        <a
            href="{{ $nextUrl }}"
            wire:navigate
            class="p-1 rounded hover:bg-gray-100 dark:hover:bg-white/5 text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200 transition-colors"
            title="Next {{ $isPromoted ? 'promoted' : 'unpromoted' }} achievement"
        >
            <x-heroicon-m-chevron-right class="size-4" />
        </a>
    @else
        <span class="p-1 text-neutral-300 dark:text-neutral-600 cursor-not-allowed">
            <x-heroicon-m-chevron-right class="size-4" />
        </span>
    @endif
</div>
