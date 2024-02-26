<?php

use function Laravel\Folio\{name};

name('demo');

?>
<x-demo-layout
    :page-title="__('Demo')"
>
    <x-slot name="breadcrumb">
        <li>
            <a href="{{ route('home') }}">
                Bread
            </a>
        </li>
        <li>
            Crumb
        </li>
    </x-slot>

    <x-slot name="header">
        <x-page-header :background="asset('assets/images/ra-icon.webp')">
            <x-slot name="title">
                <h2>Header Title</h2>
                {{--<p>Text in title -> actions will be below</p>--}}
            </x-slot>
            <x-slot name="actions">
                @foreach([null, 'warning', 'danger'] as $modifier)
                    <x-base.button class="{{ $modifier ? 'btn-' . $modifier : '' }}">
                        <x-fas-fire/>
                        Action
                    </x-base.button>
                @endforeach
            </x-slot>
            <x-slot name="stats">
                <x-page-header-stat label="Stat" :value="1234"/>
                <x-page-header-stat label="Stat" :value="5"/>
                <x-page-header-stat label="Stat" :value="67890"/>
                <x-page-header-stat label="Stat" :value="77777777"/>
                <x-page-header-stat label="Stat" :value="0.2" type="percent"/>
                <x-page-header-stat label="Stat" :value="2" type="percent" :fractionDigits="0"/>
                <x-page-header-stat label="Stat" :value="0.01" :fractionDigits="2" type="percent"/>
            </x-slot>
            ...
        </x-page-header>
    </x-slot>

    <x-slot name="sidebar">
        <x-section class="mb-5">
            <h3>Sidebar</h3>
            <p class="mb-3">
                Sidebar only shows when content was set.
            </p>
            <p>
                <b>Use paragraphs instead of <code>{{ '<br>' }}</code> wherever it makes sense!</b><br>
                <code>{{ '<br>' }}</code>s are meant for single line breaks.
            </p>
        </x-section>

        <x-section>
            <h3>Feature Flags</h3>
            <x-feature-flags />
        </x-section>

        <div>
            This is not in a {{ '<section>' }}.
        </div>
    </x-slot>

    @php
    $user = request()->user() ?? App\Models\User::first();
    @endphp

    <x-section class="mb-8">
        <x-section-header class="mb-3">
            <x-slot name="title">
                <h2>Title</h2>
            </x-slot>
            <x-slot name="actions">
                @foreach([null, 'warning', 'danger'] as $modifier)
                    <x-base.button class="{{ $modifier ? 'btn-' . $modifier : '' }}">
                        <x-fas-heart/>
                        Action
                    </x-base.button>
                @endforeach
            </x-slot>
            <p>Text in section header -> actions will be below on smaller screens.</p>
        </x-section-header>
        <h3>
            {{ '<h3>' }} headlines in subsections.
        </h3>
        <p class="mb-3">Paragraph - Lorem ipsum dolor sit amet, consectetur adipisicing elit. Amet, asperiores atque commodi consequatur cumque expedita facilis impedit, laudantium maiores molestias optio possimus quae quaerat quis quisquam reiciendis, sed tempore tenetur.</p>
        <p class="mb-3 bg-embedded px-5 py-10">
            Utility classes allow to set margins, paddings, etc. easily without having to recompile CSS all the time.
        </p>
        <p class="text-danger">Danger Text</p>
        <p class="text-muted">Muted Text</p>
        <p><small>Small Text</small></p>
    </x-section>

    <x-section class="mb-8">
        <x-section-header>
            <x-slot name="title">
                <h2>Links - canonical and permalinks</h2>
            </x-slot>
        </x-section-header>
        @if($user)
            <x-user.avatar :user="$user" display="icon" icon-size="xs"/>
            <x-user.avatar :user="$user"/>
            <x-base.button.permalink :model="$user" class="btn">
                permalink: {{ $user->permalink }}
            </x-base.button.permalink>
            {{--<x-user.edit>test</x-user.edit>--}}
            {{--@userIcon($user, 10) @user($user)--}}
        @endif
    </x-section>

    <x-section class="mb-8">
        <x-section-header>
            <x-slot name="title">
                <h2>{{ '<h2>' }} headline in main content</h2>
            </x-slot>
        </x-section-header>
    </x-section>

    <x-section class="mb-8">
        <x-section-header>
            <x-slot name="title">
                <h2>Icons & Flags</h2>
            </x-slot>
        </x-section-header>
        <p>
            <x-fas-thumbs-up class="text-green-500"/>
            <x-fas-thumbs-up class="text-success"/>
            <x-fas-heart class="text-red-500"/>
        </p>
        <p>
            <x-fas-bell class="h-6 w-6"/>
            <x-fas-envelope class="h-6 w-6"/>
            <x-fas-search class="h-6 w-6"/>
            <x-fas-paper-plane class="h-6 w-6"/>
        </p>
        <p>
            <x-flag-4x3-gb/>
            <x-flag-4x3-us/>
            <x-flag-4x3-br/>
        </p>
    </x-section>

    <x-section class="mb-8">
        <x-section-header>
            <x-slot name="title">
                <h2>Buttons</h2>
            </x-slot>
        </x-section-header>
        @foreach([null, 'warning', 'danger'] as $modifier)
            <x-base.button class="{{ $modifier ? 'btn-' . $modifier : '' }} mb-2">
                <x-fas-exclamation-triangle/>
                {{ $modifier }}
            </x-base.button>
        @endforeach
    </x-section>

    <x-section class="mb-8">
        <x-section-header>
            <x-slot name="title">
                <h2>Forms</h2>
            </x-slot>
        </x-section-header>
    </x-section>

    <x-section class="mb-8">
        <x-section-header>
            <x-slot name="title">
                <h4>Form inputs</h4>
            </x-slot>
        </x-section-header>
        <x-base.form>
            <div class="lg:grid grid-cols-2 gap-3">
                <div>
                    <x-base.form.input.checkbox label="Checkbox" />
                    <x-base.form.input.checkbox label="Checkbox checked" checked />
                    <x-base.form.input.checkbox label="Checkbox disabled" checked disabled />
                    <x-base.form.input.checkbox>
                        Checkbox with long label text<br>a break<br>and a <x-link link="{{ route('demo') }}">Link</x-link>
                    </x-input.checkbox>
                    <x-base.form.input.code label="Code" />
                    <x-base.form.input.date label="Date" value="2024-12-31" :fullWidth="false" />
                    <x-base.form.input.datetime-local label="Datetime local" value="2024-12-31 00:00:00" :fullWidth="false" />
                    <x-base.form.input.email label="Email" value="test@example.com" :fullWidth="false" />
                    <x-base.form.input.file label="File" :fullWidth="false" />
                    <x-base.form.input.image label="Image" :fullWidth="false" />
                </div>
                <div>
                    <x-base.form.input.number label="Number (stepper)" value="1234.5" :fullWidth="false" />
                    <x-base.form.input.password value="password" :fullWidth="false" />
                    <x-base.form.input.password-confirmed value="password" :fullWidth="false" />
                    <x-base.form.search label="Search" :fullWidth="false" />
                    <x-base.form.select label="Select with preselected value" :options="[5 => 'Integer value 5', 4 => 'Integer value 4', '3' => 'String value 3', '2' => 'String value 2']" value="3" />
                    <x-base.form.select label="Select with only one option, not required" :options="['Only option']" />
                    <x-base.form.select label="Select with only one option, required" required :options="['Only option and required lorem ipsum']" :fullWidth="false" />
                    <x-base.form.select label="Select with long options" required :options="['A long option label lorem ipsum', 'Another long option label lorem ipsum']" :fullWidth="false" />
                </div>
            </div>
        </x-base.form>
    </x-section>

    <x-base.form x-on:submit.prevent="alert('Preventing submission')" validate>
        <x-section class="mb-8">
            <x-section-header>
                <x-slot name="title">
                    <h4>Inline Form with only required fields</h4>
                </x-slot>
                <x-slot name="actions">
                    <div>Valid: <span x-text="isValid"></span></div>
                    <div>Sending: <span x-text="isSending"></span></div>
                    <x-base.button.submit class="btn-warning" icon="heart">{{ __('Submit in section header') }}</x-base.button.submit>
                </x-slot>
            </x-section-header>
            <x-base.form.input name="text" requiredSilent inline help="Some helpful text describing this input" />
            <x-base.form.user-select requiredSilent label="{{ __res('user', 1) }}" value="Scott" inline help="Scott preselected" />
            <x-base.form.user-select requiredSilent inline help="Search user" />
            <x-base.form.input type="hidden" name="email" requiredSilent inline />
            <x-base.form.select label="Select with preselected value" :options="[5 => 'Integer value 5', 4 => 'Integer value 4', '3' => 'String value 3', '2' => 'String value 2']" value="3" inline :fullWidth="false" />
            <x-base.form.textarea label="{{ __res('message', 1) }}" requiredSilent inline maxlength="20" help="Some helpful text describing this input" />
            <x-base.form.input.checkbox label="Checkbox" checked inline requiredSilent />
            <x-base.form-actions inline />
        </x-section>
    </x-base.form>

    <x-base.form x-on:submit.prevent="alert('Preventing submission')" validate>
        <x-section class="mb-8">
            <x-section-header>
                <x-slot name="title">
                    <h4>Form with multiple rich-text textareas</h4>
                </x-slot>
                <x-slot name="actions">
                    <div>Valid: <span x-text="isValid"></span></div>
                    <div>Sending: <span x-text="isSending"></span></div>
                </x-slot>
            </x-section-header>
            <x-base.form.textarea name="message" required richText maxlength="20000" help="Textarea with rich-text controls" />
            <x-base.form.textarea name="body" richText maxlength="60000" help="Textarea with rich-text controls" />
            <x-base.form-actions hasRequiredFields />
        </x-section>
    </x-base.form>

    <x-section>
        <x-section-header>
            <x-slot name="title">
                <h4>Styled form inputs (Tailwind)</h4>
            </x-slot>
        </x-section-header>
        <x-base.form>
            <div class="flex gap-3 flex-col items-start">
                <input type="text" class="form-input" value="test">
                <textarea class="form-textarea">Test</textarea>
            </div>
        </x-base.form>
    </x-section>
</x-demo-layout>
