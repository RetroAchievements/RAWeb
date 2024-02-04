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
                    <x-button class="{{ $modifier ? 'btn-' . $modifier : '' }}">
                        <x-fas-fire/>
                        Action
                    </x-button>
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
        <x-section>
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

    <?php
    $user = request()->user() ?? App\Models\User::first();
    ?>
    <x-section>
        <x-section-header class="mb-3">
            <x-slot name="title">
                <h2>Title</h2>
            </x-slot>
            <x-slot name="actions">
                @foreach([null, 'warning', 'danger'] as $modifier)
                    <x-button class="{{ $modifier ? 'btn-' . $modifier : '' }}">
                        <x-fas-heart/>
                        Action
                    </x-button>
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
    <x-section>
        <x-section-header>
            <x-slot name="title">
                <h2>Links - canonical and permalinks</h2>
            </x-slot>
        </x-section-header>
        @if($user)
            <x-user.avatar :user="$user" display="icon" icon-size="xs"/>
            <x-user.avatar :user="$user"/>
            <x-button.permalink :model="$user" class="btn">
                permalink: {{ $user->permalink }}
            </x-button.permalink>
            {{--<x-user.edit>test</x-user.edit>--}}
            {{--@userIcon($user, 10) @user($user)--}}
        @endif
    </x-section>
    <x-section>
        <x-section-header>
            <x-slot name="title">
                <h2>{{ '<h2>' }} headline in main content</h2>
            </x-slot>

        </x-section-header>
    </x-section>
    <x-section>
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
    <x-section>
        <x-section-header>
            <x-slot name="title">
                <h2>Buttons</h2>
            </x-slot>
        </x-section-header>
        <div class="mb-3">
            @foreach([null, 'warning', 'danger'] as $modifier)
                <x-button class="{{ $modifier ? 'btn-' . $modifier : '' }} mb-2">
                    <x-fas-exclamation-triangle/>
                    {{ $modifier }}
                </x-button>
            @endforeach
        </div>
    </x-section>
    <x-section>
        <x-section-header>
            <x-slot name="title">
                <h2>Form Inputs</h2>
            </x-slot>
        </x-section-header>
        <h4>Unstyled input</h4>
        <div class="flex gap-3 flex-col items-start">
            <input type="text" value="test">
            <textarea>Test</textarea>
        </div>
        <h4>Styled input</h4>
        <div class="flex gap-3 flex-col items-start">
            <input type="text" class="form-input" value="test">
            <textarea class="form-textarea">Test</textarea>
        </div>
    </x-section>
</x-demo-layout>
