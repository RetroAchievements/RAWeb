@props([
    'authorUsername' => '',
    'forumTopicTitle' => '',
    'hasDateTooltip' => false,
    'href' => '',
    'postedAt' => '',
    'summary' => '',
    'tooltipLabel' => null, // ?string
    'viewHref' => null, // ?string
    'viewLabel' => 'View',
    'view2Href' => null, // ?string
    'view2Label' => null, // ?string
])

<div class="embedded">
    <div class="relative flex justify-between items-center">
        <div>
            {!! userAvatar($authorUsername, iconSize: 16) !!}
            <span
                class="smalldate {{ $hasDateTooltip ? 'cursor-help' : '' }}"
                @if ($tooltipLabel) title="{{ $tooltipLabel }}" @endif
            >
                {{ $postedAt }}
            </span>
        </div>

        @if ($viewLabel !== '')
            <div class="flex flex-col">
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

    <p>in <a href="{{ $href }}">{{ $forumTopicTitle }}</a></p>

    <p class="comment text-overflow-wrap">
        {{ $summary }}
    </p>
</div>
