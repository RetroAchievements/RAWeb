<div class="mt-3 flex items-start gap-4">
    @if ($submissionUrl)
        <a href="{{ $submissionUrl }}" target="_blank" rel="noopener noreferrer" class="block shrink-0 rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
            <img src="{{ $submissionUrl }}" alt="{{ $typeLabel }} submission" class="block w-32 max-w-[8rem] rounded object-contain" />
        </a>
    @endif

    <div class="min-w-0">
        <p class="m-0 font-semibold text-gray-950 dark:text-white">{{ $gameLabel }}</p>
        <p class="m-0 mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $typeLabel }} submission
            @if ($submissionResolution)
                · {{ $submissionResolution }}
            @endif
        </p>
    </div>
</div>
