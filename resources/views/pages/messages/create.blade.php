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
        <x-form action="{{ route('message.store') }}" validate>
            <x-base.form.input.user name="recipient" value="{{ $toUser }}" requiredSilent inline />
            <x-base.form.input name="title" label="{{ __('Subject') }}" requiredSilent inline />
            <x-base.form.textarea
                name="body"
                requiredSilent
                inline
                richText
                maxlength="60000"
                placeholder="Enter your message here..."
            />
            <x-form-actions inline />
        </x-form>
    </x-section>
</x-app-layout>