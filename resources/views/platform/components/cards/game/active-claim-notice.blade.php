@props([
    'activeDeveloperUsernames' => [],
    'activeDevelopersLabel' => '',
    'claimKind' => 'new',
])

<div class="flex gap-x-2 {{ count($activeDeveloperUsernames) === 1 ? 'items-center' : 'flex-col gap-y-1' }}">
    <div class="flex -space-x-3 items-center">
        <x-avatar-stack :usernames="$activeDeveloperUsernames" />
    </div>

    <p class="leading-4">
        @if ($claimKind === 'revision')
            Revision in progress
        @else
            Achievements under development
        @endif
        by {{ $activeDevelopersLabel }}.
    </p>
</div>