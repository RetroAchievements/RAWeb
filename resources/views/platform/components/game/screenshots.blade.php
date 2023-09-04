@props(['titleImageSrc', 'ingameImageSrc'])

<script>
function carousel() {
    return {
        /**
         * @param {string} targetId
         */
        scrollTo(targetId) {
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                this.$refs.carousel.scrollLeft = targetEl.offsetLeft;
            }
        }
    }
}
</script>

<!-- XS Only -->
<div class="relative sm:hidden" x-data="carousel()">
    <div x-ref="carousel" class="-mx-5 my-6 flex snap-x snap-mandatory overflow-x-scroll scroll-smooth">
        <div id="title-screenshot" class="box-content flex w-full flex-none snap-start">
            <img class="w-full" src="{{ $titleImageSrc }}" alt="Title screenshot">
        </div>

        <div id="ingame-screenshot" class="box-content flex w-full flex-none snap-start">
            <img class="w-full" src="{{ $ingameImageSrc }}" alt="In-game screenshot">
        </div>
    </div>

    <div class="absolute right-1/2 translate-x-1/2 bottom-2" @click.prevent="">
        <a href="#title-screenshot" @click.prevent="scrollTo('title-screenshot')" class="inline-block w-4 h-4 rounded-full outline-none bg-neutral-100 border-2 border-neutral-800 cursor-pointer">
            <span class="sr-only">Title screenshot</span>
        </a>
        <a href="#ingame-screenshot" @click.prevent="scrollTo('ingame-screenshot')" class="inline-block w-4 h-4 rounded-full outline-none bg-neutral-100 border-2 border-neutral-800 cursor-pointer">
            <span class="sr-only">Ingame screenshot</span>
        </a>
    </div>
</div>

<!-- SM+ -->
<div class="
    hidden sm:flex justify-around items-center mb-3 px-6 pt-2 pb-2.5 w-full
    bg-zinc-900/50 light:bg-embed border border-embed-highlight gap-y-1 gap-x-5
    xl:px-4 xl:py-2 xl:rounded-lg xl:mx-0 xl:w-full xl:min-h-[180px]
"
>
    <div class="flex justify-center items-center">
        <img class="w-full rounded-sm" src="{{ $titleImageSrc }}" alt="Title screenshot">
    </div>

    <div class="flex justify-center items-center">
        <img class="w-full rounded-sm" src="{{ $ingameImageSrc }}" alt="In-game screenshot">
    </div>
</div>