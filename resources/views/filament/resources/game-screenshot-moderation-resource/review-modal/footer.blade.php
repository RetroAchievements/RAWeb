<div @class([
    'grid w-full grid-cols-1 gap-3',
    'md:grid-cols-2' => count($cards) >= 2,
    'xl:grid-cols-3' => count($cards) >= 3,
])>
    @foreach ($cards as $card)
        @php
            $toneClasses = 'bg-white text-gray-950 dark:bg-gray-900 dark:text-white ' . match (true) {
                $card['tone'] === 'primary' && $card['recommended'] => 'border-success-400/90 hover:border-success-400 hover:bg-success-50 dark:border-success-300/80 dark:hover:bg-success-500/10',
                $card['tone'] === 'warning' && $card['recommended'] => 'border-warning-400/90 hover:border-warning-400 hover:bg-warning-50 dark:border-warning-300/80 dark:hover:bg-warning-500/10',
                $card['tone'] === 'danger' && $card['recommended'] => 'border-danger-400/90 hover:border-danger-400 hover:bg-danger-50 dark:border-danger-300/80 dark:hover:bg-danger-500/10',
                $card['tone'] === 'primary' => 'border-success-500/45 hover:border-success-500 hover:bg-success-50 dark:hover:bg-success-500/10',
                $card['tone'] === 'warning' => 'border-warning-500/45 hover:border-warning-500 hover:bg-warning-50 dark:hover:bg-warning-500/10',
                $card['tone'] === 'danger' => 'border-danger-500/45 hover:border-danger-500 hover:bg-danger-50 dark:hover:bg-danger-500/10',
                default => 'border-gray-300 hover:border-gray-400 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800',
            };
            [$iconClasses, $detailClasses] = match ($card['tone']) {
                'primary' => ['text-success-600 dark:text-success-300', 'text-success-700 dark:text-success-300'],
                'warning' => ['text-warning-600 dark:text-warning-300', 'text-warning-700 dark:text-warning-300'],
                'danger' => ['text-danger-600 dark:text-danger-300', 'text-danger-700 dark:text-danger-300'],
                default => ['text-gray-500 dark:text-gray-300', 'text-gray-600 dark:text-gray-300'],
            };
        @endphp

        <button
            type="button"
            wire:click="{!! $card['wireClick'] !!}"
            wire:loading.attr="disabled"
            @class([
                'flex min-h-[86px] w-full flex-col items-stretch justify-start rounded-lg border p-3 text-left focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 disabled:cursor-wait disabled:opacity-60',
                $toneClasses,
            ])
        >
            <strong class="flex items-center gap-2 text-sm font-bold leading-snug">
                {!! svg($card['icon'], 'size-5 shrink-0 ' . $iconClasses, ['aria-hidden' => 'true', 'data-decision-icon' => $card['icon']])->toHtml() !!}
                <span>{{ $card['title'] }}</span>

                @if ($card['recommended'])
                    <span
                        x-tooltip="{ content: @js($suggestedPathTooltip), theme: $store.theme }"
                        aria-label="Suggested path details"
                        class="ms-auto rounded-full border border-current px-2 py-0.5 text-[0.7rem] font-semibold leading-4 {{ $detailClasses }}"
                    >
                        Suggested path
                    </span>
                @endif
            </strong>

            <span class="mt-1 block text-xs leading-5 text-gray-500 dark:text-gray-400">{{ $card['help'] }}</span>

            @if ($card['detail'])
                <span class="mt-1 block text-xs leading-5 {{ $detailClasses }}">{{ $card['detail'] }}</span>
            @endif
        </button>
    @endforeach
</div>
