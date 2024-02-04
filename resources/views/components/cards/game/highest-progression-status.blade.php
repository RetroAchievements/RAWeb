@props([
    'highestProgressionStatus' => 'beaten-hardcore',
    'highestProgressionAwardDate' => '',
    'isEvent' => false,
])

<div class="mt-1 mb-1.5 flex items-center gap-x-1">
    @if (!$isEvent)
        <div class="rounded-full w-2 h-2 {{ $highestProgressionStatus === 'mastered' ? 'bg-yellow-400' : 'bg-neutral-400' }}"></div>
    @endif

    <span>
        @if ($highestProgressionStatus === 'beaten-softcore')
            Beaten (softcore)
        @elseif ($highestProgressionStatus === 'beaten-hardcore')
            Beaten
        @elseif ($highestProgressionStatus === 'completed')
            Completed
        @elseif ($highestProgressionStatus === 'mastered')
            Mastered
        @else
            Awarded
        @endif

        {{ $highestProgressionAwardDate->format('j F Y') }}
    </span>
</div>