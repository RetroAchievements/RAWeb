@props([
    'forumTopicComment' => null, // ForumTopicComment
])

@use('App\Enums\Permissions')

@php
    $author = $forumTopicComment?->user;
@endphp

<div
    @class([
        'px-0.5 border-neutral-700 lg:py-2 lg:border-b-0 lg:border-r',
        $author ? 'border-b pb-2' : ''
    ])
>
    <div class="flex items-center w-full lg:flex-col lg:text-center lg:w-44">
        @if ($author)
            {!! 
                userAvatar(
                    $author,
                    label: false,
                    iconSize: 72,
                    iconClass: 'rounded-sm',
                    tooltip: true,
                )
            !!}

            <div class="ml-2 lg:ml-0">
                <div class="mb-0.5 lg:mt-1">
                    {!! userAvatar($author, icon: false, tooltip: true) !!}
                </div>

                {{-- TODO display visible role --}}
                @if ($author->getAttribute('Permissions') > Permissions::Registered)
                    <p class="smalltext !leading-4 !text-xs lg:!text-2xs">
                        {{ Permissions::toString($author->getAttribute('Permissions')) }}
                    </p>
                @endif

                @if ($author->Created && !$author->Deleted)
                    <p class="smalltext !leading-4 !text-xs lg:!text-2xs">
                        Joined {{ $author->Created->format('M j, Y') }}
                    </p>
                @endif
            </div>
        @endif
    </div>
</div>
