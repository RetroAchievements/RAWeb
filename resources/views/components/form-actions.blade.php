<x-form-field>
    <div class="flex items-center lg:ml-36">
        <x-button.save />
        @if($requiredFields ?? false)
            <div class="ml-3">
                * {{ __('Required') }}
            </div>
        @endif
        <div class="ml-auto">
            {{ $slot }}
        </div>
    </div>
</x-form-field>
