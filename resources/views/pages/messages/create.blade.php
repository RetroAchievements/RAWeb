<?php

use App\Models\Message;
use function Laravel\Folio\{middleware, name};

middleware(['auth', 'can:create,' . Message::class]);
name('message.create');

?>

@php
$toUser = request()->input('to') ?? '';
$subject = request()->input('subject') ?? '';
$message = request()->input('message') ?? '';
@endphp

<x-app-layout
    pageTitle="New Message"
    pageDescription="Create a new message"
>
    <x-message.breadcrumbs currentPage="New Message" />

    <div class="w-full flex gap-x-3">
        <h1 class="mt-[10px] w-full">New Message</h1>
    </div>

    <x-section>
        <x-base.form action="{{ route('message.store') }}" validate>
            <div class="flex flex-col gap-y-3">
                <x-base.form.user-select name="recipient" value="{{ $toUser }}" requiredSilent inline />
                <x-base.form.input name="title" label="{{ __('Subject') }}" value="{!! $subject !!}" requiredSilent inline />
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
