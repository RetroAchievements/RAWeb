@livewireScriptConfig
@if(!app()->environment('production'))
    <script>
        function handleClick(el) {
            el.classList.add('hidden');
        }
    </script>

    <style nonce="{{ csp_nonce() }}">
        pre.xdebug-var-dump {
            background: #FFFFFF;
        }
    </style>

    <div
        id="debug"
        role="button"
        x-init="{}"
        @click="handleClick($el)"
        @class([
            'fixed left-0 -bottom-0.5 z-50 sm:left-1',
            'flex gap-1 sm:flex-col rounded bg-black p-1 text-neutral-200 text-2xs',
            app()->isLocal() ? 'sm:bottom-10' : 'sm:bottom-2.5'
        ])
    >
        <span class="font-bold text-danger text-capitalize">
            {{ app()->environment() }}
            {{ $_SERVER['LARAVEL_OCTANE'] ?? false ? '[Octane]' : '' }}
            @if (!app()->isLocal())
                ({{ config('app.branch') }})
            @endif
        </span>

        <div>
            <span class="font-medium">
                <span class="sm:hidden">XS</span>
                <span class="hidden sm:inline-block md:hidden">SM</span>
                <span class="hidden md:inline-block lg:hidden">MD</span>
                <span class="hidden lg:inline-block xl:hidden">LG</span>
                <span class="hidden xl:inline-block 2xl:hidden">XL</span>
                <span class="hidden 2xl:inline-block">2XL</span>
            </span>
        </div>

        <div>
            @php
                $currentLocale = Auth::user()?->locale ?? app()->getLocale();
                if ($currentLocale === 'en') {
                    $currentLocale = 'en_US';
                }
            @endphp
            <span>{{ $currentLocale }}</span>
        </div>
    </div>
@endif
