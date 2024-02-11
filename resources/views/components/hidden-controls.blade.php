<script>
    function toggleHiddenControls() {
        const buttonEl = document.getElementById('hidden-controls-toggle-button');
        const contentEl = document.getElementById('hidden-controls-content');
        if (contentEl) {
            if (contentEl.classList.contains('hidden')) {
                contentEl.classList.remove('hidden');
                if (buttonEl) {
                    buttonEl.innerHTML = buttonEl.innerText.substring(0, buttonEl.innerText.length-1) + "▲";
                }
            } else {
                contentEl.classList.add('hidden');
                if (buttonEl) {
                    buttonEl.innerHTML = buttonEl.innerText.substring(0, buttonEl.innerText.length-1) + "▼";
                }
            }
        }
    }
</script>

<div
    id="hidden-controls-content"
    class="hidden py-2 px-4 -mx-5 -mt-3 sm:-mt-1.5 mb-4"
>
    <div class="mx-1 -my-2 bg-embed p-4 rounded">
        {{ $slot }}
    </div>
</div>