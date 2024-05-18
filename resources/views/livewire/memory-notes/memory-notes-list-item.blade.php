<?php

use App\Platform\Livewire\Forms\MemoryNoteForm;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\{on, state};

// == props
state(['memoryNote']); // MemoryNote

// == state
state(['isEditing' => false]);
state(['wasDeleted' => false]);

// == actions

// == lifecycle
on(['editComplete-{memoryNote.id}' => function() {
    $this->isEditing = false;
}]);

on(['deleteComplete-{memoryNote.id}' => function() {
    $this->wasDeleted = true;
}]);

?>

<tr
    class="[&>td]:align-top"
    :class="{ 'hidden': wasDeleted }"
    x-data="{
        isEditing: $wire.entangle('isEditing'),
        isEditInitialized: false,
        wasDeleted: $wire.entangle('wasDeleted'),
    }"
>
    <td>
        <p class="font-mono font-bold sticky top-11">
            {{ $memoryNote->address_hex }}
        </p>
    </td>
    
    <td width="100%">
        <div x-show="isEditing">
            <template x-if="isEditInitialized">
                <livewire:memory-notes.memory-note-edit-form
                    :memoryNote="$memoryNote"
                />
            </template>
        </div>

        <p class="font-mono" x-show="!isEditing">
            {!! nl2br(e($memoryNote->body)) !!}
        </div>
    </td>
    
    <td>
        <div class="flex justify-center py-1">
            {!! 
                userAvatar(
                    $memoryNote->user->display_name,
                    label: false,
                    iconSize: 24,
                    iconClass: 'rounded-sm'
                )
            !!}
        </div>
    </td>
    
    @can('manage', App\Models\MemoryNote::class)
        <td class="text-right min-w-[80px]">
            @can('update', $memoryNote)
                <div class="mt-1">
                    <button
                        class="btn"
                        x-show="isEditing"
                        x-on:click="isEditing = false"
                        x-cloak
                    >
                        Cancel
                    </button>
                    
                    <button
                        class="btn"
                        x-show="!isEditing"
                        x-on:click="isEditInitialized = true; isEditing = true"
                    >
                        Edit
                    </button>
                </div>
            @endcan
        </td>
    @endcan
</tr>
