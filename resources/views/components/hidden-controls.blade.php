@props([
    'name' => 'hiddenControl',
])

<script>
    function toggle{{ $name }}() {
        const buttonEl = document.getElementById('{{ $name }}-toggle-button');
        const contentEl = document.getElementById('{{ $name }}-content');
        if (contentEl && buttonEl) {
            contentEl.classList.toggle('hidden');
            buttonEl.innerHTML = buttonEl.innerText.substring(0, buttonEl.innerText.length-1) +
                (contentEl.classList.contains('hidden') ? "▼" : "▲");
        }
    }
</script>

<div
    id="{{ $name }}-content"
    class="hidden py-2 px-4 -mx-5 -mt-3 sm:-mt-1.5 mb-4"
>
    <div class="mx-1 -my-2 bg-embed p-4 rounded">
        {{ $slot }}
    </div>
</div>