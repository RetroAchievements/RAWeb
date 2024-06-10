<?php

use App\Community\Livewire\Forms\ForumTopicForm;
use App\Enums\Permissions;

use function Livewire\Volt\{mount, form, state};

form(ForumTopicForm::class);

// == props

state(['forumTopic'])->locked(); // ForumTopic

// == state

// == actions

$updateTopicTitle = function() {
    $this->form->updateTitle();
};

$updateTopicRequiredPermissions = function() {
    $this->form->updateRequiredPermissions();
};

$deleteTopic = function() {
    $this->form->delete();
};

// == lifecycle

mount(function() {
    $this->form->setForumTopic($this->forumTopic);
});

?>

{{-- TODO restyle this component --}}
<div x-data="{ 'title': $wire.entangle('form.title') }">
    <x-hidden-controls-toggle-button class="btn">
        Options
    </x-hidden-controls-toggle-button>

    <x-hidden-controls class="hidden mb-2" innerClass="bg-embed p-4 rounded-b rounded-tr">
        <div class="flex flex-col gap-4">
            <x-base.form wire:submit="updateTopicTitle">
                <div class="flex flex-col gap-1 md:max-w-96">
                    <x-base.form.input
                        label="Change Topic Title:"
                        maxlength="255"
                        wire:model="form.title"
                        name="form.title"
                    />
                    
                    <div class="flex w-full justify-end">
                        <button
                            type="submit"
                            class="btn"
                            x-bind:disabled="!title"
                        >
                            Submit
                        </button>
                    </div>
                </div>
            </x-base.form>

            @can('manage', $forumTopic)
                <x-base.form wire:submit="updateTopicRequiredPermissions">
                    <div class="flex flex-col gap-2 md:max-w-96">
                        <div class="flex flex-col">
                            <label for="required-permissions-field" class="mb-0">
                                Restrict Topic:
                            </label>
                            <select
                                id="required-permissions-field"
                                wire:model="form.requiredPermissions"
                            >
                                @foreach (Permissions::assignable() as $selectablePermission)
                                    <option value="{{ $selectablePermission }}">
                                        {{ Permissions::toString($selectablePermission) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex w-full justify-end">
                            <button
                                type="submit"
                                class="btn"
                            >
                                Change Minimum Permissions
                            </button>
                        </div>
                    </div>
                </x-base.form>
            @endcan

            @can('delete', $forumTopic)
                <div class="flex">
                    <button
                        class="btn btn-danger"
                        wire:click="deleteTopic"
                        wire:confirm="Are you sure you want to delete this topic?"
                    >
                        Delete Topic
                    </button>
                </div>
            @endcan
        </div>
    </x-hidden-controls>
</div>
