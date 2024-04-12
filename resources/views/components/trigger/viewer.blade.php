@props([
    'groups' => [],
])

<script>
    function toggleNotes($i) {
        const buttonEl = document.getElementById('toggle-notes' + $i);
        const contentEl = document.getElementById('notes' + $i);
        if (contentEl && buttonEl) {
            contentEl.classList.toggle('hidden');
            buttonEl.innerHTML = buttonEl.innerText.substring(0, buttonEl.innerText.length-1) +
                (contentEl.classList.contains('hidden') ? "▼" : "▲");
        }
    }
</script>

<table class="table-highlight">
    @php $j = 1 @endphp
    @foreach ($groups as $group)
        <tr class="do-not-highlight text-center">
            <td colspan="10"><b>{{ $group['Label'] }}</b></td>
        </tr>
        <tr class="do-not-highlight">
            <th class="whitespace-nowrap text-right">ID</th>
            <th class="whitespace-nowrap">Flag</th>
            <th class="whitespace-nowrap">Type</th>
            <th class="whitespace-nowrap">Size</th>
            <th class="whitespace-nowrap">Memory</th>
            <th class="whitespace-nowrap">Cmp</th>
            <th class="whitespace-nowrap">Type</th>
            <th class="whitespace-nowrap">Size</th>
            <th class="whitespace-nowrap">Mem/Val</th>
            <th class="whitespace-nowrap">Hits</th>
        </tr>

        @php $i = 1 @endphp
        @foreach ($group['Conditions'] as $condition)
            <tr class="whitespace-nowrap font-mono">
                <td class="text-muted text-right">{{ $i }}</td>
                <td>{{ $condition['Flag'] }}</td>
                <td>{{ $condition['SourceType'] }}</td>
                <td>{{ $condition['SourceSize'] }}</td>
                @if (($condition['SourceTooltip'] ?? '') !== '')
                    <td class="cursor-help" title="{{ $condition['SourceTooltip'] }}">{{ $condition['SourceAddress'] }}</td>
                @else
                    <td>{{ $condition['SourceAddress'] }}</td>
                @endif
                @if ($condition['Operator'] === '')
                    <td colspan="5"></td>
                @else
                    <td>{{ $condition['Operator'] }}</td>
                    <td>{{ $condition['TargetType'] }}</td>
                    <td>{{ $condition['TargetSize'] }}</td>
                    @if (($condition['TargetTooltip'] ?? '') !== '')
                        <td class="cursor-help" title="{{ $condition['TargetTooltip'] }}">{{ $condition['TargetAddress'] }}</td>
                    @else
                        <td>{{ $condition['TargetAddress'] }}</td>
                    @endif
                    @if ($condition['HitTarget'] === '')
                        <td></td>
                    @else
                        <td>({{ $condition['HitTarget'] }})</td>
                    @endif
                @endif
            </tr>
            @php $i = $i + 1 @endphp
        @endforeach
        @if (!empty($group['Notes']))
            <tr class="do-not-highlight">
                <td colspan="10">
                    <div class="flex flex-col w-full">
                        <button id="toggle-notes{{ $j }}" onclick="toggleNotes({{ $j }})">Notes ▼</button>
                        <table id="notes{{ $j }}" class="hidden">
                            @foreach ($group['Notes'] as $addr => $note)
                                <tr>
                                    <td class="whitespace-nowrap align-top font-mono"><b>{{ $addr }}</b></td>
                                    <td class="text-xs"><code>{{ $note }}<code></td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </td>
            </tr>
        @endif
    @endforeach
</table>
