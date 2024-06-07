<?php

use App\Community\Livewire\Forms\ForumTopicForm;

use function Livewire\Volt\{mount, form, state};

form(ForumTopicForm::class);

// == props
state(['forum']); // Forum

// == state

// == actions
$save = function() {
    $this->form->store();
};

// == lifecycle
mount(function() {
    $this->form->setForum($this->forum);
});
?>

<div>
    <form wire:submit="save">
        <div class="flex flex-col gap-y-3">
            <x-base.form.input
                wire:model="form.title"
                name="form.title"
                label="Title"
                inline
            />

            <x-base.form.textarea
                wire:model="form.body"
                id="input_compose"
                maxlength="60000"
                name="form.body"
                label="Body"
                rows="22"
                placeholder="Don't share links to copyrighted ROMs."
                inline
                required-silent
                richText
            >
                <x-slot name="formActions">
                    <button type="submit" class="btn">Submit</button>
                </x-slot>
            </x-base.form.textarea>
        </div>
    </form>

    <div id="post-preview-input_compose"></div>
</div>
