<?php

use function Livewire\Volt\{mount, on, state};

// == props
state(['game']); // Game
state(['memoryNotes']); // Collection<MemoryNote>

// == state
state(['currentCount']);

// == actions

// == lifecycle
mount(function() {
    $this->currentCount = $this->memoryNotes->count();
});

on(['deleteComplete' => function() {
    $this->currentCount--;
}]);
?>

<div>
    <p class="mb-2">
        There {{ $currentCount === 1 ? 'is' : 'are' }} currently
        <span class="font-bold">{{ $currentCount }}</span>
        code {{  $currentCount === 1 ? 'note' : 'notes' }}.
    </p>

    <table class="table-highlight">
        <thead>
            <tr class="do-not-highlight">
                <th>Address</th>
                <th>Body</th>
                <th>Author</th>

                @can('manage', App\Models\MemoryNote::class)
                    <th aria-label="tools"></td>
                @endcan
            </tr>
        </thead>

        <tbody>
            @foreach ($memoryNotes as $memoryNote)
                <livewire:memory-notes.memory-notes-list-item
                    :key="$memoryNote->id"
                    :memoryNote="$memoryNote"
                />
            @endforeach
        </tbody>
    </table>
</div>
