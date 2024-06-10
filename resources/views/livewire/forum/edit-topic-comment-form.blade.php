<?php

use App\Community\Livewire\Forms\ForumTopicCommentForm;

use function Livewire\Volt\{mount, form, state};

form(ForumTopicCommentForm::class);

// == props

state(['forumTopicComment']); // ForumTopicComment

// == state

// == actions

$save = function() {
    $this->form->update();
};

// == lifecycle

mount(function() {
    $this->form->setForumTopicComment($this->forumTopicComment);
});

?>

<div>
    <form wire:submit="save">
        <div class="flex flex-col gap-y-3">
            <x-base.form.input
                :value="$forumTopicComment->forumTopic->title"
                label="Title"
                inline
                disabled
            />

            <x-base.form.textarea
                wire:model="form.body"
                :value="$forumTopicComment->body"
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
