<?php

use App\Models\Message;
use App\Models\User;
use Illuminate\View\View;

use function Laravel\Folio\{middleware, name, render};

middleware(['auth', 'can:create,' . Message::class]); // TODO add 'verified' middleware
name('message.create');

render(function (View $view) {
    $to = request()->input('to') ?? '';
    $toUser = null;
    if ($to) {
        $toUser = User::whereName($to)->first();
    }

    return $view->with([
        'message' => request()->input('message') ?? '',
        'subject' => request()->input('subject') ?? '',
        'templateKind' => request()->input('templateKind') ?? null,
        'toUsername' => $to,
        'toUser' => $toUser,
    ]);
});

?>

@props([
    'message' => '',
    'subject' => '',
    'templateKind' => null, // 'manual-unlock' | 'writing-error' | 'misclassification' | 'unwelcome-concept' | 'achievement-issue' | null
    'toUsername' => '',
    'toUser' => null, // ?User
])

<x-app-layout
    pageTitle="New Message"
    pageDescription="Create a new message"
>
    <x-message.breadcrumbs currentPage="New Message" />

    <div class="w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">New Message</h1>
    </div>

    @if ($templateKind)
        <x-message.template-kind-alert :templateKind="$templateKind" />
    @endif

    <x-section>
        <x-base.form action="{{ route('message.store') }}" validate>
            <div class="flex flex-col gap-y-3">
                <x-base.form.user-select
                    :disabled="!!$templateKind"
                    initialStableUsername="{{ $toUser?->username }}"
                    name="recipient"
                    value="{{ $toUsername }}"
                    requiredSilent
                    inline
                />
                @if ($templateKind)
                    <input type="hidden" name="recipient" value="{{ $toUsername }}">
                @endif

                <x-base.form.input
                    :disabled="!!$templateKind"
                    name="title"
                    label="{{ __('Subject') }}"
                    value="{!! $subject !!}"
                    requiredSilent
                    inline
                />
                @if ($templateKind)
                    <input type="hidden" name="title" value="{!! $subject !!}">
                @endif
                
                <x-base.form.textarea
                    id="input_compose"
                    name="body"
                    requiredSilent
                    inline
                    richText
                    maxlength="60000"
                    placeholder="Enter your message here..."
                    value="{!! $message !!}"
                >
                    <x-slot name="formActions">
                        <x-base.form-actions />
                    </x-slot>
                </x-base.form.textarea>
            </div>
        </x-base.form>

        <div id="post-preview-input_compose"></div>
    </x-section>
</x-app-layout>
