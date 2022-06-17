<x-dynamic-component
    :component="request()->is('livewire*') ? 'PromptLayout' : 'AppLayout'"
    :page-title="$pageTitle"
>
    <?php
    $status = [
        'assets/images/cheevo/amazed.webp',
        'assets/images/cheevo/angry.webp',
        'assets/images/cheevo/confused.webp',
        'assets/images/cheevo/cute.webp',
        'assets/images/cheevo/popcorn.webp',
        'assets/images/cheevo/sad.webp',
        'assets/images/cheevo/thinking.webp',
    ];
    $image ??= collect($status)->random();
    ?>
    <div class="flex flex-col justify-center md:h-96 md:w-96 mx-auto items-center">
        @if($image ?? null)
            <img class="mb-3" src="{{ asset($image) }}" style="width: 128px" alt="@yield('message')">
        @endif
        <h3>{{ $title ?? $pageTitle ?? ''  }}</h3>
    </div>
</x-dynamic-component>
