@props([
    'src',
    'alt' => '',
    'url' => '',
    'width' => 64,
    'height' => 64,
])
<table class="panel" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="panel-content">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td width="{{ $width }}">
@if ($url)<a href="{{ $url }}">@endif
<img src="{{ $src }}" @if ($alt)alt="{{ $alt }}"@endif width="{{ $width }}" height="{{ $height }}" />
@if ($url)</a>@endif
</td>
</td>
<td style="padding-left: 8px; vertical-align:top">
{{ $slot }}
</td>
</tr>
</table>
</td>
</tr>
</table>

