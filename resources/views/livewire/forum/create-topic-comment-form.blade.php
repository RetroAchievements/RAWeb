<?php

use App\Community\Livewire\Forms\ForumTopicCommentForm;

use function Livewire\Volt\{mount, form, state};

form(ForumTopicCommentForm::class);

// == props

state(['forumTopic'])->locked(); // ForumTopic

// == state

// == actions

$save = function() {
    $this->form->store();
};

// == lifecycle

mount(function() {
    $this->form->setForumTopic($this->forumTopic);
});

?>

<div>
    <x-base.form wire:submit="save" validate>
        <div class="flex flex-col gap-y-3">
            <x-base.form.textarea
                :isLabelVisible="false"
                wire:model="form.body"
                id="input_quickreply"
                maxlength="60000"
                name="form.body"
                label="Reply"
                rows="10"
                richText
                placeholder="Don't share links to copyrighted ROMs."
            >
                <x-slot name="formActions">
                    <button
                        type="submit"
                        class="btn"
                        disabled
                        wire:dirty.remove.attr="disabled"
                    >
                        Submit
                    </button>
                </x-slot>
            </x-base.form.textarea>
        </div>
    </x-base.form>

    <div id="post-preview-input_quickreply"></div>
</div>
