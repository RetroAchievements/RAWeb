<div
    class="relative box-content flex w-full flex-none snap-start min-h-[270px]"
    role="group"
    aria-label="{{ "News item " . ($index + 1) }}"
    {{ $index === 0 ? 'id=first-news-item' : '' }}
    {{ $index === $totalCount - 1 ? 'id=last-news-item' : '' }}
>
    <img
        src="{{ $news->Image }}"
        alt="{{ $news->Title }} cover image"
        loading={{ $index === 0 ? 'eager' : 'lazy' }}
        decoding={{ $index === 0 ? 'sync' : 'async' }}
        importance={{ $index === 0 ? 'high' : 'auto' }}
        class="ease-out duration-[300ms] -z-1 h-full w-full object-cover opacity-50 group-hover:opacity-20 group-hover:blur-[2px] transition"
    >
    <div
        class="py-6 px-8 absolute top-0 left-0 w-full h-full"
        style="background: linear-gradient(180deg, #00000066 20%, #ffffff01 100%)"
    >
        <h4 class="shadowoutline lg:opacity-0 transition delay-100 duration-300 subpixel-antialiased {{ $index === 0 ? '!opacity-100' : '' }}">
            <a href="{{ $news->Link }}">{{ $news->Title }}</a>
        </h4>

        <p class="newstext shadowoutline lg:opacity-0 transition delay-300 duration-300 subpixel-antialiased {{ $index === 0 ? '!opacity-100' : '' }}">
            {!! $news->Payload !!}
        </p>

        <div class="hidden sm:flex flex-col newsauthor shadowoutline absolute bottom-2 right-2">
            {!! userAvatar($news->user->User, icon: false) !!}
            {{ $news->Timestamp->format('M j, Y') }}
        </div>
    </div>
</div>
