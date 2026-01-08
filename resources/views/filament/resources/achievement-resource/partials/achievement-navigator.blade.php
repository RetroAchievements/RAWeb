{{--
    This component provides compact inline navigation between achievements in the same set.

    @param array $navData - Navigation data from getAchievementSetNavigationData()
    @param string $pageType - 'view', 'edit', or 'logic' to determine navigation targets
--}}

@use('App\Filament\Resources\AchievementResource')

@php
    $achievements = $navData['achievements'];
    $current = $navData['current'];
    $currentIndex = $navData['currentIndex'];
    $total = $navData['total'];
    $previous = $navData['previous'];
    $next = $navData['next'];

    $getUrlForAchievement = fn ($achievement) => match ($pageType) {
        'edit' => AchievementResource::getUrl('edit', ['record' => $achievement]),
        'logic' => AchievementResource::getUrl('logic', ['record' => $achievement]),
        default => AchievementResource::getUrl('view', ['record' => $achievement]),
    };

    $prevUrl = $previous ? $getUrlForAchievement($previous) : null;
    $nextUrl = $next ? $getUrlForAchievement($next) : null;
@endphp

<div
    x-data="{
        open: false,
        search: '',
        get filteredAchievements() {
            if (!this.search.trim()) return @js($achievements->map(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'description' => $a->description, 'badge_url' => $a->badge_url])->values());
            const term = this.search.toLowerCase();
            return @js($achievements->map(fn ($a) => ['id' => $a->id, 'title' => $a->title, 'description' => $a->description, 'badge_url' => $a->badge_url])->values()).filter(a =>
                a.title.toLowerCase().includes(term) || a.id.toString().includes(term) || a.description.toLowerCase().includes(term)
            );
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
            title="Previous achievement"
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
            <span>Achievement {{ $currentIndex + 1 }} of {{ $total }}</span>
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
            @if ($total >= 10)
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
            <div class="max-h-64 overflow-y-auto py-1">
                <template x-for="achievement in filteredAchievements" :key="achievement.id">
                    @php
                        $baseUrl = match ($pageType) {
                            'edit' => \App\Filament\Resources\AchievementResource::getUrl('edit', ['record' => '__ID__']),
                            'logic' => \App\Filament\Resources\AchievementResource::getUrl('logic', ['record' => '__ID__']),
                            default => \App\Filament\Resources\AchievementResource::getUrl('view', ['record' => '__ID__']),
                        };
                    @endphp
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
    </div>

    {{-- Next Achievement Button --}}
    @if ($nextUrl)
        <a
            href="{{ $nextUrl }}"
            wire:navigate
            class="p-1 rounded hover:bg-gray-100 dark:hover:bg-white/5 text-neutral-500 hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200 transition-colors"
            title="Next achievement"
        >
            <x-heroicon-m-chevron-right class="size-4" />
        </a>
    @else
        <span class="p-1 text-neutral-300 dark:text-neutral-600 cursor-not-allowed">
            <x-heroicon-m-chevron-right class="size-4" />
        </span>
    @endif
</div>
