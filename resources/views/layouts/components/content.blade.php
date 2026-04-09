<x-header fluid>
    {{ $header ?? '' }}
</x-header>
@if(trim((string) ($breadcrumb ?? '')))
    <x-container>
        <x-breadcrumb>
            {{ $breadcrumb }}
        </x-breadcrumb>
    </x-container>
@endif
<x-messages />
{{ $slot }}
