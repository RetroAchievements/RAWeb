@props([
    'hash' => null, // GameHash
])

<li>
    <p>
        @if ($hash->name)
            <span class="font-bold">{{ $hash->name }}</span>
        @endif

        @if (!empty($hash->labels))
            @foreach (explode(',', $hash->labels) as $label)
                @if (empty($label))
                    @continue;
                @endif

                @php
                    $image = "/assets/images/labels/" . $label . '.png';
                    $publicPath = public_path($image);
                @endphp
                
                @if (file_exists($publicPath))
                    <img class="inline-image" src="{{ asset($image) }}">
                @else
                    <span>[{{ $label }}]</span>
                @endif
            @endforeach
        @endif
    </p>

    <div class="flex flex-col">
        <p class="font-mono text-neutral-200 light:text-neutral-700">
            {{ $hash->md5 }}
        </p>

        @if ($hash->patch_url)
            <a href="{{ $hash->patch_url }}" rel="noreferrer">Download Patch File</a>
        @endif
    </div>
</li>
