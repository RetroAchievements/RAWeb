<?php

use App\Community\Models\News;

$totalNewsCount = 10;

$newsData = News::orderByDesc('ID')->take($totalNewsCount)->get();
if ($newsData->isEmpty()) {
    return;
}
?>

<div class="mb-6 xl:flex xl:flex-col xl:items-center xl:bg-embed xl:rounded-lg xl:pt-1 xl:pb-4 xl:border xl:border-embed-highlight" aria-live="polite" aria-atomic="true" x-data="newsCarouselComponent()">
    <h2 class="sr-only">News</h2>

    <div 
        class="mt-2 relative h-[300px] max-h-[300px] sm:h-[270px] sm:max-h-[270px] xl:max-w-[700px]"
        @mouseenter="pause()"
        @mouseleave="resume()"
    >
        <div
            id="news-carousel-image-list"
            class="group h-[300px] sm:h-auto max-h-[300px] sm:max-h-[270px] flex snap-x snap-mandatory overflow-x-scroll sm:overflow-x-hidden scroll-smooth rounded-lg transition"
            @touchstart="pause()"
            @touchend="updateActiveIndex()"
        >
            @foreach ($newsData as $index => $news)
                <x-news.carousel-item :news="$news" :index="$index" :totalCount="$totalNewsCount" />
            @endforeach
        </div>

        <x-news.carousel-scroll-buttons />
    </div>

    <div class="mt-4 flex justify-center gap-x-1">
        @foreach ($newsData as $index => $news)
            <x-news.carousel-position-indicator :index="$index" />
        @endforeach
    </div>
</div>