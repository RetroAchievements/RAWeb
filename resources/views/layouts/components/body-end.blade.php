{{-- TODO add livewire--}}
{{--<livewire:scripts />--}}
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

        #debug {
            position: fixed;
            @if(app()->environment('local'))
            bottom: 40px;
            @else
            bottom: 10px;
            @endif
            left: 4px;
            z-index: 100;
        }
    </style>

    <div id="debug" role="button" x-init="{}" @click="handleClick($el)" class="text-2xs flex flex-col rounded-lg bg-black/20 p-1">
        <b class="text-danger text-capitalize">{{ app()->environment() }} ({{ config('app.branch') }})</b>

        <div>
            <b>
                <span class="sm:hidden">XS</span>
                <span class="hidden sm:inline-block md:hidden">SM</span>
                <span class="hidden md:inline-block lg:hidden">MD</span>
                <span class="hidden lg:inline-block xl:hidden">LG</span>
                <span class="hidden xl:inline-block 2xl:hidden">XL</span>
                <span class="hidden 2xl:inline-block">2XL</span>
            </b>
            <b>{{ app()->getLocale() }}</b>
            <b>{{ Locale::getDisplayLanguage(app()->getLocale()) }}</b>
        </div>
    </div>
@endif
