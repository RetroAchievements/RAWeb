<div class="flex min-w-0 flex-col gap-1">
    @if ($gameUrl)
        <a href="{{ $gameUrl }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-gray-950 underline-offset-2 hover:underline dark:text-white">
            {{ $gameTitle }}
        </a>
    @else
        <span class="font-semibold text-gray-950 dark:text-white">{{ $gameTitle }}</span>
    @endif

    @if ($systemName)
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $systemName }}</span>
    @endif

    @if ($cues !== [])
        <div class="flex flex-wrap gap-1">
            @foreach ($cues as $cue)
                @include('filament.resources.game-screenshot-moderation-resource.partials.cue-badge', $cue)
            @endforeach
        </div>
    @endif
</div>
