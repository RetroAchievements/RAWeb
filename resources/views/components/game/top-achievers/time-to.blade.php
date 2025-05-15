@props([
    'label' => '',
    'value' => 0,
    'count' => 0,
])

<tr>
    <td class="whitespace-nowrap">{{ $label }}</td>
    <td class="whitespace-nowrap text-right">
        @if ($count < 5)
            @if ($count == 0)
                <span class="smalltext">No samples available</span>
            @else
                <span class="smalltext">More samples needed</span>
            @endif
        @else
            @if ($value > 60 * 60)
                {{ floor($value / (60 * 60)) }}h
            @endif
            {{ ($value / 60) % 60 }}m
            <span class="smalltext">({{ $count }})</span>
        @endif
    </td>
</tr>
