<x-container :fluid="$fluid ?? false">
    <main class="{{ $class ?? 'mb-5' }}" data-scroll-target>
        @if(trim($sidebar ?? false))
            <div class="lg:grid grid-cols-[1fr_340px] gap-4">
                <article class="{{ $sidebarPosition === 'right' ? 'order-2' : 'order-1'}}">
                    {{ $slot }}
                </article>
                <aside class="{{ $sidebarPosition === 'right' ? 'order-2' : 'order-1'}}">
                    {{ $sidebar }}
                </aside>
            </div>
        @else
            <article>
                {{ $slot }}
            </article>
        @endif
    </main>
</x-container>
