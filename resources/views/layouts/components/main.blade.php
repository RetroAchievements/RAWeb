<x-container :fluid="$fluid ?? false">
    <main class="mb-5 {{ trim($sidebar ?? false) ? 'with-sidebar' : '' }}" data-scroll-target>
        @if(trim($sidebar ?? false))
            <article class="{{ $sidebarPosition === 'right' ? 'order-2' : 'order-1'}}">
                {{ $slot }}
            </article>
            <aside class="{{ $sidebarPosition === 'right' ? 'order-2' : 'order-1'}}">
                {{ $sidebar }}
            </aside>
        @else
            <article>
                {{ $slot }}
            </article>
        @endif
    </main>
</x-container>
