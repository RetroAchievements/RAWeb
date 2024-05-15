<?php

use App\Platform\Livewire\Forms\MemoryNoteForm;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\{form, mount, state};

form(MemoryNoteForm::class);

// == props
state(['memoryNote']); // MemoryNote

// == state
state(['user'])->locked();

// == actions
$save = function() {
    if ($this->form->body === '') {
        $this->delete();

        return;
    }

    $this->form->update();
    $this->dispatch("editComplete-{$this->memoryNote->id}");
    
    $this->dispatch('flashSuccess', message: 'Updated successfully.');
};

$delete = function() {
    $this->authorize('delete', $this->memoryNote);

    $this->memoryNote->body = '';
    $this->memoryNote->user_id = $this->user->id;

    $this->memoryNote->save();
    // TODO $this->memoryNote->delete();

    $this->dispatch("deleteComplete-{$this->memoryNote->id}");
    $this->dispatch("deleteComplete");
    
    $this->dispatch('flashSuccess', message: 'Deleted successfully.');
};

// == lifecycle
mount(function() {
    $this->user = Auth::user();

    $this->form->setMemoryNote($this->memoryNote);
});

?>

<div x-data="{
    textareaValue: $wire.entangle('form.body'),
    isEmpty: function() { return this.textareaValue === ''; }
}">
    <form @submit.prevent>
        <textarea
            class="w-full font-mono leading-5 p-0 border-0"
            wire:model="form.body"
            x-model="textareaValue"
            x-elastic
        >{{ $memoryNote->body }}</textarea>

        <div id="dirty-monitor" wire:dirty.class="is-dirty"></div>

        <div class="w-full flex gap-x-2 items-center justify-end">
            @can('delete', $memoryNote)
                <button
                    class="btn btn-danger"
                    wire:click="delete"
                    wire:confirm="Are you sure you want to delete this note? It will be irreversibly lost."
                >
                    Delete
                </button>
            @endcan

            <button
                class="btn"
                x-on:click="window.beginSave(@this, {{ $this->user->is($this->memoryNote->user) }})"
                disabled
                wire:dirty.remove.attr="disabled"
            >
                Save
            </button>
        </div>
    </form>
</div>

{{-- 
    Use @assets to only mount this <script> tag once, even if it's used on thousands
    of these components on the current page. This blocks access to $wire, so inner
    functions will need a ref to the current Livewire component.
--}}
@assets
<script>
/**
 * @param {Object} livewireRef
 * @param {boolean} isMemoryNoteAuthor
 */
function beginSave(livewireRef, isMemoryNoteAuthor) {
    const wouldSaveCauseDeletion = livewireRef.form.body === '';
    if (wouldSaveCauseDeletion) {
        if (confirm('Are you sure you want to delete this note? It will be irreversibly lost.')) {
            livewireRef.save();

            return;
        }
    }

    if (!isMemoryNoteAuthor) {
        if (confirm('Are you sure you want to update this note? You will become the author of this note.')) {
            livewireRef.save();

            return;
        }
    }

    // If we made it here, we don't need to confirm anything with the user. Just try to save.
    livewireRef.save();
}
</script>
@endassets
