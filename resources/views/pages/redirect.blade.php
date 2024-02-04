<x-app-layout :page-title="__('Redirect')">
    <x-section>
        <x-section-header>
            <x-slot name="title"><h2>{{ __('Redirect') }}</h2></x-slot>
            <p class="lead mb-2">
                {{ __('The previous page is sending you to') }}
                <a href="{{ $url }}" rel="noreferrer">{{ $url }}</a>.
            </p>
            <p class="mb-0">
                <a href="javascript:window.history.back()">{{ __('Return to the previous page') }}</a>.
            </p>
        </x-section-header>
    </x-section>
</x-app-layout>
