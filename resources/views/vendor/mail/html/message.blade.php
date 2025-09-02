@props([
    'granularUrl' => null,
    'granularText' => null,
    'categoryUrl' => null,
    'categoryText' => null,
])

<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
<img src="{{ asset('assets/images/ra-icon-mail.png') }}" alt="{{ config('app.name') }} Logo" style="max-height: 50px; float: left;">
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{{ $slot }}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{{ $subcopy }}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
@if($granularUrl || $categoryUrl)

@if($granularUrl && $granularText)
[{{ $granularText }}]({{ $granularUrl }})@if($categoryUrl && $categoryText) • @endif
@endif
@if($categoryUrl && $categoryText)
[{{ $categoryText }}]({{ $categoryUrl }})
@endif
• [Manage all email preferences]({{ route('settings.show') }})
@endif
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
