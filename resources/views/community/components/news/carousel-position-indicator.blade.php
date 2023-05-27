<button
    @click="handleIndicatorClick({{ $index }})"
    @mouseenter="pause()"
    @mouseleave="resume()"
    :class="{
        'active bg-link bg-opacity-100 hover:bg-link focus:bg-link': activeIndex === {{ $index }},
        'hover:bg-link hover:bg-opacity-100 bg-embed focus:bg-embed lg:active:bg-link': activeIndex !== {{ $index }},
    }"
    class="carousel-indicator border !border-link bg-opacity-50 rounded-full w-3 h-3"
    aria-label="{{ "Go to slide " . ($index + 1) }}"
></button>