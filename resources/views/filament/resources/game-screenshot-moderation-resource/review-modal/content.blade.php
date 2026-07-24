@php
    $panels = [$currentPanel, $candidatePanel];
    $contextCards = array_filter([
        ['type' => 'primaries', 'data' => $currentPrimaries],
        $allPendingForGame ? ['type' => 'allPendingForGame', 'data' => $allPendingForGame] : null,
        $approvedIngame ? ['type' => 'ingame', 'data' => $approvedIngame] : null,
    ]);
    $galleryZoomItems = [];
    $galleryZoomIndexByItem = [];

    if ($approvedIngame) {
        foreach ($approvedIngame['items'] as $itemIndex => $galleryItem) {
            if (! $galleryItem['url']) {
                continue;
            }

            $galleryZoomIndexByItem[$itemIndex] = count($galleryZoomItems);
            $galleryZoomItems[] = [
                'url' => $galleryItem['url'],
                'label' => $galleryItem['label'],
                'imageRendering' => $galleryItem['imageRendering'],
            ];
        }
    }

    $checkerboardStyle = 'background-color: #030712; background-image: linear-gradient(45deg, rgba(148, 163, 184, 0.08) 25%, transparent 25%), linear-gradient(-45deg, rgba(148, 163, 184, 0.08) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, rgba(148, 163, 184, 0.08) 75%), linear-gradient(-45deg, transparent 75%, rgba(148, 163, 184, 0.08) 75%); background-size: 18px 18px; background-position: 0 0, 0 9px, 9px -9px, -9px 0;';
@endphp

<div
    wire:key="screenshot-review-zoom-{{ $recordKey }}"
    class="flex flex-col gap-4 border-t border-gray-200 pt-5 dark:border-gray-700/70"
    x-data="{
        zoomedUrl: null,
        zoomedLabel: '',
        zoomedImageRendering: null,
        galleryZoomItems: @js($galleryZoomItems),
        zoomItems: [],
        zoomIndex: 0,
        zoomMode: 'scale',
        zoomScale: {{ $isPixelated ? 4 : 2 }},
        defaultScale: {{ $isPixelated ? 4 : 2 }},
        max4xDimension: 1024,
        naturalWidth: 0,
        naturalHeight: 0,
        maxViewportWidth() {
            return window.innerWidth - 64;
        },
        maxViewportHeight() {
            return window.innerHeight - 128;
        },
        needsFitZoom() {
            return this.naturalWidth > this.maxViewportWidth() || this.naturalHeight > this.maxViewportHeight();
        },
        bestFittingScale() {
            const fitting = this.zoomLevels().filter((level) =>
                this.naturalWidth * level <= this.maxViewportWidth() && this.naturalHeight * level <= this.maxViewportHeight()
            );

            return fitting.length ? Math.min(Math.max(...fitting), this.defaultScale) : null;
        },
        zoomOptions() {
            return [...(this.needsFitZoom() ? ['fit'] : []), ...this.zoomLevels()];
        },
        zoomLevels() {
            return [1, 2, 4].filter((level) => level !== 4 || this.canUse4xZoom());
        },
        canUse4xZoom() {
            return Math.max(this.naturalWidth, this.naturalHeight) < this.max4xDimension;
        },
        isZoomSelected(option) {
            return option === 'fit' ? this.zoomMode === 'fit' : this.zoomMode === 'scale' && this.zoomScale === option;
        },
        setZoom(option) {
            this.zoomMode = option === 'fit' ? 'fit' : 'scale';
            if (option !== 'fit') {
                this.zoomScale = option;
            }
        },
        imageRenderingStyle(imageRendering) {
            return imageRendering ? `image-rendering: ${imageRendering};` : '';
        },
        zoomImageStyle() {
            return `${this.imageRenderingStyle(this.zoomedImageRendering)} border: 1px solid #404040; ${this.zoomMode === 'fit' ? 'max-width: calc(100vw - 4rem); max-height: calc(100vh - 8rem);' : ''}`;
        },
        applyZoomItem(item) {
            this.naturalWidth = 0;
            this.naturalHeight = 0;
            this.zoomMode = 'scale';
            this.zoomScale = this.defaultScale;
            this.zoomedLabel = item.label;
            this.zoomedImageRendering = item.imageRendering;
            this.zoomedUrl = item.url;
        },
        openZoom(url, label, imageRendering) {
            this.zoomItems = [];
            this.zoomIndex = 0;
            this.applyZoomItem({ url, label, imageRendering });
        },
        openGalleryZoom(index) {
            this.zoomItems = this.galleryZoomItems;
            this.zoomIndex = index;
            this.applyZoomItem(this.zoomItems[this.zoomIndex]);
        },
        canGoPrevious() {
            return this.zoomItems.length > 1 && this.zoomIndex > 0;
        },
        canGoNext() {
            return this.zoomItems.length > 1 && this.zoomIndex < this.zoomItems.length - 1;
        },
        navigateZoom(direction) {
            const nextIndex = this.zoomIndex + direction;

            if (nextIndex < 0 || nextIndex >= this.zoomItems.length) {
                return;
            }

            this.zoomIndex = nextIndex;
            this.applyZoomItem(this.zoomItems[this.zoomIndex]);
        },
        handleZoomKey(direction, event) {
            if (this.zoomedUrl === null || this.zoomItems.length <= 1) {
                return;
            }

            const canNavigate = direction === -1 ? this.canGoPrevious() : this.canGoNext();
            if (!canNavigate) {
                return;
            }

            event.preventDefault();
            this.navigateZoom(direction);
        },
        closeZoom() {
            this.zoomedUrl = null;
        },
        onZoomImageLoad(event) {
            this.naturalWidth = event.target.naturalWidth;
            this.naturalHeight = event.target.naturalHeight;
            const scale = this.bestFittingScale();
            if (scale === null) {
                this.zoomMode = 'fit';
            } else {
                this.zoomMode = 'scale';
                this.zoomScale = scale;
            }
        },
    }"
    @keydown.escape.window="closeZoom()"
    @keydown.left.window="handleZoomKey(-1, $event)"
    @keydown.right.window="handleZoomKey(1, $event)"
