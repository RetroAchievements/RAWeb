@props([
    'forumTopic' => null, // ForumTopic
    'forumTopicComment' => null, // ForumTopicComment
    'threadPostNumber' => 0,
    'variant' => 'base', // 'base' | 'preview' | 'highlight'
])

@php
    $canManage = request()->user()?->can('manage', App\Models\ForumTopicComment::class) ?? false;
@endphp

<x-forum.topic-comment.container :$forumTopicComment :$variant>
    <x-forum.topic-comment.author-box :$forumTopicComment />

    <div @class(['comment w-full lg:py-0, lg:px-6', $variant === 'preview' ? 'py-2' : 'pt-2 pb-4'])>
        <div
            @class([
                'w-full mb-4 lg:mb-3 gap-x-2 flex justify-between',
                !$forumTopicComment?->Authorised && $canManage
                    ? 'flex-col items-start gap-y-2 sm:flex-row'
                    : 'items-center',
            ])
        >
            @if ($variant === 'preview')
                <p class="smalltext !leading-[14px]">Preview</p>
            @else
                <div class="flex items-center gap-x-2">
                    <x-forum.topic-comment.meta :$forumTopicComment />
                </div>

                <div class="flex items-center gap-x-1 lg:-mx-4 lg:pl-4 lg:w-[calc(100% + 32px)]">
                    @if (!$forumTopicComment->Authorised && $canManage)
                        <x-forum.topic-comment.manage :$forumTopicComment />
                    @endif

                    @can('update', $forumTopicComment)
                        <a
                            href="{{ route('forum.post.edit', ['forumTopicComment' => $forumTopicComment]) }}"
                            class='btn p-1 lg:text-xs'
                        >
                            Edit
                        </a>
                    @endcan

                    <x-forum.topic-comment.copy-link-button :$forumTopicComment :$threadPostNumber />
                </div>
            @endif
        </div>

        {{ $slot }}
    </div>
</x-forum.topic-comment.container>
