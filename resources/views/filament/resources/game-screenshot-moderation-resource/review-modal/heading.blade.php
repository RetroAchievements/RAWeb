<div class="flex items-start justify-between gap-4" style="width: min(76rem, calc(100vw - 4rem));">
    <div class="flex min-w-0 items-start gap-3">
        <span class="inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-success-500/15 text-success-600 dark:text-success-300">
            {!! svg($icon, 'size-5', ['aria-hidden' => 'true'])->toHtml() !!}
        </span>

        <div class="flex min-w-0 flex-col gap-1.5">
            <div class="text-lg font-bold leading-snug text-gray-950 dark:text-white">{{ $heading }}</div>

            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-medium leading-5 text-gray-600 dark:text-gray-300">
                @if ($gameUrl)
                    <a href="{{ $gameUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-gray-950 underline-offset-2 hover:underline dark:text-white">
                        {{ $gameTitle }}
                        @if ($systemName)
                            ({{ $systemName }})
                        @endif
                    </a>
                @else
                    <span class="font-semibold text-gray-950 dark:text-white">
                        {{ $gameTitle }}
                        @if ($systemName)
                            ({{ $systemName }})
                        @endif
                    </span>
                @endif

                <span>{{ $typeLabel }}</span>
                <span>{{ $resolution }}</span>
                <span>
                    by
                    @if ($submitterUrl)
                        <a href="{{ $submitterUrl }}" target="_blank" rel="noopener noreferrer" class="underline-offset-2 hover:underline">{{ $submitterLabel }}</a>
                    @else
                        {{ $submitterLabel }}
                    @endif
                </span>
                <span>{{ $submitted }}</span>
            </div>
        </div>
    </div>

    @if ($navigation !== [])
        <div class="flex shrink-0 gap-2 sm:me-3">
            @foreach ($navigation as $item)
                @if ($item['disabled'])
                    <button type="button" disabled class="rounded-md border border-gray-300/70 px-3 py-2 text-sm font-medium text-gray-400 opacity-60 dark:border-gray-700 dark:text-gray-500">
                        {{ $item['label'] }}
                    </button>
                @else
                    <button
                        type="button"
                        wire:click="replaceMountedScreenshotReview('{{ $item['recordKey'] }}', true)"
                        wire:loading.attr="disabled"
                        class="rounded-md border border-gray-300/80 px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-gray-400 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-wait disabled:opacity-60 dark:border-gray-700 dark:text-gray-200 dark:hover:border-gray-500 dark:hover:bg-gray-800"
                    >
                        {{ $item['label'] }}
                    </button>
                @endif
            @endforeach
        </div>
    @endif
</div>
