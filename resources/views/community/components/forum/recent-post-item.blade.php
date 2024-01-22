@props([
    'authorUsername' => '',
    'forumTopicTitle' => '',
    'hasDateTooltip' => false,
    'href' => '',
    'postedAt' => '',
    'summary' => '',
    'tooltipLabel' => null, // ?string
    'variant' => 'base', // 'base' | 'desktop-slim'
    'viewHref' => null, // ?string
    'viewLabel' => 'View',
    'view2Href' => null, // ?string
    'view2Label' => null, // ?string
])

<div class="embedded">
    <div class="relative flex justify-between items-center">
        <div @if ($variant === 'desktop-slim') class="lg:flex items-center gap-x-1" @endif>
            {!! userAvatar($authorUsername, iconSize: 16) !!}
            <span
                class="smalldate {{ $hasDateTooltip ? 'cursor-help' : '' }}"
                @if ($tooltipLabel) title="{{ $tooltipLabel }}" @endif
            >
                {{ $postedAt }}
            </span>

            @if ($variant === 'desktop-slim')
                <p class="hidden lg:block">in <a href="{{ $href }}">{{ $forumTopicTitle }}</a></p>
            @endif
        </div>

        @if ($viewLabel !== '')
            <div class="flex flex-col whitespace-nowrap">
                <a class="text-right text-2xs" href="{{ $viewHref ?? $href }}">
                    {{ $viewLabel }}
                </a>

                @if ($view2Href && $view2Label)
                    <a class="text-right text-2xs" href="{{ $view2Href }}">
                        {{ $view2Label }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    <p @if ($variant === 'desktop-slim') class="lg:hidden" @endif>
        in <a href="{{ $href }}">{{ $forumTopicTitle }}</a>
    </p>

    <p class="comment text-overflow-wrap">
        {{ $summary }}
    </p>
</div>
