<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<x-head/>
<body
    data-scheme="{{ request()->cookie('scheme', '') }}"
    data-theme="{{ request()->cookie('theme', '') }}"
    class="{{ config('app.debug') ? 'debug' : '' }} flex flex-col justify-center with-footer"
>
<div class="w-full max-w-[320px] mx-auto">
    <x-brand-top-prompt/>
    <x-header class="mb-5 text-center">
        {{ $header ?? '' }}
    </x-header>
    {{--<x-messages/> TODO differentiate between validation errors and custom errors --}}
    <x-main>
        {{ $slot }}
    </x-main>
</div>
<footer>
    <x-footer-navigation/>
</footer>
<x-body-end/>
</body>
</html>
