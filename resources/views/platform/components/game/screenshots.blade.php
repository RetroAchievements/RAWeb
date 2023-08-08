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

<div class="relative" x-data="carousel()">
    <div x-ref="carousel" class="-mx-5 my-6 sm:mt-0 sm:mb-3 sm:mx-0 flex sm:justify-around sm:w-full sm:gap-x-5 snap-x sm:snap-none sm:overflow-x-auto snap-mandatory overflow-x-scroll scroll-smooth">
        <div id="title-screenshot" class="box-content flex w-full sm:w-auto flex-none snap-start sm:items-center">
            <img class="w-full sm:rounded-sm" src="{{ $titleImageSrc }}" alt="Title screenshot">
        </div>

        <div id="ingame-screenshot" class="box-content flex w-full sm:w-auto flex-none snap-start sm:items-center">
            <img class="w-full sm:rounded-sm" src="{{ $ingameImageSrc }}" alt="In-game screenshot">
        </div>
    </div>

    <div class="absolute right-1/2 translate-x-1/2 bottom-2 sm:hidden" @click.prevent="">
        <a href="#title-screenshot" @click.prevent="scrollTo('title-screenshot')" class="inline-block w-4 h-4 rounded-full outline-none bg-neutral-100 border-2 border-neutral-800 cursor-pointer">
            <span class="sr-only">Title screenshot</span>
        </a>
        <a href="#ingame-screenshot" @click.prevent="scrollTo('ingame-screenshot')" class="inline-block w-4 h-4 rounded-full outline-none bg-neutral-100 border-2 border-neutral-800 cursor-pointer">
            <span class="sr-only">Ingame screenshot</span>
        </a>
    </div>
</div>
