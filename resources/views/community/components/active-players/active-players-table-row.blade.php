@props(['activePlayer'])

<?php
$hasUnknownMacro = str_contains($activePlayer['RichPresenceMsg'], 'Unknown macro');
?>

{{-- When you update this template, you MUST also update the Alpine.js updateTable() function to match. --}}
<tr>
    <td width="44">{!! userAvatar($activePlayer['User'], label: false) !!}</td>
    <td width="44">
        <div
            x-data="tooltipComponent($el, {dynamicType: 'game', dynamicId: '{{ $activePlayer['GameID'] }}'})"
            @mouseover="showTooltip($event)"
            @mouseleave="hideTooltip"
            @mousemove="trackMouseMovement($event)"
        >
            <a href="{{ route('game.show', $activePlayer['GameID']) }}">
                <img
                    src="{{ media_asset($activePlayer['GameIcon']) }}"
                    alt="{{ $activePlayer['GameTitle'] }} game badge"
                    width="32"
                    height="32"
                    loading="lazy"
                    decoding="async"
                    class="badgeimg"
                >
            </a>
        </div>
    </td>

    <td @if ($hasUnknownMacro) class="cursor-help" title="{{ $activePlayer['RichPresenceMsg'] }}" @endif>
        @if ($hasUnknownMacro)
            ⚠️ Playing {{ $activePlayer['GameTitle'] }}
        @else
            {{ $activePlayer['RichPresenceMsg'] }}
        @endif
    </td>
</tr>
