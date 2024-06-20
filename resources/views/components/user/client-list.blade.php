@props([
    'clients' => [],
])

@if (empty($clients))
    <span class="text-muted">No client data available</span>
@else
    <span>
        <span class="font-bold">Clients used:</span>
        <span>
            @foreach ($clients as $client => $data)
                <span title="{{ implode("\n", $data['agents']) }}">
                    {{ $client }}
                </span>
                @if (count($clients) > 1)
                    <span class="smalltext">({{ $data['durationPercentage'] }}%)</span>{{ $loop->last ? '' : ', ' }}
                @endif
            @endforeach
        </span>
    </span>
@endif
