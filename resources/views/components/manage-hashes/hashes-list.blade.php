@props([
    'gameId' => 1,
    'hashes' => null, // Collection<GameHash>
    'myUsername' => '',
])

@if (!$hashes->isEmpty())
    <script>
    /**
     * @param {Event} event
     * @param {string} hash
     */
    function updateHashDetails(event, hash) {
        event.preventDefault();
    
        const gameId = {{ $gameId }};
        const user = "{{ $myUsername }}";
        const name = document.querySelector(`#HASH_${hash}_Name`).value.trim();
        const labels = document.querySelector(`#HASH_${hash}_Labels`).value.trim();
        const patchUrl = document.querySelector(`#HASH_${hash}_PatchURL`).value.trim();
        const source = document.querySelector(`#HASH_${hash}_SourceURL`).value.trim();
    
        showStatusMessage('Updating hash...');
        $.ajax({
            url: `/game-hash/${hash}`,
            type: 'PUT',
            data: {
                name,
                labels,
                source,
                patch_url: patchUrl
            },
            success: () => {
                // Hard refresh the page rather than doing an optimistic UI update.
                window.location.reload();
            },
        });
    }
    
    /**
     * @param {string} hash
     */
    function unlinkHash(hash) {
        const hashName = document.querySelector(`#HASH_${hash}_Name`).value.trim();

        if (!confirm(`Are you sure you want to unlink hash ${hashName} (${hash}) from this game?`)) {
            return;
        }
    
        showStatusMessage('Unlinking hash...');
        $.ajax({
            url: `/game-hash/${hash}`,
            type: 'DELETE',
            success: () => {
                // Hard refresh the page rather than doing an optimistic UI update.
                window.location.reload();
            }
        });
    }
    </script>

    <div class="flex flex-col gap-y-4">
        @foreach ($hashes as $hashEntity)
            <x-manage-hashes.hash-entity
                :gameId="$gameId"
                :hashEntity="$hashEntity"
                :myUsername="$myUsername"
            />
        @endforeach
    </div>
@endif
