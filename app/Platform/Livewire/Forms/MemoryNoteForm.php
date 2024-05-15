<?php

namespace App\Platform\Livewire\Forms;

use App\Models\MemoryNote;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class MemoryNoteForm extends Form
{
    #[Validate('required')]
    public string $body = '';

    #[Locked]
    public ?MemoryNote $memoryNote;

    public function setMemoryNote(MemoryNote $memoryNote): void
    {
        $this->memoryNote = $memoryNote;
        $this->body = $memoryNote->body;
    }

    public function update(): void
    {
        $user = request()->user();

        if (!$user->can('update', $this->memoryNote)) {
            abort(401);
        }

        $validated = $this->validate();

        // Preserve whitespace correctly.
        $this->memoryNote->body = str_replace("\n", "\r\n", $validated['body']);
        $this->memoryNote->user_id = $user->id;

        $this->memoryNote->save();
    }
}
