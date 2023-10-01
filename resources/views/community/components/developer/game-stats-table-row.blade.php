@props([
    'headingLabel' => '',
])

<tr>
    <td width="50%">{{ $headingLabel }}</td>
    <td>{{ $slot }}</td>
</tr>