>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($panels as $panel)
            @php
                $panelImageRenderingStyle = $panel['imageRendering'] ? 'image-rendering: ' . $panel['imageRendering'] . ';' : '';
            @endphp

            <div class="min-w-0">
                <div class="min-h-[2.6rem] text-center">
                    <div class="font-bold text-gray-950 dark:text-white">{{ $panel['label'] }}</div>

                    @if ($panel['cues'] !== [])
                        <div class="mt-0.5 flex flex-col items-center gap-0.5">
                            @foreach ($panel['cues'] as $cue)
                                <div @class([
                                    'inline-flex items-center justify-center gap-1 text-sm font-medium',
                                    'text-danger-700 dark:text-danger-300' => $cue['tone'] === 'danger',
                                    'text-warning-700 dark:text-warning-300' => $cue['tone'] === 'warning',
                                    'text-success-700 dark:text-success-300' => $cue['tone'] === 'success',
                                    'text-gray-500 dark:text-gray-400' => ! in_array($cue['tone'], ['danger', 'warning', 'success'], true),
                                ])>
                                    {!! svg($cue['icon'], 'size-4 shrink-0', ['aria-hidden' => 'true'])->toHtml() !!}
                                    <span>{{ $cue['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if ($panel['url'])
                    <button
                        type="button"
                        @click="openZoom(@js($panel['url']), @js($panel['label']), @js($panel['imageRendering']))"
                        class="mt-2 flex w-full items-center justify-center overflow-hidden focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        style="min-height: 280px; max-height: 320px; padding: 0.35rem; border-radius: 0.25rem; {{ $checkerboardStyle }}"
                    >
                        <img
                            src="{{ $panel['url'] }}"
                            alt="{{ $panel['label'] }}"
                            style="{{ $panelImageRenderingStyle }} max-height: 310px; box-shadow: 0 0 0 1px rgba(2, 6, 23, 0.7);"
                            class="block h-full w-full cursor-zoom-in object-contain"
                        />
                    </button>
                @else
                    <div
                        class="mt-2 flex items-center justify-center text-gray-300"
                        style="min-height: 280px; border-radius: 0.25rem; {{ $checkerboardStyle }}"
                    >
                        {{ $panel['placeholder'] }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <template x-teleport="body">
        <div
            x-show="zoomedUrl !== null"
            x-cloak
            x-transition.opacity
            @click.self="closeZoom()"
            class="flex flex-col"
            style="position: fixed; inset: 0; z-index: 9999999; background-color: rgba(0, 0, 0, 0.92);"
        >
            <div
                class="flex shrink-0 items-center justify-between gap-3 px-6 py-3 text-neutral-100"
                style="background-color: #000; border-bottom: 1px solid #404040;"
            >
                <span x-text="zoomedLabel" class="truncate text-sm font-semibold"></span>

                <div class="flex items-center gap-2 text-sm">
                    <template x-if="zoomItems.length > 1">
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="navigateZoom(-1)"
                                :disabled="!canGoPrevious()"
                                class="rounded px-2.5 py-0.5 disabled:opacity-50"
                                style="border: 1px solid #404040;"
                            >
                                Previous
                            </button>
                            <span x-text="`${zoomIndex + 1} / ${zoomItems.length}`" class="tabular-nums"></span>
                            <button
                                type="button"
                                @click="navigateZoom(1)"
                                :disabled="!canGoNext()"
                                class="rounded px-2.5 py-0.5 disabled:opacity-50"
                                style="border: 1px solid #404040;"
                            >
                                Next
                            </button>
                        </div>
                    </template>
                    <template x-if="zoomItems.length > 1">
                        <span aria-hidden="true" class="flex h-5 items-center px-1.5">
                            <span class="h-5 border-l border-neutral-700"></span>
                        </span>
                    </template>
                    <template x-for="option in zoomOptions()" :key="option">
                        <button
                            type="button"
                            @click="setZoom(option)"
                            x-text="option === 'fit' ? 'Fit' : option + 'x'"
                            class="rounded px-2.5 py-0.5 tabular-nums"
                            :style="isZoomSelected(option)
                                ? 'border: 1px solid #d4d4d4; background-color: #d4d4d4; color: #0a0a0a;'
                                : 'border: 1px solid #404040;'"
                        ></button>
                    </template>
                    <span aria-hidden="true" class="flex h-5 items-center px-1.5">
                        <span class="h-5 border-l border-neutral-700"></span>
                    </span>
                    <button type="button" @click="closeZoom()" class="rounded px-2 py-0.5 hover:bg-neutral-800" style="border: 1px solid #404040;">
                        Close
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-auto" @click.self="closeZoom()">
                <div class="flex min-h-full w-max min-w-full items-center justify-center p-8" @click.self="closeZoom()">
                    <img
                        :src="zoomedUrl"
                        :alt="zoomedLabel"
                        :width="zoomMode === 'scale' && naturalWidth ? naturalWidth * zoomScale : null"
                        :height="zoomMode === 'scale' && naturalHeight ? naturalHeight * zoomScale : null"
                        :style="zoomImageStyle()"
                        @load="onZoomImageLoad($event)"
                        class="block max-w-none"
                    />
                </div>
            </div>
        </div>
    </template>

    <div @class([
        'grid grid-cols-1 gap-3',
        'md:grid-cols-2' => count($contextCards) >= 2,
        'xl:grid-cols-3' => count($contextCards) >= 3,
    ])>
        @foreach ($contextCards as $card)
            @if ($card['type'] === 'primaries')
                <section class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700/70 dark:bg-gray-900/60">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">Primary screenshots</div>

                    @if ($card['data'] === [])
                        <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">No primary screenshots yet</div>
                    @else
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($card['data'] as $item)
                                <div class="w-[76px] min-w-[76px] text-xs leading-tight">
                                    @if ($item['url'])
                                        @php
                                            $itemImageRenderingStyle = $item['imageRendering'] ? 'image-rendering: ' . $item['imageRendering'] . ';' : '';
                                        @endphp

                                        <button
                                            type="button"
                                            @click="openZoom(@js($item['url']), @js($item['label']), @js($item['imageRendering']))"
                                            class="block w-full cursor-zoom-in rounded bg-gray-950 transition hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                                            title="{{ $item['label'] }}"
                                        >
                                            <img src="{{ $item['url'] }}" alt="{{ $item['typeLabel'] }} primary" class="block h-11 w-[76px] rounded object-contain" style="{{ $itemImageRenderingStyle }}" />
                                        </button>
                                    @else
                                        <div class="h-11 w-[76px] rounded bg-gray-950"></div>
                                    @endif

                                    @if ($item['url'])
                                        <button
                                            type="button"
                                            @click="openZoom(@js($item['url']), @js($item['label']), @js($item['imageRendering']))"
                                            class="mt-1 block w-full cursor-zoom-in rounded text-left text-gray-700 transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-gray-300 dark:hover:bg-gray-800"
                                            title="{{ $item['label'] }}"
                                        >
                                            {{ $item['typeLabel'] }}<br>{{ $item['resolution'] }}
                                        </button>
                                    @else
                                        <span class="mt-1 block text-gray-700 dark:text-gray-300">{{ $item['typeLabel'] }}<br>{{ $item['resolution'] }}</span>
                                    @endif

                                    @if ($item['invalid'])
                                        <span class="mt-0.5 block text-warning-700 dark:text-warning-300">invalid size</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @elseif ($card['type'] === 'allPendingForGame')
                @php $allPendingCount = count($card['data']['items']); @endphp
                <section class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700/70 dark:bg-gray-900/60">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        All pending for this game{{ $allPendingCount > 3 ? ' (' . $allPendingCount . ')' : '' }}
                    </div>
                    <div class="mt-2 flex max-h-[152px] flex-col gap-1.5 overflow-y-auto pr-1">
                        @foreach ($card['data']['items'] as $item)
                            @if ($item['isCurrent'])
                                <div
                                    aria-current="true"
                                    class="flex w-full min-w-0 shrink-0 items-center gap-2 rounded-md bg-gray-100 p-1 text-left dark:bg-gray-800"
                                >
                            @else
                                <button
                                    type="button"
                                    wire:click="replaceMountedScreenshotReview('{{ $item['recordKey'] }}', false)"
                                    wire:loading.attr="disabled"
                                    class="flex w-full min-w-0 shrink-0 items-center gap-2 rounded-md p-1 text-left transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-wait disabled:opacity-60 dark:hover:bg-gray-800"
                                >
                            @endif

                            @if ($item['url'])
                                <img src="{{ $item['url'] }}" alt="" class="block h-[34px] w-14 shrink-0 rounded bg-gray-950 object-contain" />
                            @else
                                <span class="block h-[34px] w-14 shrink-0 rounded bg-gray-950"></span>
                            @endif

                            <span class="min-w-0 text-xs leading-tight">
                                <span class="block font-semibold text-gray-950 dark:text-white">{{ $item['typeLabel'] }} · {{ $item['resolution'] }}</span>
                                <span class="block truncate text-gray-500 dark:text-gray-400">by {{ $item['submitterLabel'] }}</span>
                            </span>
                            @if ($item['isCurrent'])
                                <span class="ml-auto shrink-0 text-[10px] font-medium text-gray-500 dark:text-gray-400">Viewing</span>
                            @endif

                            @if ($item['isCurrent'])
                                </div>
                            @else
                                </button>
                            @endif
                        @endforeach
                    </div>
                </section>
            @elseif ($card['type'] === 'ingame')
                <section class="rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700/70 dark:bg-gray-900/60">
                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Current in-game gallery
                        <a href="{{ $card['data']['mediaPageUrl'] }}" target="_blank" rel="noopener noreferrer" class="underline-offset-2 hover:underline">
                            ({{ $card['data']['count'] }} / {{ $card['data']['cap'] }})
                        </a>
                    </div>

                    @if ($card['data']['items'] !== [])
                        <div class="mt-2 flex max-h-[152px] flex-col gap-1.5 overflow-y-auto pr-1">
                            @foreach ($card['data']['items'] as $itemIndex => $item)
                                @php
                                    $itemImageRenderingStyle = $item['imageRendering'] ? 'image-rendering: ' . $item['imageRendering'] . ';' : '';
                                @endphp

                                <button
                                    type="button"
                                    @if ($item['url'])
                                        @click="openGalleryZoom({{ $galleryZoomIndexByItem[$itemIndex] }})"
                                    @endif
                                    class="flex w-full min-w-0 shrink-0 cursor-zoom-in items-center gap-2 rounded-md p-1 text-left transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-gray-800"
                                    title="{{ $item['label'] }}"
                                >
                                    @if ($item['url'])
                                        <img src="{{ $item['url'] }}" alt="" class="block h-[34px] w-14 shrink-0 rounded bg-gray-950 object-contain" style="{{ $itemImageRenderingStyle }}" />
                                    @else
                                        <span class="block h-[34px] w-14 shrink-0 rounded bg-gray-950"></span>
                                    @endif

                                    <span class="min-w-0 text-xs leading-tight">
                                        <span class="block font-semibold text-gray-950 dark:text-white">{{ $item['typeLabel'] }} · {{ $item['resolution'] }}</span>
                                        <span class="block truncate text-gray-500 dark:text-gray-400">by {{ $item['submitterLabel'] }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif
        @endforeach
    </div>
</div>
