@props([
    'actionsClass' => null,
    'class' => null,
    'style' => null,
    'title' => null,
])

<div class="flex flex-wrap justify-between {{ $class }}">
    {{--<div class="w-100">--}}{{-- width 100% to have navigations not extend out of viewport --}}
    <div class="">
        <div class="">
            {{ $title }}
        </div>
        @if(trim((string) $slot))
            <div>
                {{ $slot }}
            </div>
        @endif
    </div>
    <div class="actions-container">
        @if(trim((string) ($actions ?? '')))
            <div class="actions {{ $actionsClass }}">
                <div class="flex gap-1 justify-end items-center">
                    {{ $actions }}
                </div>
            </div>
        @endif
    </div>
</div>
