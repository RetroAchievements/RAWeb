<table class="table-highlight">
    <tbody x-ref="playerTable">
        @foreach ($activePlayers as $activePlayer)
            <tr>
                <td class="w-[52px]">
                    {!! userAvatar($activePlayer['User'], iconSize: 32, label: false) !!}
                    <span class="hidden">{{ $activePlayer['User'] }}
                </td>

                <td class="w-[52px]">
                    {!! gameAvatar(['ID' => $activePlayer['GameID'], 'ImageIcon' => $activePlayer['GameIcon']], iconSize: 32, label: false) !!}
                    <span class="hidden">{{ $activePlayer['GameTitle'] }}</span>
                </td>

                <td>{{ $activePlayer['RichPresenceMsg'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>