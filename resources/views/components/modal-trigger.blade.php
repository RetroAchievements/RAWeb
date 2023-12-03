@props([
    'buttonLabel' => 'buttonLabel',
    'modalTitleLabel' => 'modalTitleLabel',
    
    'disabled' => false,
    'resourceApiRoute' => '', // Optional
    'resourceId' => null, // Optional
    'resourceContext' => null, // Optional
    'trigger' => null, // Optional, provide a custom button
])

<div
    @if (!$disabled)
        x-data="window.modalComponent('{{ $resourceApiRoute }}', '{{ $resourceId }}', '{{ $resourceContext }}')"
        @keydown.escape.window="closeModal"
    @endif
    class="relative w-auto h-auto inline"
>
    @isset ($trigger)
        <button @click="openModal" @mouseenter="fetchDynamicHtmlContent" {{ $disabled ? 'disabled' : '' }}>
            {{ $trigger }}
        </button>
    @else
        <button
            type="button"
            class="btn inline"
            @click="openModal"
            @mouseenter="fetchDynamicHtmlContent"
            {{ $disabled ? 'disabled' : '' }}
        >
            {{ $buttonLabel }}
        </button>
    @endisset

    <template x-teleport="body">
        <div
            class="fixed top-0 left-0 z-[99] flex items-center justify-center w-screen h-screen"
            x-show="isModalOpen"
            x-trap.inert.noscroll="isModalOpen"
            x-cloak
        >
            <!-- Backdrop -->
            <div
                x-show="isModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="closeModal"
                class="absolute inset-0 w-full h-full bg-black/70 backdrop-blur-[2px] backdrop-grayscale">
            </div>

            <div
                x-show="isModalOpen"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="
                    bg-box-bg relative w-full h-screen max-h-screen overflow-y-auto
                    sm:h-auto py-6 px-7 sm:max-w-lg md:max-w-[845px] sm:rounded-lg sm:max-h-[60vh]
                "
            >
                <div class="flex items-center justify-between pb-2">
                    <p class="text-lg font-semibold mb-4">{{ $modalTitleLabel }}</p>
                    <button
                        aria-label="Close dialog"
                        @click="closeModal"
                        class="
                            fixed sm:absolute top-0 right-0 z-10 flex items-center justify-center w-8 h-8 mt-5 mr-5 text-link 
                            rounded-full hover:text-link-hover bg-embed border border-embed-highlight hover:border-link-hover
                        "
                    >
                        <title class="sr-only">Close</title>
                        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="relative w-auto">
                    @if ($resourceApiRoute)
                        <div x-html="dynamicHtmlContent"></div>
                    @else
                        {{ $slot }}
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
