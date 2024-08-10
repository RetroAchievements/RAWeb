@props([
    'forumTopicComment' => null, // ForumTopicComment
    'variant' => 'base', // 'base' | 'preview' | 'highlight'
])

<div @class(['relative', $variant === 'preview' ? 'my-2' : ''])>
    <div id="{{ $forumTopicComment?->id }}" class="absolute left-0 h-px w-px" style="top: -64px;"></div>

    <div
        @class([
            $variant === 'highlight' ? 'highlight' : '',
            $variant === 'preview' ? 'py-2' : 'pb-3 pt-2',

            'relative w-[calc(100%+16px)] mt-3 -mx-2 px-1 bg-embed-highlight rounded-lg',
            'sm:w-full sm:mx-0 lg:flex',
            'odd:bg-embed',
        ])
    >
        {{ $slot }}
    </div>
</div>
