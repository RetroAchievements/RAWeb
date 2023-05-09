<table class="table-highlight">
    <tbody x-ref="playerTable">
        <template x-for="player in filteredPlayers">
            <tr>
                <td class="w-[52px]">
                    <div x-html="player.userAvatarHtml"></div>
                </td>
                
                <td class="w-[52px]">
                    <div x-html="player.gameAvatarHtml"></div>
                </td>

                <td x-text="player.RichPresenceMsg"></td>
            </tr>
        </template>
    </tbody>
</table>